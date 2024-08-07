<?php

class Constant
{
    const APP_NAME = "product_operation_client";
    const CLIENT_UNIQUE_ID = "product_operation_client_production";

    const UXCELLBO_APP_URL_MASTER = "http://master.app.uxcellbo.ux168.cn:8080/uxcellbo_app";
    const UXCELLBO_APP_URL_QUERY = "http://query.app.uxcellbo.ux168.cn:8080/uxcellbo_app";

    const INVENTORY_APP_URL_MASTER = "http://query.esb.ux168.cn/wsdl/inventory_app/appMaster";
    const INVENTORY_APP_URL_QUERY = "http://query.esb.ux168.cn/wsdl/inventory_app/appQuery";
    //add app.gateway server to save access logs, by Rocker, 2017-02-17
    //const INVENTORY_APP_URL_MASTER = "http://master.app.gateway.ux168.cn/inventory_app";
    //const INVENTORY_APP_URL_QUERY = "http://query.app.gateway.ux168.cn/inventory_app";

    const PRODUCTCENTER_APP_URL_MASTER = "http://master.app.productcenter.ux168.cn:8080/productcenter_app";
    const PRODUCTCENTER_APP_URL_QUERY = "http://query.app.productcenter.ux168.cn:8080/productcenter_app";

    const CETS_APP_URL_MASTER = "http://query.esb.ux168.cn/wsdl/cets_app/appMaster";
    const CETS_APP_URL_QUERY = "http://query.esb.ux168.cn/wsdl/cets_app/appQuery";
    //add app.gateway server to save access logs, by Rocker, 2017-02-17
    //const CETS_APP_URL_MASTER = "http://master.app.gateway.ux168.cn/cets_app";
    //const CETS_APP_URL_QUERY = "http://query.app.gateway.ux168.cn/cets_app";

    const EBAYBO_APP_URL_MASTER = "http://master.app.ebaybo.ux168.cn:8080/ebaybo_app";
    const EBAYBO_APP_URL_QUERY = "http://query.app.ebaybo.ux168.cn:8080/ebaybo_app";

    const UX168_APP_URL_MASTER = "http://query.esb.ux168.cn/wsdl/ux168_app/appMaster";
    const UX168_APP_URL_QUERY = "http://query.esb.ux168.cn/wsdl/ux168_app/appQuery";

    const LMS_APP_URL_QUERY = "http://query.app.lms.ux168.cn:8080/lms_app";
    const LMS_APP_URL_MASTER = "http://master.app.lms.ux168.cn:8080/lms_app";

    const ESM_APP_URL_MASTER = "http://master.app.esm.ux168.cn:8080/esm_app";
    const ESM_APP_URL_QUERY = "http://query.app.esm.ux168.cn:8080/esm_app";

    /*skip api gateway by Rocker, 2017-11-28
    const PRODUCT_OPERATION_REST_MASTER = 'master.api.gateway.ux168.cn/product_operation';
    const PRODUCT_OPERATION_REST_QUERY = 'master.api.gateway.ux168.cn/product_operation';
    const PRODUCT_OPERATION_URL_REST_KEY = 'HzN8rK1B6jK7LIa9Ex76jgUN76ij4e4H8rFzwqJh';

    const DATA_COMPOSITE_REST_MASTER = 'http://master.api.gateway.ux168.cn/data_composite';
    const DATA_COMPOSITE_REST_QUERY = 'http://master.api.gateway.ux168.cn/data_composite';
    const DATA_COMPOSITE_REST_URL_REST_KEY = 'VGiXi8hWk2QCa3tiFbDDb2rGMOE7iNc7eCeFjoCI';

   const POMS_LIST_MANAGEMENT_APP_URL_REST_MASTER = "https://master.api.gateway.ux168.cn/poms_listing";
   const POMS_LIST_MANAGEMENT_APP_URL_REST_QUERY = "https://master.api.gateway.ux168.cn/poms_listing";
   const POMS_LIST_MANAGEMENT_APP_URL_REST_KEY = "HzN8rK1B6jK7LIa9Ex76jgUN76ij4e4H8rFzwqJh";

   const PRODUCT_OPERATION_URL_ALI_PHP_REST_MASTER = "http://master.api.gateway.ux168.cn/poms_php";
   const PRODUCT_OPERATION_URL_ALI_PHP_REST_QUERY = "http://master.api.gateway.ux168.cn/poms_php";
   const PRODUCT_OPERATION_URL_ALI_PHP_REST_KEY = "HzN8rK1B6jK7LIa9Ex76jgUN76ij4e4H8rFzwqJh";

   const HK_POMS_PHP_URL_MASTER = "http://master.api.gateway.ux168.cn/hk_poms_php";

   const POMS_SOLD_APP_URL_REST_MASTER = "http://master.nodejs.poms.sold.ux168.cn:60023";
   const POMS_SOLD_APP_URL_REST_QUERY = "http://master.nodejs.poms.sold.ux168.cn:60023";
   const POMS_SOLD_APP_URL_REST_KEY = "";
   */

    const PRODUCT_OPERATION_APP_URL_MASTER = 'http://master.nodejs.poms.ux168.cn:60009';
    const PRODUCT_OPERATION_APP_URL_QUERY = 'http://master.nodejs.poms.ux168.cn:60009';
    const PRODUCT_OPERATION_APP_URL_KEY = '';

    const DATA_COMPOSITE_REST_MASTER = 'http://master.nodejs.datacomposite.ux168.cn:50001';
    const DATA_COMPOSITE_REST_QUERY = 'http://master.nodejs.datacomposite.ux168.cn:50001';
    const DATA_COMPOSITE_REST_URL_REST_KEY = '';

    const POMS_LIST_MANAGEMENT_APP_URL_REST_MASTER = "http://master.script.nodejs.poms.list.manage.ux168.cn:60015";
    const POMS_LIST_MANAGEMENT_APP_URL_REST_QUERY = "http://master.script.nodejs.poms.list.manage.ux168.cn:60015";
    const POMS_LIST_MANAGEMENT_APP_URL_REST_KEY = "";

    const PRODUCT_OPERATION_URL_ALI_PHP_REST_MASTER = "http://alivpc.slim.poms.ux168.cn:8000";
    const PRODUCT_OPERATION_URL_ALI_PHP_REST_QUERY = "http://alivpc.slim.poms.ux168.cn:8000";
    const PRODUCT_OPERATION_URL_ALI_PHP_REST_KEY = "";

    const HK_POMS_PHP_URL_MASTER = "http://hk.slim.poms.ux168.cn:8000";

    const POMS_SOLD_APP_URL_REST_MASTER = "http://master.nodejs.poms.sold.ux168.cn:60023";
    const POMS_SOLD_APP_URL_REST_QUERY = "http://master.nodejs.poms.sold.ux168.cn:60023";
    const POMS_SOLD_APP_URL_REST_KEY = "";

    const PRODUCT_OPERATION_LISTING_MANAGEMENT_REST_MASTER = 'http://master.script.nodejs.poms.list.manage.ux168.cn:60015';
    const PRODUCT_OPERATION_LISTING_MANAGEMENT_REST_QUERY = 'http://master.script.nodejs.poms.list.manage.ux168.cn:60015';
    const PRODUCT_OPERATION_LISTING_MANAGEMENT_URL_REST_KEY = '';

    const PRODUCTCENTER_APP_URL_REST_MASTER = "http://master.nodejs.productcenter.ux168.cn:60005";
    const PRODUCTCENTER_APP_URL_REST_QUERY = "http://master.nodejs.productcenter.ux168.cn:60005";
    const PRODUCTCENTER_APP_URL_REST_KEY = "";

    const PRODUCT_OPERATION_URL_HK_PHP_REST_MASTER = "http://hk.slim.poms.ux168.cn:8000";
    const PRODUCT_OPERATION_URL_HK_PHP_REST_QUERY = "http://hk.slim.poms.ux168.cn:8000";
    const PRODUCT_OPERATION_URL_HK_PHP_REST_KEY = "";

    const CHANNEL_APP_URL_REST_MASTER = "http://master.nodejs.channel.ux168.cn:60000";
    const CHANNEL_APP_URL_REST_QUERY = "http://master.nodejs.channel.ux168.cn:60000";
    const CHANNEL_APP_URL_REST_KEY = "";

    const POMS_QMS_APP_URL_REST_MASTER = "http://master.nodejs.poms.qms.ux168.cn:60016";
    const POMS_QMS_APP_URL_REST_QUERY = "http://master.nodejs.poms.qms.ux168.cn:60016";
    const POMS_QMS_APP_URL_REST_KEY = "";

    const NODE_STOCKMANAGE_APP_URL_MASTER = "http://master.nodejs.stockmanage.ux168.cn:60011";
    const NODE_STOCKMANAGE_APP_URL_QUERY = "http://master.nodejs.stockmanage.ux168.cn:60011";
    const NODE_STOCKMANAGE_APP_URL_KEY = "";

    //新channel
    const PRODUCT_OPERATION_CHANNEL_MASTER = 'http://master.nodejs.poms.channel.ux168.cn:60033';
    const PRODUCT_OPERATION_CHANNEL_QUERY = 'http://master.nodejs.poms.channel.ux168.cn:60033';
    const PRODUCT_OPERATION_CHANNEL_KEY = '';

    //log
    const PRODUCT_OPERATION_LOG_URL_REST_MASTER = "http://master.nodejs.poms.log.ux168.cn:60035";
    const PRODUCT_OPERATION_LOG_URL_REST_QUERY = "http://master.nodejs.poms.log.ux168.cn:60035";
    const PRODUCT_OPERATION_LOG_URL_REST_KEY = "";

    const CRAWLER_APP_URL_REST_MASTER = 'http://master.nodejs.crawler.ux168.cn:60034';
    const CRAWLER_APP_URL_REST_QUERY = 'http://master.nodejs.crawler.ux168.cn:60034';
    const CRAWLER_APP_URL_REST_KEY = '';

    const HG_ERP_REST_MASTER = "http://master.nodejs.hg-erp.ux168.cn:60039";
    const HG_ERP_REST_QUERY = "http://master.nodejs.hg-erp.ux168.cn:60039";
    const HG_ERP_URL_REST_KEY = "";

    const POMS_PL_APP_URL_REST_MASTER = "http://master.nodejs.poms.pl.ux168.cn:60028";
    const POMS_PL_APP_URL_REST_QUERY = "http://master.nodejs.poms.pl.ux168.cn:60028";
    const POMS_PL_APP_URL_REST_KEY = "";

    const CETS_LOGISTICS_COST_REST_MASTER = "http://master.nodejs.cets.logisticscost.ux168.cn:60040";
    const CETS_LOGISTICS_COST_REST_QUERY = "http://master.nodejs.cets.logisticscost.ux168.cn:60040";
    const CETS_LOGISTICS_COST_REST_KEY = "";

    //168node
    const UX168_APP_URL_REST_MASTER = "http://master.nodejs.168.ux168.cn:60013";
    const UX168_APP_URL_REST_QUERY = "http://master.nodejs.168.ux168.cn:60013";
    const UX168_APP_URL_REST_KEY = "";

    const DREAM_MALL_URL_REST_MASTER = 'https://dreammall-api.allegra-k.com';
    const DREAM_MALL_URL_REST_QUERY = 'https://dreammall-api.allegra-k.com';
    const DREAM_MALL_URL_REST_KEY = '';

    const POMS_NEST_JS_REST_URL_MASTER = 'http://master.nodejs.poms.list.nest.ux168.cn:60044';
    const POMS_NEST_JS_REST_URL_QUERY = 'http://master.nodejs.poms.list.nest.ux168.cn:60044';
    const POMS_NEST_JS_URL_REST_KEY = '';

    const KBS_APP_URL_MASTER = "http://query.app.kbs.ux168.cn:8080/kbs_app";
    const KBS_APP_URL_QUERY = "http://query.app.kbs.ux168.cn:8080/kbs_app";
    const KBS_APP_URL_KEY = "";

    const RMS_API_BIGDATA_MASTER = "http://api-bigdata.ux168.cn:5000";
    const RMS_API_BIGDATA_QUERY = "http://api-bigdata.ux168.cn:5000";
    const RMS_API_BIGDATA_KEY = "";

    //翻译
    const TRANSLATIONS_MASTER = "http://master.nodejs.translation.ux168.cn:60026";
    const TRANSLATIONS_QUERY = "http://master.nodejs.translation.ux168.cn:60026";
    const TRANSLATIONS_KEY = "";

    const CETS_APP_URL_REST_MASTER = 'http://master.nodejs.cets.ux168.cn:60010';
    const CETS_APP_URL_REST_QUERY = 'http://master.nodejs.cets.ux168.cn:60010';
    const CETS_APP_URL_REST_KEY = '';

    const POMS_BIG_DATA_NEST_JS_REST_URL_MASTER = 'http://master.nodejs.poms.big-data.nest.ux168.cn:60047';
    const POMS_BIG_DATA_NEST_JS_REST_URL_QUERY = 'http://master.nodejs.poms.big-data.nest.ux168.cn:60047';
    const POMS_BIG_DATA_NEST_JS_URL_REST_KEY = '';

    const PRODUCT_OPERATION_URL_HKVPC_PHP_REST_MASTER = "http://hk.alivpc.slim.poms.ux168.cn:8000";
    const PRODUCT_OPERATION_URL_HKVPC_PHP_REST_QUERY = "http://hk.alivpc.slim.poms.ux168.cn:8000";
    const PRODUCT_OPERATION_URL_HKVPC_PHP_REST_KEY = "";

    const FEATURE_JS_REST_URL_MASTER = 'http://feature-api.ux168.com';
    const FEATURE_JS_REST_URL_QUERY = 'http://feature-api.ux168.com';
    const FEATURE_JS_URL_REST_KEY = '';

    const PPMS_NODE_URL_REST_MASTER = 'http://master.nodejs.ppms.ux168.cn:60017';
    const PPMS_NODE_URL_REST_QUERY = 'http://master.nodejs.ppms.ux168.cn:60017';
    const PPMS_NODE_URL_REST_KEY = '';

    const POMS_LMS_APP_URL_REST_MASTER = "http://master.nodejs.lms.ux168.cn:60025";
    const POMS_LMS_APP_URL_REST_QUERY = "http://master.nodejs.lms.ux168.cn:60025";
    const POMS_LMS_APP_URL_REST_KEY = "";

    const CSAMS_NODE_JS_URL_MASTER = 'http://master.nodejs.csams.ux168.cn:60037';
    const CSAMS_NODE_JS_URL_QUERY = 'http://master.nodejs.csams.ux168.cn:60037';
    const CSAMS_NODE_JS_URL_KEY = '';

    const BIG_DATA_UX168_URL_MASTER = 'http://api-bigdata.ux168.cn:5000';
    const BIG_DATA_UX168_URL_QUERY = 'http://api-bigdata.ux168.cn:5000';
    const BIG_DATA_UX168_URL_KEY = '';

    //轻小
    const SMALL_AND_LIGHT_URL_REST_MASTER = 'http://hk.alivpc.slim.poms.ext-sellingpartner.ux168.cn:8010';
    const SMALL_AND_LIGHT_URL_REST_QUERY = 'http://hk.alivpc.slim.poms.ext-sellingpartner.ux168.cn:8010';
    const SMALL_AND_LIGHT_URL_REST_KEY = '';

    //api项目 amazon关联帐号服务器
    const POMS_EXTERNAL_SELLING_PARTNER_PHP_RESTFUL_MASTER = 'http://hk.alivpc.slim.poms.ext-sellingpartner.ux168.cn:8010';
    const POMS_EXTERNAL_SELLING_PARTNER_PHP_RESTFUL_QUERY = 'http://hk.alivpc.slim.poms.ext-sellingpartner.ux168.cn:8010';
    const POMS_EXTERNAL_SELLING_PARTNER_PHP_RESTFUL_KEY = '';

    //api项目 amazon第三方帐号服务器
    const POMS_SELLING_PARTNER_PHP_RESTFUL_MASTER = "http://hk.alivpc.slim.poms.sellingpartner.ux168.cn:8010";
    const POMS_SELLING_PARTNER_PHP_RESTFUL_QUERY = "http://hk.alivpc.slim.poms.sellingpartner.ux168.cn:8010";
    const POMS_SELLING_PARTNER_PHP_RESTFUL_KEY = "";

    //hg自营库存看板钉钉机器人
    const HG_FETCH_ALL_INVENTORY_DING_TALK_ROBOT_TOKEN = "ac94386e5bc4268ad180c1abc0143c6e722cab4b5cabf04c23abe9c3591b5025";

    const INCENTIVE_PL_NEW_URL_MASTER = 'http://app.incentive-pl.ux168.cn:8888/';
    const INCENTIVE_PL_NEW_URL_QUERY = 'http://app.incentive-pl.ux168.cn:8888/';
    const INCENTIVE_PL_NEW_URL_KEY = '';

    const BIG_DATA_URL_MASTER = "http://api-bigdata.ux168.cn:5101";
    const BIG_DATA_URL_QUERY = "http://api-bigdata.ux168.cn:5101";
    const BIG_DATA_URL_KEY = '';

    const POMS_ORIGINAL_NEST_JS_REST_URL_MASTER = 'http://poms-original-listing-nest.ux168.cn:60052';
    const POMS_ORIGINAL_NEST_JS_REST_URL_QUERY = 'http://poms-original-listing-nest.ux168.cn:60052';
    const POMS_ORIGINAL_NEST_JS_REST_URL_KEY = '';

    const DTC_BUBLEDON_URL_REST_MASTER = 'https://api.bubledon.com';
    const DTC_BUBLEDON_URL_REST_QUERY = 'https://api.bubledon.com';
    const DTC_BUBLEDON_URL_REST_KEY = '';

    const DTC_HARFINGTON_URL_REST_MASTER = 'https://adminapi.harfington.com';
    const DTC_HARFINGTON_URL_REST_QUERY = 'https://adminapi.harfington.com';
    const DTC_HARFINGTON_URL_REST_KEY = '';

    const POMS_EXTERNAL_NESTJS_MASTER = "http://poms-external-nest.ux168.cn:60053";
    const POMS_EXTERNAL_NESTJS_QUERY = "http://poms-external-nest.ux168.cn:60053";
    const POMS_EXTERNAL_NESTJS_KEY = "";

    //网关地址
    const UX168_GATEWAY_SERVICE_URL = "https://gateway.ux168.cn";
    //传给网关的项目名
    const UX168_GATEWAY_SERVICE_CALLER = "product-operation-client";
    //网关签名密钥
    const UX168_GATEWAY_SERVICE_SECRET = "VXa4YlhV97";

    // 大数据同步接口
    const BIG_DATA_GATEWAY_URL_MASTER = "https://bigdatagateway.ux168.cn";
    const BIG_DATA_GATEWAY_URL_QUERY = "https://bigdatagateway.ux168.cn";
    const BIG_DATA_GATEWAY_URL_SECRET = "3ngR7wF5FXKXZn1r29zNjX9NSFRUkVeB";


    public static function defineConstant()
    {
        define("COOKIE_AUTH", "ali_product_operation_client_auth");

        define("CNT_ROOT_FOR_CREATE_EMAIL", "/var/tmp/email/ux168_client");
        define("CNT_SENDING_DIR_FOR_CREATE_EMAIL", CNT_ROOT_FOR_CREATE_EMAIL . "/normal/sending");

        define("RS_HOST", "sj.ux168.cn");
        define("RS_PORT", 80);
        define("RS_ROOT", "/reportsystem");
        define('RS_direct_report', "/reportmessage.php");
        define('RS_REPORT_SYSTEM', "ux168");
        define("RS_reportpath", RS_ROOT . "/reportmessage.php");

        define('CNT_PRODUCT_IMAGES_ROOT', 'http://pic01.uxsight.com/photo_new');
        define('CNT_PRODUCT_IMAGES_ROOT_IN_UX168', 'http://www.ux168.com/photo_new_watermark');

        define('CNT_CACHE_TYPE', 'File2'); // File ,Memcache ,SCA  -- Cache type
        define('FILE_LOCATION', '/tmp/ux168_client_cache');

        define('NSQ_HOST', 'master.nsq.ux168.cn');
        define('NSQ_PORT', '4151');
        define('NSQ_ROOT', '/put?topic=');

        define('UX168_FTP_ADDRESS', 'ali.ftp.168image.ux168.cn');
        define('UX168_FTP_PORT', '21');
        define('UX168_FTP_USERNAME', 'ftpimage');
        define('UX168_FTP_PASSWORD', 'UF741image');

        define('IMPALA_HOST', 'gzdt.product.impala.ux168.cn');
        define('IMPALA_PORT', '25003');
        define('IMPALA_USER', 'incentive_pl');
        define('IMPALA_PASSWORD', 'incentive_pl123ewq');

        //POMS FTP服务器
        define('POMS_FTP_ADDRESS', 'poms-ftp.ux168.cn');
        define('POMS_FTP_USERNAME', 'pomsftp');
        define('POMS_FTP_PASSWORD', 'PPuu888');
        define('POMS_FTP_PORT', '21');

        //集群网段
        define('NETWORK_SEGMENT', 'cdh12');
        define('POMS_CLIENT_LOG_FILE_LOCATION', '/var/tmp/logs/product_operation_client');
    }
}
