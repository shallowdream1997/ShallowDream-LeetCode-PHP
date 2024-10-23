<?php

require dirname(__FILE__) . '/../../vendor/autoload.php';

require_once dirname(__FILE__) . '/../requiredfile/requiredChorm.php';

class EnvironmentConfig
{
    public $curlService;

    public function __construct($pageName = '')
    {
        $this->setPageEnvironment($pageName);
    }


    private function setPageEnvironment($pageName){
        switch ($pageName){
            case "paProductBrandSearch":
                $this->curlService = (new CurlService())->pro();
                break;
            case "pageSwitchConfig":
                $this->curlService = (new CurlService())->pro();
                break;
            case "fixTranslationManagements":
                $this->curlService = (new CurlService())->pro();
                break;
            case "fixCeMaterials":
                $this->curlService = (new CurlService())->pro();
                break;
            case "paFbaChannelSellerConfig":
                $this->curlService = (new CurlService())->pro();
                break;
            case "paSampleSku":
                $this->curlService = (new CurlService())->pro();
                break;
            case "paProductList":
                $this->curlService = (new CurlService())->test();
                break;
        }
        return $this;
    }
}