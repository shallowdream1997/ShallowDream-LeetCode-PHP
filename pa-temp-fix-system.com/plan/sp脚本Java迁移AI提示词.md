# AI 提示词：将 PHP SP 广告脚本迁移为 Java MQ 消费程序

> 使用方法：将本文件内容（含 `==== 提示词开始 ====` 至 `==== 提示词结束 ====`）整体复制，粘贴到 Java 项目的 AI 编程助手中即可。

==== 提示词开始 ====

## 任务

在本 Java 项目中实现 Amazon SP（Sponsored Products）广告的三个操作：**投放（创建/启用）、关停（暂停）、调整 bid（出价）**。逻辑迁移自现有 PHP 脚本（`pa-temp-fix-system.com/php/shell/sp/` 下的 `SpCreateKeywordController`、`SpCreateTargetController`、`SpPausedKeywordController`、`SpUpdateKeywordBidController` 等），但**数据源不做 Excel 导入**——上游系统把任务写入数据库表并发送 MQ 消息，本程序通过**消费 MQ 消息逐个执行**操作。

## 一、系统背景

- 本系统是 POMS（Product Operation Management System）的一部分，纯 API 消费方，**不直接连 Amazon，也不直接连业务 MongoDB**，全部通过 HTTP 调内部微服务。
- 需要对接的三个下游服务：
  1. **Amazon 广告 API 代理**（原 PHP 中 `phphk()`，Java 中配置为 `amazon-api` 服务）：路径前缀 `amazon/ad/...`，所有路径含占位 `{sellerId}`。
  2. **MongoDB 同步服务**（原 PHP 中 `s3023()`，Java 中配置为 `mongo-sync` 服务）：路径前缀 `amazon_sp_xxx/...`，用于把 Amazon 侧变更同步到内部 MongoDB。
  3. **MQ 消息队列**（上游数据源）：数据库表由上游负责写入，我们只负责消费。

- 技术栈建议：Spring Boot 3.x + Spring Cloud OpenFeign（或 RestTemplate）+ Jackson + Caffeine 本地缓存（替代原 PHP 的 Redis 缓存，单机部署无需分布式缓存）。若团队已有 MQ 中间件（RocketMQ/RabbitMQ/Kafka），沿用现有规范。

## 二、MQ 消息设计

### 2.1 统一消息体

```json
{
  "messageId": "UUID（消费幂等去重用）",
  "type": "LAUNCH | PAUSE | UPDATE_BID | UPDATE_BUDGET",
  "entityType": "KEYWORD | TARGET | NEGATIVE_KEYWORD | NEGATIVE_TARGET | PRODUCT | AD_GROUP | CAMPAIGN",
  "sellerId": "amazon",
  "channel": "amazon_us",
  "payload": { },
  "traceId": "链路追踪号"
}
```

### 2.2 各类型 payload

**LAUNCH（投放/创建并启用）**

```json
// entityType=KEYWORD
{ "campaignId": "123456", "adGroupId": "654321", "keywordText": "phone case",
  "matchType": "broad | phrase | exact", "bid": 0.42 }
// entityType=TARGET
{ "campaignId": "123456", "adGroupId": "654321", "asin": "B0XXXXXX", "bid": 0.42 }
// entityType=NEGATIVE_KEYWORD
{ "campaignId": "123456", "adGroupId": "654321", "keywordText": "iphone",
  "matchType": "negativeExact | negativePhrase" }
// entityType=NEGATIVE_TARGET
{ "campaignId": "123456", "adGroupId": "654321", "asin": "B0XXXXXX" }
// entityType=PRODUCT（商品广告，仅补充场景）
{ "campaignId": "123456", "adGroupId": "654321", "skuId": "...", "scuId": "..." }
```

**PAUSE（关停）**

```json
// entityType=KEYWORD/TARGET：只给一个 id，类型可能不匹配，需按"keyword优先、target降级"处理
{ "entityId": "987654321" }
// entityType=PRODUCT
{ "entityId": "adId", "entityKey": "adId" }
```

**UPDATE_BID（调整出价）**

```json
{ "entityId": "987654321", "newBid": 0.55 }
```

**UPDATE_BUDGET（调整 campaign 预算，可选范围）**

```json
{ "campaignId": "123456", "dailyBudget": 50.00 }
```

### 2.3 消费约定

- **一条消息 = 一个操作**，串行执行完整流程（查状态 → 调用 Amazon → 同步 Mongo → 结果记录），处理完成才算消费成功。
- **消费成功后记录结果**（落库：成功/失败/跳过及原因），供运营核对（替代原脚本导出的 Excel）。
- **幂等**：消费前按 `messageId` 去重（Redis/DB 唯一键）；业务本身也要自然幂等（见各操作"跳过条件"）。
- **失败重试**：消费异常按 `10s → 30s → 1min` 指数退避重试，超过上限进**死信队列**（DLQ），由人工/定时任务补偿。区分"可重试异常"（网络、下游 5xx）与"不可重试异常"（参数错误、实体不存在、降级耗尽——直接记失败，不重试）。
- 同一消息的延迟重试期间，**不得重复消费**（需要分布式锁或消费组内串行投递；单机部署可用 Caffeine 记录进行中消息）。

## 三、公共规则（所有操作必须遵守）

### 3.1 sellerId / channel 转换

- Mongo 侧存 `channel = "amazon_us"`，Amazon API 路径用 `sellerId = "amazon"`。
- 消息中若带 `sellerId`（amazon 格式）直接用于 API 路径；用于 Mongo 查询时转 `amazon_us`。
- 若消息只带 channel 不带 sellerId，需先查 Mongo 反查 sellerId（如按 campaignId 查 `amazon_sp_campaigns/queryPage` 取 sellerId）。

### 3.2 批量接口限制

| 场景 | 上限 |
|---|---|
| Amazon 查询（ID 过滤） | 每批 100 个 |
| Amazon 更新 | 每批 200 个 |
| Amazon 创建 | 每批 1000 个 |

单消息单实体场景通常不涉及批量；但在"查询已有实体"（投放的去重查询）时，一个 adGroup 下的实体数量可能较大，注意分页/分批。

### 3.3 批量更新响应判定

所有批量更新接口（putKeywords/putTargets 等）响应形如 `{success: [id...], error: [id...]}`。判定依据是响应列表内每条 `code == "SUCCESS"`（成功）或 `code == "FAILURE"`（失败，带 message）。

### 3.4 ID 规范

- 所有 ID 按字符串处理，**禁止用数值类型**（防止精度丢失/科学计数法），Java 中统一 `String`。
- 非空校验：空串/`"0"` 视为无效。

## 四、操作一：投放（LAUNCH）

### 4.1 通用流程（keyword / target / 否定词 / 否定 target 共用）

```
1. 解析 adGroup 信息 → 得到 campaignId、defaultBid
   ├─ 先查 Mongo：GET mongo-sync/amazon_sp_adgroups/queryPage
   │   参数: {channel: amazon_us, adGroupId, limit: 1}  取第一条
   └─ Mongo 查不到 → 查 Amazon：GET amazon-api/amazon/ad/adGroups/getAdGroupsExtend/{sellerId}
       参数: {adGroupIdFilter: [adGroupId]}  取 campaignId、defaultBid
       Mongo 也查不到 → 直接记失败（ad group not found），结束

2. 确定 campaignId（优先级从高到低）
   ├─ 消息 payload 中的 campaignId（非空非"0"）
   └─ adGroupInfo.campaignId
   都没有 → 记失败，结束

3. 查询 Amazon 该 adGroup 下已有实体（全部状态，不过滤 state）→ 用于去重
   ├─ keyword:        POST amazon-api/amazon/ad/keywords/getKeywordsExtended/{sellerId}
   │                  参数: {campaignIdFilter:[campaignId], adGroupIdFilter:[adGroupId],
   │                        matchTypeFilter:[matchType], keywordTextFilter:[keywordText]}
   ├─ target:         GET  amazon-api/amazon/ad/productTargeting/getTargets/{sellerId}
   │                  参数: {campaignIdFilter:[campaignId], adGroupIdFilter:[adGroupId]}
   ├─ negativeKeyword: GET amazon-api/amazon/ad/negativeKeywords/getNegativeKeywordsExtend/{sellerId}
   └─ negativeTarget:  GET amazon-api/amazon/ad/negativeProductTargeting/getNegativeTargets/{sellerId}

4. 去重判断键
   ├─ keyword / negativeKeyword: matchType + "_" + keywordText
   └─ target / negativeTarget:  表达式 expression[0].value（= asin）

5. 三种结果分支
   ├─ 已存在且 state == "enabled"          → 跳过（记"已存在，跳过"，消费成功）
   ├─ 已存在且 state != "enabled"          → 更新为 enabled（PUT 单条，见下方）
   └─ 不存在                              → 创建（POST，state=enabled，见下方）
       创建/更新成功 → 步骤 6 同步 Mongo
       创建/更新失败 → 记失败（可重试则抛异常走 MQ 重试）

6. Mongo 同步（成功后立即执行）
   ├─ keyword:         POST mongo-sync/amazon_sp_keywords
   │    {companyId:"CR201706060001", channel:"amazon_us", keywordId, campaignId, adGroupId,
   │     keywordText, keywordType:"expansion", state:"enabled", matchType, bid}
   ├─ target:          POST mongo-sync/amazon_sp_targets
   │    {companyId:"CR201706060001", targetId, channel:"amazon_us", type:"asinSameAs",
   │     value:asin, expressionType:"manual", state:"enabled", bid, targetName:asin}
   ├─ negativeKeyword: POST mongo-sync/amazon_sp_negativeKeywords
   └─ negativeTarget:  POST mongo-sync/amazon_sp_negative_targets   （value 存小写 asin）
   Mongo 同步失败：按可重试异常处理（等 MQ 重试；重试时步骤 3/4 的幂等判断会自然跳过已创建的实体）
```

### 4.2 各实体创建/更新 payload

```
keyword 创建: POST amazon/ad/keywords/postKeywords/{sellerId}
  [{campaignId, adGroupId, keywordText, matchType, state:"enabled", bid}]
keyword 更新: PUT  amazon/ad/keywords/putKeywords/{sellerId}
  [{keywordId, state:"enabled"}]

target 创建: POST amazon/ad/productTargeting/postTargets/{sellerId}
  [{campaignId, adGroupId, state:"enabled", expressionType:"manual", bid,
    expression:[{value:asin, type:"asinSameAs"}],
    resolvedExpression:[{value:asin, type:"asinSameAs"}]}]
target 更新: PUT  amazon/ad/productTargeting/putTargets/{sellerId}
  [{targetId, state:"enabled"}]

negativeKeyword 创建: POST amazon/ad/negativeKeywords/postNegativeKeywords/{sellerId}
  [{campaignId, adGroupId, keywordText, matchType, state:"enabled"}]
negativeKeyword 更新: PUT amazon/ad/negativeKeywords/putNegativeKeywords/{sellerId}
  [{keywordId, state:"enabled"}]

negativeTarget 创建: POST amazon/ad/negativeProductTargeting/postNegativeTargets/{sellerId}
  [{campaignId, adGroupId, state:"enabled", expressionType:"manual",
    expression:[{value:asin, type:"asinSameAs"}],
    resolvedExpression:[{value:asin, type:"asinSameAs"}]}]
negativeTarget 更新: PUT amazon/ad/negativeProductTargeting/putNegativeTargets/{sellerId}
  [{targetId, state:"enabled"}]
```

### 4.3 特殊规则

- **bid 为空**：keyword/target 的 bid 为空时，回退用 adGroup 的 defaultBid；adGroup 也没有 → 记失败。
- **投放 PRODUCT**（如有需求）：需先解析 sku 资料（原 PHP 查 `pa_sku_materials`）得到 asin/sellerSku，再创建 product ad（POST `productAds/postProductAds/{sellerId}`，payload `{campaignId, adGroupId, state:"enabled", asin, sku: sellerSku}`），成功后同步 `amazon_sp_products`。
- 投放入口只处理"本消息声明的实体类型"，不做跨类型降级（降级是关停/调整 bid 的规则，见下）。

## 五、操作二：关停（PAUSE）

### 5.1 核心：keyword → target 自动降级

消息只给 `entityId`，可能是 keywordId 也可能是 targetId，**无法预知类型**，按以下顺序处理：

```
1. 第一步：把 entityId 当 keyword 关停
   PUT amazon/ad/keywords/putKeywords/{sellerId}  [{keywordId: entityId, state:"paused"}]
   ├─ 成功 → 同步 Mongo（见 5.2）→ 完成
   └─ 失败（含"实体不存在"类错误）→ 进入第二步

2. 第二步：把 entityId 当 target 关停
   PUT amazon/ad/productTargeting/putTargets/{sellerId}  [{targetId: entityId, state:"paused"}]
   ├─ 成功 → 同步 Mongo → 完成
   └─ 失败 → 记失败（"keyword 与 target 均关停失败"），按可重试处理

3. 说明：Amazon 更新接口对"不存在的 id"返回失败，因此第一步失败即证明它大概率是 target 或不存在，
   无需预查询；两步都失败才需要人工排查。
```

### 5.2 Mongo 同步（成功后立即执行）

先取 mongo `_id`（见 5.3），再调：

```
keyword 关停成功: POST mongo-sync/amazon_sp_keywords/updateBiddableKeywords
  {id: <_id>, isPassNotification: "false", from: "system",
   updateParams: {keywordId, state:"paused", modifiedBy:"system",
                  modifiedOn:<当前时间ISO>, status:"2", messages:"system"}}

target 关停成功: POST mongo-sync/amazon_sp_targets/updateBiddableTargets
  {id: <_id>, isPassNotification: "false", from: "system",
   updateParams: {targetId, state:"paused", modifiedBy:"system",
                  modifiedOn:<当前时间ISO>, status:"2", messages:"system"}}
```

### 5.3 Amazon ID → Mongo `_id` 映射（本地缓存）

```
1. 优先查本地缓存（Caffeine，缓存 key 形如 "spKeyword:{sellerId}" / "spTarget:{sellerId}"
   → Map<AmazonId, MongoId>，TTL 建议 30 分钟，容量 10 万）
2. 未命中 → 批量查 Mongo 回填缓存：
   GET mongo-sync/amazon_sp_keywords/queryPage  {channel: amazon_us, keywordId_in: [ids], limit: 200}
   GET mongo-sync/amazon_sp_targets/queryPage   {channel: amazon_us, targetId_in: [ids], limit: 200}
3. Mongo 中也查不到 → 记 warn 日志后继续（Amazon 侧已处理成功，同步失败不阻塞业务，
   但需在结果记录中标注 "mongo 未同步（查不到 _id）"）
```

### 5.4 其他实体关停

```
PRODUCT:  PUT amazon/ad/productAds/putProductAds/{sellerId}  [{adId: entityId, state:"paused"}]
          同步: POST mongo-sync/amazon_sp_products/updateProductAds
                updateParams: {adId, state:"paused", ...}（同统一结构）
AD_GROUP: PUT amazon/ad/adGroups/putAdGroups/{sellerId}  [{campaignId, adGroupId, state:"paused"}]
          同步: POST mongo-sync/amazon_sp_adgroups/updateAdGroups
NEGATIVE_KEYWORD: PUT amazon/ad/negativeKeywords/putNegativeKeywords/{sellerId}
          同步: POST mongo-sync/amazon_sp_negativeKeywords/updateNegativeKeywords
NEGATIVE_TARGET:  PUT amazon/ad/negativeProductTargeting/putNegativeTargets/{sellerId}
          同步: POST mongo-sync/amazon_sp_negative_targets/updateBiddableNegativeTargets
（否定词的 PAUSE 消息 entityType 必须显式声明，不做 keyword/target 降级）
```

## 六、操作三：调整 bid（UPDATE_BID）

### 6.1 核心流程（同样 keyword → target 降级）

```
1. 第一步：把 entityId 当 keyword，先查询当前 state 与 bid
   POST amazon/ad/keywords/getKeywordsExtended/{sellerId}  {keywordIdFilter: [entityId]}
   → 得到 {keywordId: {state, bid}}；查询不到该 id → notFound，进入第二步

   对查询到的 id：
   ├─ state == "archived"       → 跳过（archived 为终态不可修改，记"跳过：archived"，消费成功）
   ├─ bid 相同                  → 跳过（见 6.2 比较规则，记"跳过：bid 未变化"）
   └─ 否则                     → 执行更新：
        PUT amazon/ad/keywords/putKeywords/{sellerId}  [{keywordId: entityId, bid: newBid}]
        成功 → 同步 Mongo（6.3）→ 完成
        失败 → 进入第二步（作为 target 再试）

2. 第二步：把 entityId 当 target（notFound 或 keyword 更新失败都进来）
   GET amazon/ad/productTargeting/getTargets/{sellerId}  {targetIdFilter: [entityId]}
   → 同样的三个判断：查不到 → 记失败结束；archived → 跳过；bid 相同 → 跳过；
     否则 PUT amazon/ad/productTargeting/putTargets/{sellerId}  [{targetId: entityId, bid: newBid}]
     成功 → 同步 Mongo → 完成；失败 → 记失败

3. 两步都失败 → 记失败（"keyword 与 target 均调整失败"）
```

### 6.2 bid 比较规则

- 用 `BigDecimal` 比较：`newBid.setScale(2, RoundingMode.HALF_UP)` vs `currentBid.setScale(2, RoundingMode.HALF_UP)`，相等即跳过。
- **archived 状态不算失败**，只跳过并记录。
- 传入的 newBid 需先归一化（转 BigDecimal，去除末尾 0）。

### 6.3 Mongo 同步

```
keyword 调整成功: POST mongo-sync/amazon_sp_keywords/updateBiddableKeywords
  {id: <_id>, isPassNotification: "false", from: "system",
   updateParams: {keywordId, bid: newBid, modifiedBy:"system",
                  modifiedOn:<当前时间ISO>, status:"2", messages:"system"}}
  ← 注意：state 不传（只更新 bid）

target 调整成功: POST mongo-sync/amazon_sp_targets/updateBiddableTargets
  {id: <_id>, ..., updateParams: {targetId, bid: newBid, ...}}   ← 同上，state 不传
```

（_id 获取与 5.3 相同：Caffeine 缓存 → queryPage 补查 → 查不到记日志。）

### 6.4 Campaign 预算（UPDATE_BUDGET，可选实现）

```
1. 按 campaignId 查 Mongo 补全 sellerId：GET mongo-sync/amazon_sp_campaigns/queryPage {campaignId_in:[id]}
2. PUT amazon/ad/campaigns/putCampaigns/{sellerId}  [{campaignId, dailyBudget}]
3. 成功 → POST mongo-sync/amazon_sp_campaigns/updateCampaigns
   updateParams: {dailyBudget, modifiedBy:"system", modifiedOn, status:"2", messages:"system"}
```

## 七、代码结构要求

```
com.xxx.xxx.sp
├── mq/
│   ├── SpMessageConsumer.java          # MQ 监听入口：校验消息 → messageId 幂等 → 路由到对应 Service
│   └── SpMessage.java                  # 统一消息体（type/entityType/payload/sellerId/channel）
├── service/
│   ├── SpLaunchService.java            # 投放：按 entityType 路由到 4 个投放器
│   ├── SpPauseService.java             # 关停：keyword→target 降级
│   ├── SpUpdateBidService.java         # 调整 bid：keyword→target 降级
│   └── SpUpdateBudgetService.java      # 预算调整（可选）
├── client/
│   ├── AmazonAdClient.java             # Amazon API 客户端（Feign/RestTemplate），路径含 {sellerId}
│   ├── MongoSyncClient.java            # Mongo 同步客户端
│   ├── AmazonKeywordApi.java           # 以下按实体拆分，或统一放入 AmazonAdClient，二选一
│   ├── AmazonTargetApi.java
│   ├── AmazonProductApi.java
│   ├── MongoKeywordSyncApi.java
│   └── MongoTargetSyncApi.java
├── strategy/
│   ├── LaunchStrategy.java             # 投放策略接口（按 entityType 注册实现）
│   ├── KeywordLaunchStrategy.java      # / TargetLaunchStrategy / NegativeKeywordLaunchStrategy / NegativeTargetLaunchStrategy
│   └── PauseDegradationStrategy.java   # 降级策略接口（关停/bid 共用）：primaryApi → fallbackApi
├── cache/
│   └── SpIdMappingCache.java           # Caffeine 封装：spKeyword_{sellerId} / spTarget_{sellerId}
├── exception/
│   ├── AmazonApiException.java         # 区分 client(4xx, 不可重试) / server(5xx, 可重试) / network(可重试)
│   ├── MongoSyncException.java         # Mongo 同步失败（可重试，但注意幂等）
│   ├── EntityNotFoundException.java    # 实体不存在（不可重试，记失败）
│   └── DegradationExhaustedException.java # 降级链走完仍失败（可重试）
├── result/
│   └── SpOperationResult.java          # 操作结果：SUCCESS / SKIPPED / FAILED + 原因 + 明细
└── dao/
    └── SpOperationRecordDao.java       # 结果落库（替代原脚本导出的 Excel，供运营核对/重推）
```

### 关键设计点

1. **降级策略抽象**：关停与调整 bid 的"keyword → target"是同构的（先主 API 后备用 API），抽成 `PauseDegradationStrategy`/`BidDegradationStrategy` 接口，两个 Service 复用，避免复制粘贴。
2. **查询与更新分离**：调整 bid 必须先查（拿 state/bid）再决定是否更新；关停不需要预查询，直接更新、失败自然进入降级。
3. **异常分层**：`AmazonApiException` 区分 4xx（参数/不存在 → 直接记失败）与 5xx/网络（→ MQ 重试）；`EntityNotFoundException` 在降级链的**最后一步**才视为不可重试失败。
4. **幂等要点**：
   - 消费前按 messageId 去重；
   - LAUNCH 重试时靠"查询已有实体"判断：已 enabled 跳过，已存在非 enabled 更新为 enabled；
   - PAUSE 重试是天然幂等（paused 再 paused 无副作用）；
   - UPDATE_BID 重试靠 bid 比较跳过（已改成目标价则不再调）。
5. **同步与异步边界**：Amazon 操作成功后**必须**同步 Mongo，Mongo 失败不抛给 MQ 重试整个消息（否则 Amazon 侧重复执行），而是记录"mongo 同步失败"并抛出**仅回滚消息不重试**的处理——这里需要与产品确认策略：**推荐方案是 Mongo 同步失败也抛异常重试**，靠业务幂等兜底（Amazon 侧重复执行被跳过条件拦住）；如果必须避免 Amazon 重复调用，则 Mongo 失败只记日志+落补偿表，由补偿任务重试同步。
6. **结果记录**：每个操作最终都写 `sp_operation_record`（messageId、type、entityId、结果、原因、请求/响应摘要、耗时），替代 PHP 的失败 Excel，运营据此核对与重推。

## 八、验收标准

1. 三个操作（投放/关停/调整 bid）各能独立消费消息执行，结果落库可查。
2. 关停与调整 bid 的 keyword→target 降级在以下场景验证：
   - id 是 keywordId → 第一步成功，Mongo 同步 keyword；
   - id 是 targetId → 第一步失败、第二步成功，Mongo 同步 target；
   - id 都不存在 → 记失败，进 DLQ（或记失败表）。
3. 调整 bid 的跳过条件：archived 跳过、bid 相同跳过，均不计失败。
4. 投放幂等：同一条 LAUNCH 消息重发不产生重复实体（已 enabled 跳过）。
5. 消息重试：模拟下游 5xx → 按 10s/30s/1min 退避重试，超限进 DLQ；模拟 4xx → 不重试直接记失败。
6. messageId 重复消费被拦截。
7. 空 bid 投放回退 adGroup defaultBid 的链路可用。
8. 代码结构符合第七节设计，异常分层、幂等、结果记录完整。

## 九、实现顺序建议

1. **基础层**：SpMessage 结构、AmazonAdClient/MongoSyncClient、SpIdMappingCache、异常体系、操作结果与记录表。
2. **PAUSE**（最简，验证降级框架）：SpPauseService + keyword→target 降级 + Mongo 同步 + 缓存。
3. **UPDATE_BID**（验证查询→判断→更新模式）：查询 state/bid、跳过条件、降级复用。
4. **LAUNCH**（最复杂）：adGroup 解析、去重查询、三分支、四个实体类型。
5. **UPDATE_BUDGET**（可选）：campaign 预算。
6. **运营配套**：重推/补偿工具、核对查询页、DLQ 处理脚本。

==== 提示词结束 ====
