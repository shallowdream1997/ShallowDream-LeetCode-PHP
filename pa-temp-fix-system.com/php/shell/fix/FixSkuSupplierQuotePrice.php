<?php

require_once dirname(__FILE__) . '/../../../php/bootstrap.php';
/**
 * 批量修复 sku_supplier 的 lastQuotePrice 和 lastQuoteDate
 *
 * 逻辑：
 * 1. 根据CE单号通过 CETS SOAP 查询 CE 单明细数据，获取 cost 和 qualityTestFinishOn
 * 2. 可选：通过 SKU ID 过滤，只处理指定的 SKU；不传 SKU 则处理该 CE 单下所有 SKU
 * 3. 根据 SKU ID 列表通过 CETS SOAP 查询 sku_supplier 数据（获取 skuSupplierId、modifiedOn、当前值）
 * 4. 对每个有 CE 明细的 sku_supplier，用 CE 单的 cost 更新 lastQuotePrice，用 qualityTestFinishOn 更新 lastQuoteDate
 * 5. 通过 CETS SOAP updateSkuSupplier 执行更新
 *
 * 用法：
 *   php FixSkuSupplierQuotePrice.php [环境] [CE单号] [dryRun] [SKU列表]
 *   环境:   pro | test | uat (默认 pro)
 *   CE单号: 必填，逗号分隔的 CE 单号列表
 *   dryRun: true(默认,只预览) | false(执行更新)
 *   SKU列表: 可选，逗号分隔的 skuId，不传则处理 CE 单下所有 SKU
 *
 * 参照: PHP mro_judge_source_recruit.php::deactivateOriginAndBindNew()
 *       PHP CeBillTool.php::getCeDetailByCeBillNoList
 *       Java SkuSupplierTransferService#createNewSkuSupplier
 */


class FixSkuSupplierQuotePrice
{
    private MyLogger $log;

    // CETS SOAP URL
    private $cetsMasterUrl;
    private $cetsQueryUrl;

    private $env;

    public function __construct($env = 'pro')
    {
        $this->env = $env;
        $this->log = new MyLogger("pa_biz_application");

        // CETS SOAP URL - 参照 calculate_client production.php
        if ($env === 'test') {
            $this->cetsMasterUrl = "http://172.16.10.46:8080/cets_app";
            $this->cetsQueryUrl = "http://172.16.10.43:8080/cets_app";
        } elseif ($env === 'uat') {
            $this->cetsMasterUrl = "http://172.16.11.221:8080/cets_app";
            $this->cetsQueryUrl = "http://172.16.11.221:8080/cets_app";
        } else {
            // pro 环境
            $this->cetsMasterUrl = "http://master.app.cets.ux168.cn:8080/cets_app";
            $this->cetsQueryUrl = "http://query.app.cets.ux168.cn:8080/cets_app";
        }
    }

    private function logMsg($message = "")
    {
        $this->log->log2($message);
        echo $message . "\n";
    }

    /**
     * 通过 CETS SOAP 按 CE 单号查询 CE 单明细数据
     * 参照: CeBillTool.php::getCeDetailByCeBillNoList
     * 使用 getCeDetailByConditionsEx 传入 CE_BillNoList 条件，支持分页
     *
     * @param array $ceBillNoList CE 单号列表
     * @return array skuId => object(ceDetail记录，含cost, qualityTestFinishOn, skuId, ceBillNo 等)
     */
    private function queryCeDetailByCeBillNos($ceBillNoList)
    {
        $this->logMsg("[查询CE明细] 按CE单号SOAP查询, ceBillNoCount=" . count($ceBillNoList));

        $skuCeDetailMap = array();

        try {
            $wsdl = $this->cetsQueryUrl . "/CeBillComponent?wsdl";
            $client = new SoapClient($wsdl, array(
                'trace' => false,
                'exceptions' => true,
                'connection_timeout' => 30,
                'cache_wsdl' => WSDL_CACHE_BOTH
            ));

            // 参照 CeBillTool::commonGetCeDetail，使用 getCeDetailByConditionsEx 分页查询
            $condition = array("CE_BillNoList" => $ceBillNoList);
            $conditionJson = json_encode($condition);

            $pageNumber = 1;
            $entriesPerPage = 100;
            $allCeDetails = array();

            do {
                $retry = 0;
                $retryMax = 3;
                $resp = null;
                do {
                    try {
                        $resp = $client->getCeDetailByConditionsEx(array(
                            'conditionsJsonEncode' => $conditionJson,
                            'orderBy' => '',
                            'pageNumber' => $pageNumber,
                            'entriesPerPage' => $entriesPerPage
                        ));
                        break;
                    } catch (Exception $e) {
                        $retry++;
                        if ($retry >= $retryMax) {
                            throw $e;
                        }
                        $this->logMsg("[查询CE明细] 重试第{$retry}次: " . $e->getMessage());
                    }
                } while (true);

                // 检查响应
                if (!isset($resp->ceDetailResponse)) {
                    $this->logMsg("[查询CE明细] 响应中无ceDetailResponse");
                    break;
                }
                $respObj = $resp->ceDetailResponse;
                if (isset($respObj->responseMessage) && $respObj->responseMessage->messageType !== 'success') {
                    $this->logMsg("[查询CE明细] SOAP返回失败: " . $respObj->responseMessage->messageContent);
                    break;
                }
                if (!isset($respObj->pagination) || $respObj->pagination->totalNumberOfPages <= 0) {
                    $this->logMsg("[查询CE明细] 无数据或分页为0");
                    break;
                }

                $ceDetails = isset($respObj->ceDetails) ? $respObj->ceDetails : array();
                if (!is_array($ceDetails)) {
                    $ceDetails = array($ceDetails);
                }
                $allCeDetails = array_merge($allCeDetails, $ceDetails);

                $totalNumberOfPages = $respObj->pagination->totalNumberOfPages;
                $pageNumber++;
            } while ($pageNumber <= $totalNumberOfPages);

            // 按 skuId 索引
            foreach ($allCeDetails as $detail) {
                $skuId = isset($detail->skuId) ? $detail->skuId : null;
                if ($skuId && !isset($skuCeDetailMap[$skuId])) {
                    $skuCeDetailMap[$skuId] = $detail;
                }
            }

            $this->logMsg("[查询CE明细] 按CE单号查询完成, totalDetails=" . count($allCeDetails) . ", mappingSize=" . count($skuCeDetailMap));
        } catch (Exception $e) {
            $this->logMsg("[查询CE明细] SOAP查询异常: " . $e->getMessage());
        }

        return $skuCeDetailMap;
    }

    /**
     * 通过 CETS SOAP 查询 sku_supplier 数据（获取 skuSupplierId、modifiedOn、当前字段值）
     * 参照: mro_judge_source_recruit.php::queryOriginSkuSupplier
     *
     * @param array $skuIdList SKU ID 列表
     * @return array skuId => object(skuSupplier记录，含skuSupplierId, supplierId, modifiedOn, lastQuotePrice, lastQuoteDate 等)
     */
    private function querySkuSupplierBySkuIds($skuIdList)
    {
        $this->logMsg("[查询sku_supplier] 开始SOAP查询, skuCount=" . count($skuIdList));

        $skuSupplierMap = array();
        $batchSize = 20;

        $batches = array_chunk($skuIdList, $batchSize);
        foreach ($batches as $batch) {
            try {
                $wsdl = $this->cetsQueryUrl . "/SkuSupplierComponent?wsdl";
                $client = new SoapClient($wsdl, array(
                    'trace' => false,
                    'exceptions' => true,
                    'connection_timeout' => 30,
                    'cache_wsdl' => WSDL_CACHE_BOTH
                ));

                $skuIdListJson = json_encode($batch);
                $response = $client->getSkuSupplierBySkuIds(array(
                    'skuIdListJsonEncode' => $skuIdListJson
                ));

                // 解析响应
                if (!isset($response->skuSupplierResponse)) {
                    $this->logMsg("[查询sku_supplier] 响应中无skuSupplierResponse");
                    continue;
                }

                $respObj = $response->skuSupplierResponse;
                // 检查 responseMessage
                if (isset($respObj->responseMessage) && $respObj->responseMessage->messageType !== 'success') {
                    $this->logMsg("[查询sku_supplier] SOAP返回失败: " . $respObj->responseMessage->messageContent);
                    continue;
                }

                // 提取 skuSuppliers 记录
                if (!isset($respObj->skuSuppliers)) {
                    continue;
                }
                $skuSuppliers = $respObj->skuSuppliers;
                if (!is_array($skuSuppliers)) {
                    $skuSuppliers = array($skuSuppliers);
                }

                // 过滤 supplierStatus=1(active) 的记录，参照 PHP queryOriginSkuSupplier
                foreach ($skuSuppliers as $skuSupplier) {
                    if ($skuSupplier->supplierStatus === 1) {
                        $skuId = $skuSupplier->skuId;
                        if (!isset($skuSupplierMap[$skuId])) {
                            $skuSupplierMap[$skuId] = $skuSupplier;
                        }
                    }
                }
            } catch (Exception $e) {
                $this->logMsg("[查询sku_supplier] SOAP查询异常: " . $e->getMessage());
            }
        }

        $this->logMsg("[查询sku_supplier] 查询完成, mappingSize=" . count($skuSupplierMap));
        return $skuSupplierMap;
    }

    /**
     * 通过 CETS SOAP 更新 sku_supplier
     * otherInfoJsonEncode 中的 key 必须和 CETS SkuSupplier bean 的属性名一致
     * 支持的字段: lastInPrice, lastInDate, lastQuotePrice, lastQuoteDate, supplierStatus, remark 等
     * 参照: mro_judge_source_recruit.php 中的 updateSkuSupplier / createSkuSupplier 调用
     *
     * @param int $skuSupplierId sku_supplier ID
     * @param string $modifiedOn 最后修改时间（乐观锁）
     * @param string $modifiedBy 修改人
     * @param array $updateInfo 更新内容（key 为 CETS SkuSupplier bean 属性名 => value）
     * @return bool 是否成功
     */
    private function updateSkuSupplier($skuSupplierId, $modifiedOn, $modifiedBy, $updateInfo)
    {
        try {
            $wsdl = $this->cetsMasterUrl . "/SkuSupplierComponent?wsdl";
            $client = new SoapClient($wsdl, array(
                'trace' => true,
                'exceptions' => true,
                'connection_timeout' => 30,
                'cache_wsdl' => WSDL_CACHE_BOTH
            ));

            $otherInfoJsonEncode = json_encode($updateInfo);

            $response = $client->updateSkuSupplier(array(
                'skuSupplierId' => $skuSupplierId,
                'lastModifiedOn' => $modifiedOn,
                'modifiedBy' => $modifiedBy,
                'otherInfoJsonEncode' => $otherInfoJsonEncode
            ));

            // 检查响应
            if (isset($response->skuSupplierResponse) && isset($response->skuSupplierResponse->responseMessage)) {
                $msg = $response->skuSupplierResponse->responseMessage;
                if ($msg->messageType === 'success') {
                    return true;
                }
                $this->logMsg("[SOAP更新] updateSkuSupplier 返回失败: skuSupplierId={$skuSupplierId}, message={$msg->messageContent}");
                return false;
            }
            if (isset($response->responseMessage)) {
                $msg = $response->responseMessage;
                if ($msg->messageType === 'success') {
                    return true;
                }
                $this->logMsg("[SOAP更新] updateSkuSupplier 返回失败: skuSupplierId={$skuSupplierId}, message={$msg->messageContent}");
                return false;
            }
            return true;
        } catch (Exception $e) {
            $this->logMsg("[SOAP更新] updateSkuSupplier 异常: skuSupplierId={$skuSupplierId}, error=" . $e->getMessage());
            return false;
        }
    }

    /**
     * 主逻辑：按 CE 单号批量更新 sku_supplier 的 lastQuotePrice 和 lastQuoteDate
     *
     * @param array $ceBillNoList CE 单号列表（必填）
     * @param array $filterSkuIds 过滤的 SKU ID 列表（可选，为空则处理 CE 单下所有 SKU）
     * @param bool $dryRun 是否只预览不执行（默认 true）
     */
    public function main($ceBillNoList, $filterSkuIds = array(), $dryRun = true)
    {
        $this->logMsg("========== 开始执行 FixSkuSupplierQuotePrice ==========");
        $this->logMsg("环境: {$this->env}, CE单号: " . implode(',', $ceBillNoList)
            . ", 过滤SKU: " . (empty($filterSkuIds) ? '全部' : implode(',', $filterSkuIds))
            . ", dryRun: " . ($dryRun ? 'YES' : 'NO'));

        // 1. 通过 CETS SOAP 按 CE 单号查询所有 SKU 明细
        $skuCeDetailMap = $this->queryCeDetailByCeBillNos($ceBillNoList);
        if (empty($skuCeDetailMap)) {
            $this->logMsg("CE单无明细数据，退出");
            return;
        }

        // 2. 如果传了 filterSkuIds，只保留指定的 SKU；否则处理全部
        if (!empty($filterSkuIds)) {
            $filteredMap = array();
            foreach ($filterSkuIds as $skuId) {
                if (isset($skuCeDetailMap[$skuId])) {
                    $filteredMap[$skuId] = $skuCeDetailMap[$skuId];
                } else {
                    $this->logMsg("[过滤] SKU {$skuId} 不在CE单明细中，跳过");
                }
            }
            $skuCeDetailMap = $filteredMap;
            $this->logMsg("[过滤] 过滤后SKU数量: " . count($skuCeDetailMap));
        }

        if (empty($skuCeDetailMap)) {
            $this->logMsg("过滤后无SKU数据，退出");
            return;
        }

        // 3. 提取 skuId 列表
        $skuIdList = array_keys($skuCeDetailMap);

        // 4. 通过 CETS SOAP 查询 sku_supplier 数据
        $skuSupplierMap = $this->querySkuSupplierBySkuIds($skuIdList);

        // 5. 逐 SKU 处理
        $successCount = 0;
        $skipCount = 0;
        $failCount = 0;
        $updateList = array();

        foreach ($skuIdList as $skuId) {
            $ceDetail = isset($skuCeDetailMap[$skuId]) ? $skuCeDetailMap[$skuId] : null;
            $skuSupplier = isset($skuSupplierMap[$skuId]) ? $skuSupplierMap[$skuId] : null;

            if (!$ceDetail) {
                $this->logMsg("[跳过] SKU {$skuId} 无CE单明细数据");
                $skipCount++;
                continue;
            }

            if (!$skuSupplier) {
                $this->logMsg("[跳过] SKU {$skuId} 无active sku_supplier数据");
                $skipCount++;
                continue;
            }

            // 从 CE 明细获取 cost 和 qualityTestFinishOn（SOAP 返回的是 object）
            $cost = isset($ceDetail->cost) ? $ceDetail->cost : null;
            $qualityTestFinishOn = isset($ceDetail->qualityTestFinishOn) ? $ceDetail->qualityTestFinishOn : null;

            if ($cost === null && $qualityTestFinishOn === null) {
                $this->logMsg("[跳过] SKU {$skuId} CE明细中cost和qualityTestFinishOn均为空");
                $skipCount++;
                continue;
            }

            // 从 SOAP 返回的 skuSupplier 获取更新所需字段
            $skuSupplierId = $skuSupplier->skuSupplierId;
            $supplierId = $skuSupplier->supplierId;
            $modifiedOn = $skuSupplier->modifiedOn;
            $currentLastQuotePrice = $skuSupplier->lastQuotePrice;
            $currentLastQuoteDate = $skuSupplier->lastQuoteDate;

            if (!$skuSupplierId || !$modifiedOn) {
                $this->logMsg("[失败] SKU {$skuId} 缺少skuSupplierId或modifiedOn");
                $failCount++;
                continue;
            }

            // 构建更新内容：参照 sync_sku_supplier_cets.php 的 otherInfo 结构
            // otherInfoJsonEncode 中的 key 必须和 CETS SkuSupplier bean 属性名一致
            // 把查询回来的现有值全部带上，只更新需要改的字段
            $updateInfo = array(
                'supplierSkuId' => isset($skuSupplier->skuSupplierId) ? $skuSupplier->skuSupplierId : '',
            );
            // 用 CE 单数据覆盖需要修复的字段
            if ($cost !== null) {
                $updateInfo['lastQuotePrice'] = is_numeric($cost) ? floatval($cost) : $cost;
            }
            if ($qualityTestFinishOn !== null) {
                $updateInfo['lastQuoteDate'] = $qualityTestFinishOn;
            }

            $updateList[] = array(
                'skuId' => $skuId,
                'skuSupplierId' => $skuSupplierId,
                'supplierId' => $supplierId,
                'modifiedOn' => $modifiedOn,
                'currentLastQuotePrice' => $currentLastQuotePrice,
                'currentLastQuoteDate' => $currentLastQuoteDate,
                'newLastQuotePrice' => isset($updateInfo['lastQuotePrice']) ? $updateInfo['lastQuotePrice'] : null,
                'newLastQuoteDate' => isset($updateInfo['lastQuoteDate']) ? $updateInfo['lastQuoteDate'] : null,
                'updateInfo' => $updateInfo,
            );
        }

        // 6. 输出更新计划
        $this->logMsg("");
        $this->logMsg("========== 更新计划 ==========");
        $this->logMsg("待更新: " . count($updateList) . " 条, 跳过: {$skipCount} 条, 失败: {$failCount} 条");
        $this->logMsg("");

        foreach ($updateList as $idx => $item) {
            $this->logMsg(sprintf("[%d] SKU=%s, supplierId=%s, skuSupplierId=%s",
                $idx + 1, $item['skuId'], $item['supplierId'], $item['skuSupplierId']));
            $this->logMsg(sprintf("    lastQuotePrice: %s → %s",
                $item['currentLastQuotePrice'] ?? 'null',
                $item['newLastQuotePrice'] ?? 'null'));
            $this->logMsg(sprintf("    lastQuoteDate:  %s → %s",
                $item['currentLastQuoteDate'] ?? 'null',
                $item['newLastQuoteDate'] ?? 'null'));
        }

        // 7. 执行更新
        if (!$dryRun) {
            $this->logMsg("");
            $this->logMsg("========== 开始执行更新 ==========");

            foreach ($updateList as $idx => $item) {
                $this->logMsg(sprintf("[%d] 更新 SKU=%s, skuSupplierId=%s ...",
                    $idx + 1, $item['skuId'], $item['skuSupplierId']));

                $success = $this->updateSkuSupplier(
                    $item['skuSupplierId'],
                    $item['modifiedOn'],
                    'fix_quote_price_script',
                    $item['updateInfo']
                );

                if ($success) {
                    $successCount++;
                    $this->logMsg(sprintf("[%d] 更新成功", $idx + 1));
                } else {
                    $failCount++;
                    $this->logMsg(sprintf("[%d] 更新失败", $idx + 1));
                }
            }
        } else {
            $this->logMsg("");
            $this->logMsg("========== DRY RUN 模式，未执行更新 ==========");
            $this->logMsg("如需执行更新，请传入 dryRun=false");
        }

        $this->logMsg("");
        $this->logMsg("========== 执行完成 ==========");
        $this->logMsg("待更新: " . count($updateList) . ", 成功: {$successCount}, 跳过: {$skipCount}, 失败: {$failCount}");
    }
}

// ========== 脚本入口 ==========

$env = isset($argv[1]) ? $argv[1] : 'pro';
$ceBillNos = isset($argv[2]) ? $argv[2] : '';
$dryRunStr = isset($argv[3]) ? $argv[3] : 'true';
$filterSkuIdsStr = isset($argv[4]) ? $argv[4] : '';

if (empty($ceBillNos)) {
    echo "用法: php FixSkuSupplierQuotePrice.php [环境] [CE单号] [dryRun] [SKU列表]\n";
    echo "  环境:    pro | test | uat (默认 pro)\n";
    echo "  CE单号:  必填，逗号分隔的 CE 单号列表\n";
    echo "  dryRun:  true(默认,只预览) | false(执行更新)\n";
    echo "  SKU列表: 可选，逗号分隔的 skuId，不传则处理 CE 单下所有 SKU\n";
    echo "\n示例:\n";
    echo "  # 预览：CE单下所有SKU\n";
    echo "  php FixSkuSupplierQuotePrice.php pro CE202504140132\n";
    echo "  # 预览：只处理指定SKU\n";
    echo "  php FixSkuSupplierQuotePrice.php pro CE202504140132 true a15092200ux0854,a21031700ux0093\n";
    echo "  # 执行更新：CE单下所有SKU\n";
    echo "  php FixSkuSupplierQuotePrice.php pro CE202504140132 false\n";
    echo "  # 执行更新：只处理指定SKU\n";
    echo "  php FixSkuSupplierQuotePrice.php pro CE202504140132 false a15092200ux0854,a21031700ux0093\n";
    exit(1);
}

$ceBillNoList = array_map('trim', explode(',', $ceBillNos));
$dryRun = $dryRunStr !== 'false';
$filterSkuIds = !empty($filterSkuIdsStr) ? array_map('trim', explode(',', $filterSkuIdsStr)) : array();

$script = new FixSkuSupplierQuotePrice($env);
$script->main($ceBillNoList, $filterSkuIds, $dryRun);
