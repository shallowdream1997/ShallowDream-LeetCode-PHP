<?php
/**
 * 数据导出
 * 从 SyncCurlController 拆分
 * Class DataExport
 */

require_once dirname(__FILE__) . '/../../../php/bootstrap.php';

class DataExport extends CrudService
{
    public function __construct()
    {
        parent::__construct("sync/dataexport");
    }

    public function downloadPa()
        {
            $curlService = new CurlService();
            $curlService = $curlService->pro();

            $page = 1;
            $list = [];
            do {
                $this->log($page);
                $ss = DataUtils::getResultData($curlService->s3015()->get("sku_photography_progresss/queryPage", [
                    "photoOn_lte" => "2025-06-20T23:59:59Z",
                    "photoOn_gte" => "2025-05-01T00:00:00Z",
                    "limit" => 1000,
                    "page" => $page
                ]));
                if (count($ss['data']) == 0) {
                    break;
                }
                foreach ($ss['data'] as $info) {
                    $list[] = [
                        "batchName" => $info['batchName'],
    //                    "salesType" => $info['salesType'],
    //                    "tempSkuId" => $info['tempSkuId'],
                        "ceBillNo" => $info['ceBillNo'],
                        "createCeBillNoOn" => $info['createCeBillNoOn'],
                        "skuId" => $info['skuId'],
                        "status" => $info['status'],
                        "photoBy" => $info['photoBy'],
                        "photoOn" => $info['photoOn'],
                    ];
                }

                $page++;
            } while (true);

            if (count($list) > 0) {
                $excelUtils = new ExcelUtils();
                foreach (array_chunk($list, 10000) as $chunk) {
                    $filePath = $excelUtils->downloadXlsx([
                        "批次",
                        "CE单",
                        "CE创建日期",
                        "sku",
                        "状态",
                        "拍摄人",
                        "拍摄完成日期",
                    ], $chunk, "图片拍摄进度导出_" . date("YmdHis") . ".xlsx");

                }
            } else {
                $this->log("没有导出");
            }


        }

    public function downloadPaSkuPhotoProgress()
        {
            $curlService = new CurlService();
            $curlService = $curlService->pro();

            $page = 1;
            $list = [];
            do {
                $this->log($page);
                $ss = DataUtils::getResultData($curlService->s3015()->get("sku_photography_progresss/queryPage", [
                    "photoOn_lte" => "2025-06-20T23:59:59Z",
                    "photoOn_gte" => "2025-05-01T00:00:00Z",
                    "limit" => 1000,
                    "page" => $page
                ]));
                if (count($ss['data']) == 0) {
                    break;
                }
                foreach ($ss['data'] as $info) {
                    $list[] = [
                        "batchName" => $info['batchName'],
    //                    "salesType" => $info['salesType'],
    //                    "tempSkuId" => $info['tempSkuId'],
                        "ceBillNo" => $info['ceBillNo'],
                        "createCeBillNoOn" => $info['createCeBillNoOn'],
                        "skuId" => $info['skuId'],
                        "status" => $info['status'],
                        "photoBy" => $info['photoBy'],
                        "photoOn" => $info['photoOn'],
                    ];
                }

                $page++;
            } while (true);

            if (count($list) > 0) {
                $excelUtils = new ExcelUtils();
                foreach (array_chunk($list, 10000) as $chunk) {
                    $filePath = $excelUtils->downloadXlsx([
                        "批次",
                        "CE单",
                        "CE创建日期",
                        "sku",
                        "状态",
                        "拍摄人",
                        "拍摄完成日期",
                    ], $chunk, "图片拍摄进度导出_" . date("YmdHis") . ".xlsx");

                }
            } else {
                $this->log("没有导出");
            }


        }

    public function downloadPaSkuMaterialSP()
        {
            $curlService = new CurlService();
            $curlService = $curlService->pro();

            foreach ([
                         "CE202503",
                         "CE202504",
                         "CE202505",
                         "CE202506",
                     ] as $ceBillNoLike) {
                $ceBillNoMap = [];
                $this->log($ceBillNoLike);
                $page = 1;
                do {
                    $this->log($page);
                    $l = DataUtils::getPageDocList($curlService->s3044()->get("pa_ce_materials/queryPage", [
                        "ceBillNo_like" => $ceBillNoLike,
                        "limit" => 1000,
                        "page" => $page
                    ]));
                    if (count($l) == 0) {
                        break;
                    }
                    foreach ($l as $info) {
                        $ceBillNoMap[$info['ceBillNo']] = [
                            'developer' => $info['developer'],
                            'saleName' => $info['saleName']
                        ];
                    }

                    $page++;
                } while (true);


                if (count($ceBillNoMap) > 0) {
                    $keywordsList = [];
                    $cpAsinList = [];
                    $fitmentList = [];
                    foreach ($ceBillNoMap as $ceBillNo => $info) {
                        $this->log($ceBillNo . "查询资料呈现");
                        $ll = DataUtils::getPageDocList($curlService->s3044()->get("pa_sku_materials/queryPage", [
                            "ceBillNo" => $ceBillNo,
                            "limit" => 1500,
                            "page" => 1
                        ]));
                        if (count($ll) == 0) {
                            break;
                        }
                        foreach ($ll as $item) {
                            foreach ($item['keywords'] as $k) {
                                $keywordsList[] = [
                                    'ceBillNo' => $ceBillNo,
                                    'saleName' => $info['saleName'],
                                    'skuId' => $item['skuId'],
                                    'keywords' => $k
                                ];
                            }
                            foreach ($item['cpAsin'] as $cp) {
                                $cpAsinList[] = [
                                    'ceBillNo' => $ceBillNo,
                                    'saleName' => $info['saleName'],
                                    'skuId' => $item['skuId'],
                                    'cpAsin' => $cp
                                ];
                            }
                            foreach ($item['fitment'] as $fit) {
                                $fitmentList[] = [
                                    'ceBillNo' => $ceBillNo,
                                    'saleName' => $info['saleName'],
                                    'skuId' => $item['skuId'],
                                    'make' => $fit['make'],
                                    'model' => $fit['model'],
                                ];
                            }
                        }
                    }

                    if (count($keywordsList) > 0) {
                        $this->redis->hSet(REDIS_MATERIAL_KEY . "_keywords", $ceBillNoLike, json_encode($keywordsList, JSON_UNESCAPED_UNICODE));

                        $excelUtils = new ExcelUtils();
                        $filePath = $excelUtils->downloadXlsx([
                            "CE单号",
                            "产品运营",
                            "skuId",
                            "核心词",
                        ], $keywordsList, "{$ceBillNoLike}_核心词导出_" . date("YmdHis") . ".xlsx");
                        $this->log("导出核心词");
                    } else {
                        $this->log("没有核心词");
                    }

                    if (count($cpAsinList) > 0) {
                        $this->redis->hSet(REDIS_MATERIAL_KEY . "_cpasins", $ceBillNoLike, json_encode($cpAsinList, JSON_UNESCAPED_UNICODE));
                        $excelUtils = new ExcelUtils();
                        $filePath = $excelUtils->downloadXlsx([
                            "CE单号",
                            "产品运营",
                            "skuId",
                            "CP ASIN",
                        ], $cpAsinList, "{$ceBillNoLike}_CP_Asin导出_" . date("YmdHis") . ".xlsx");
                        $this->log("导出CP ASIN");
                    } else {
                        $this->log("没有CP ASIN");
                    }

                    if (count($fitmentList) > 0) {
                        $this->redis->hSet(REDIS_MATERIAL_KEY . "_fitment", $ceBillNoLike, json_encode($fitmentList, JSON_UNESCAPED_UNICODE));
                        $excelUtils = new ExcelUtils();
                        $filePath = $excelUtils->downloadXlsx([
                            "CE单号",
                            "产品运营",
                            "skuId",
                            "make",
                            "model",
                        ], $fitmentList, "{$ceBillNoLike}_fitment导出_" . date("YmdHis") . ".xlsx");
                        $this->log("导出fitment");
                    } else {
                        $this->log("没有fitment");
                    }

                } else {
                    $this->log("{$ceBillNoLike}没有数据");
                }


            }


        }

    public function downloadChannelAmazonCategory()
        {
            $curlService = new CurlService();
            $curlService = $curlService->pro();

            $page = 1;
            $list = [];
            do {
                $this->log($page);
                $ss = DataUtils::getResultData($curlService->s3015()->get("channel-amazon-categories/queryPage", [
                    "channel" => "amazon_jp",
                    "columns" => "channel,categoryId,categoryName,leafCategory,browsePathId,browsePathName",
                    "limit" => 1000,
                    "page" => $page
                ]));
                if (count($ss['data']) == 0) {
                    break;
                }
                foreach ($ss['data'] as $info) {
                    $list[] = [
                        "channel" => $info['channel'],
                        "categoryId" => $info['categoryId'],
                        "categoryName" => $info['categoryName'],
                        "leafCategory" => $info['leafCategory'],
                        "browsePathId" => $info['browsePathId'],
                        "browsePathName" => $info['browsePathName'],
                    ];
                }

                $page++;
            } while (true);

            if (count($list) > 0) {
                $excelUtils = new ExcelUtils();

                $filePath = $excelUtils->downloadXlsx([
                    "channel",
                    "categoryId",
                    "categoryName",
                    "leafCategory",
                    "browsePathId",
                    "browsePathName",
                ], $list, "JP_amazon_category_" . date("YmdHis") . ".xlsx");


            } else {
                $this->log("没有导出");
            }


        }

    public function exportAmazonUsAttribute()
        {
            $curlService = (new CurlService())->pro();

            $fileFitContent = (new ExcelUtils())->getXlsxData("../export/需导Amazon _us部分资料.xlsx");


            if (sizeof($fileFitContent) > 0) {
                $skuIdList = array_column($fileFitContent, "SKUID");
                $exportList = [];
                foreach (array_chunk($skuIdList, 200) as $skuIds) {

                    $list = DataUtils::getPageList($curlService->s3015()->get("product-skus/queryPage", [
                        "productId" => implode(",", $skuIds),
                        "columns" => "productId,title,description,weight_net,attribute",
                        "limit" => 200
                    ]));
                    foreach ($list as $info) {
                        $jinzhong = ($info['weight_net'] ?? 0) * 1000;

                        $data = [];
                        $data['skuId'] = $info['productId'];
                        $data['jinzhong'] = $jinzhong;
                        foreach ($info['attribute'] as $attr) {
                            if (in_array($attr['label'], [
                                    "title",
                                    "description",
                                    "Bullet_1",
                                    "Bullet_2",
                                    "Bullet_3",
                                    "Bullet_4",
                                    "Bullet_5",
                                    "item_length_width/length",
                                    "item_length_width/width",
                                    "item_height",
                                    "Color",
                                    "material"
                                ]) && $attr['channel'] == "amazon_us") {
                                $data[$attr['label']] = $attr['value'];
                            }
                        }
                        if (!isset($data['title']) || !$data['title']) {
                            $data['title'] = $info['title'];
                        }
                        if (!isset($data['description']) || !$data['description']) {
                            $data['description'] = $info['description'];
                        }
                        //字段重新排序
                        $lastData = [];
                        foreach (['skuId', 'title', 'description', 'Bullet_1', 'Bullet_2', 'Bullet_3', 'Bullet_4', 'Bullet_5', 'item_length_width/length', 'item_length_width/width', 'item_height', 'Color', 'material', 'jinzhong'] as $field) {
                            $lastData[$field] = $data[$field] ?? "";
                        }
                        $exportList[] = $lastData;
                        $this->redis->hSet("exportAmazonUsAttr", $data['skuId'], json_encode($lastData, JSON_UNESCAPED_UNICODE));
                    }

                }

            }

            if (sizeof($exportList) > 0) {
                $excelUtils = new ExcelUtils();
                $filePath = $excelUtils->downloadXlsx([
                    "SKUID",
                    "标题",
                    "描述",
                    "Bullet_1",
                    "Bullet_2",
                    "Bullet_3",
                    "Bullet_4",
                    "Bullet_5",
                    "item_length_width/length",
                    "item_length_width/width",
                    "item_height",
                    "Color",
                    "material",
                    "净重(g)"
                ], $exportList, "AmazonUS属性和净重_" . date("YmdHis") . ".xlsx");
            }


        }

    public function exportBusinessModules()
        {

            $curlService = (new CurlService())->pro();

            $list = DataUtils::getPageList($curlService->ux168()->get("business_modules/queryPage", [
                "vertical" => "PA",
                "activeStatus" => 1,
                "limit" => 1000,
                "page" => 1
            ]));
            if (sizeof($list) > 0) {

                $exportList = [];
                foreach ($list as $info) {
                    $exportList[] = [
                        "groupId" => $info['groupId'],
                        "supplierId" => $info['supplierId'],
                    ];
                }


                $excelUtils = new ExcelUtils();
                $filePath = $excelUtils->downloadXlsx([
                    "groupId",
                    "supplierId",
                ], $exportList, "test寄卖商PA_" . date("YmdHis") . ".xlsx");
            }


        }

    public function exportCEEEEEEEEEEEEEE()
        {
            $fileFitContent = (new ExcelUtils())->getXlsxData("../export/uploads/default/导出资呈数据_20250718113804.xlsx");
            if (sizeof($fileFitContent) > 0) {
                $ceBillNoMap = [];
                foreach ($fileFitContent as $info) {
                    $ceBillNoMap[$info['ceBillNo']][] = [
                        "skuId" => $info['skuId'],
                        "fitment" => json_decode($info['fitment'], true),
                        "keywords" => json_decode($info['keywords'], true),
                        "cpAsin" => json_decode($info['cpAsin'], true),
                    ];
                }

                if (count($ceBillNoMap) > 0) {

                    foreach ($ceBillNoMap as $ceBillNo => $list) {

                    }

                }

            }

        }

    public function week()
        {
            echo date("w", 1750806000);
        }

    public function getSkuPhotoProgress()
        {
            $c = new ProductSkuController();
            $curlService = (new CurlService())->pro();
            $fileFitContent = (new ExcelUtils())->getXlsxData("../export/未写入的图片拍摄.xlsx");
            if (sizeof($fileFitContent) > 0) {
                $skuIdList = array_column($fileFitContent, "productid");
                $preList = $c->getSkuPhotoProgress($skuIdList, "pro");
                $batch = [];
                foreach ($preList as $info) {
                    if ($info['isExist'] == "可修补") {
                        $batch[] = $info;
                    }
                }
                if (count($batch) > 0) {
                    $curlService->s3015()->post("sku_photography_progresss/createBatch", $batch);
                }
            }
        }

}

// === 入口 ===
$method = isset($argv[1]) ? $argv[1] : 'downloadPa';
$controller = new DataExport();
if (method_exists($controller, $method)) {
    $controller->$method();
} else {
    echo "可用方法：\n";
    $ref = new ReflectionClass($controller);
    foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
        $name = $m->getName();
        if (strpos($name, '__') !== 0 && strpos($name, 'common') !== 0 && $name !== 'getModule') {
            echo "  $name\n";
        }
    }
}
