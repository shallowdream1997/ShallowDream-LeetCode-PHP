# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a **Product Operation Management System (POMS)** client tool for ux168.cn — a PHP-based internal operations platform that manages Amazon Sponsored Products (SP) advertising, consignment/recruitment, supply chain, and product SKU operations. It acts as a **client-side API consumer** — there is no direct database access; all data flows through backend microservices via HTTP.

## Architecture

**No framework** — this is a custom PHP application using manual `require_once` includes and Composer autoloading. The architecture is service-oriented:

```
php/
├── requiredfile/requiredfile.php   # Bootstrap — loads all core classes (include this first)
├── curl/CurlService.php            # Central HTTP client (fluent API for microservice calls)
├── utils/
│   ├── DataUtils.php               # Response data extraction (getPageList, getResultData, etc.)
│   ├── RequestUtils.php            # Pre-built API request wrappers per domain entity
│   ├── ExcelUtils.php              # Excel import/export (PHPExcel + PhpSpreadsheet)
│   └── ProductUtils.php            # Product-specific business helpers
├── controller/                     # HTTP request handlers (called from frontend pages)
├── shell/                          # CLI scripts and batch jobs
│   └── sp/                         # Amazon SP advertising sub-modules
│       ├── SpApi.php               # Core SP API integration (~75KB)
│       ├── campaign/               # Campaign management (budget, status, dedup)
│       ├── keyword/                # Keyword bid updates, enable/pause
│       ├── target/                 # Targeting bid updates, pause
│       ├── adgroup/                # Ad group operations
│       ├── negativeKeyword/        # Negative keyword management
│       ├── negativeTarget/         # Negative target management
│       ├── product/                # Product-level ad operations
│       ├── portfolios/             # Portfolio state checks
│       ├── seller/                 # Seller initialization
│       ├── common/                 # Shared SP operations (sync, enable/pause by ad group)
│       └── sync/                   # Data migration controllers
├── redis/RedisService.php          # Redis caching (hash + string ops)
├── constant/Constant.php           # Redis key constants and connection config
├── class/Logger.php                # File-based logger (php/log/)
├── job/                            # Cron job shell scripts (.sh) + PHP handlers
├── export/                         # Generated export files (Excel/CSV)
└── log/                            # Runtime logs (default/ and curl/ subdirs)
```

**Frontend** is in `template/` — standalone HTML pages using Vue 2 + Bootstrap 5 + Axios, each page is self-contained (no SPA build step).

## Key Patterns

### CurlService Fluent API

All API calls follow this chain pattern:
```php
$curlService = new CurlService();
$result = $curlService->pro()->s3015()->get("pa_products/queryPage", ["limit" => 100]);
$result = $curlService->test()->s3015()->post("pa_products", $data);
$result = $curlService->uat()->gateway()->getWayPost("/some/path", $data);
```

- **Environment**: `->test()`, `->uat()`, `->pro()`, `->local()`
- **Service**: `->s3015()`, `->s3009()`, `->s3023()`, `->s3044()`, `->s3047()`, `->s3013()`, `->s3010()`, `->s3016()`, `->phphk()`, `->phpali()`, `->ux168()`, `->gateway()`, `->smsSupport()`, `->aiCategoryApi()`
- **Method**: `->get()`, `->post()`, `->put()`, `->delete()`, `->getWayPost()`, `->getWayGet()`, `->getWayFormDataPost()`
- **Module**: `->getModule('pa')` / `->getModule('wms')` / etc. (sets the `module` header field)
- Old architecture endpoints prepend `/api` automatically; gateway (`getWay*`) methods do not

### Response Handling

CurlService returns `["httpCode" => int, "header" => string, "result" => array]`. Use DataUtils to extract:
- `DataUtils::getResultData($res)` — raw result array
- `DataUtils::getPageList($res)` — paginated list data (`result.data`)
- `DataUtils::getQueryList($res)` — query list data
- `DataUtils::getPageListInFirstData($res)` — first item from paginated list
- `DataUtils::checkArrFilesIsExist($arr, $field)` — check field existence

### EnvironmentConfig

`php/controller/EnvironmentConfig.php` maps page names to environments. Each page has a hardcoded environment (test/pro). When adding a new page, add its case to `setPageEnvironment()`.

### Adding a New Shell Script

1. Create PHP file in `php/shell/` (or `php/shell/sp/` subdirectory for SP-related)
2. Include bootstrap: `require_once(dirname(__FILE__) . "/../../php/requiredfile/requiredfile.php");`
3. Instantiate `CurlService`, set environment and service, make API calls
4. Use `DataUtils` for response parsing, `ExcelUtils` for Excel I/O
5. Run via CLI: `php php/shell/YourScript.php`

### Adding a New Frontend Page

1. Create HTML file in `template/fix/`
2. Include Vue 2 + Axios + Bootstrap 5 (copy from existing page)
3. Create corresponding controller in `php/controller/` if server-side logic needed
4. Add page name to `EnvironmentConfig::setPageEnvironment()` with its target environment

## Dependencies

- **PHP extensions**: `ext-redis`, `ext-json` (required)
- **phpoffice/phpspreadsheet** 1.29.2 — Excel operations
- **PHPExcel 1.8** — legacy Excel support (in `extends/`)
- **monolog/monolog** 1.27.1, **psr/log** 1.1.4, **logger-one/logger-one** ^1.0 — logging
- **Redis** — local instance on 127.0.0.1:6379

## Running Scripts

Shell scripts are executed directly via PHP CLI:
```bash
php php/shell/ScriptName.php
php php/shell/sp/campaign/SpUpdateCampaignBudgetController.php
```

Cron jobs are defined in `php/job/` as `.sh` wrappers around PHP scripts.

## Language

Code comments, documentation, and UI text are primarily in **Chinese (中文)**. Variable/function names are in English. PRD and API docs are in `plan/` directory (Chinese).
