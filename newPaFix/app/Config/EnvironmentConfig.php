<?php

declare(strict_types=1);

namespace App\Config;

use App\Infrastructure\Http\CurlService;

class EnvironmentConfig
{
    private CurlService $curlService;

    public function __construct(string $pageName = '')
    {
        $this->setPageEnvironment($pageName);
    }


    private function setPageEnvironment(string $pageName): self{
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
                $this->curlService = (new CurlService())->pro();
                break;
            case "paFixProductLine":
                $this->curlService = (new CurlService())->pro();
                break;
            case "addBrandFor":
                $this->curlService = (new CurlService())->pro();
                break;
            case "uploadOss":
                $this->curlService = (new CurlService())->test();
                break;
            case "getPmoData":
                $this->curlService = (new CurlService())->pro();
                break;
            case "registerIp":
                $this->curlService = (new CurlService())->test();
                break;
            case "fixFcuProductLine":
                $this->curlService = (new CurlService())->pro();
                break;
            case "textDiff":
                $this->curlService = (new CurlService())->local();
                break;
            case "consignmentQD":
                $this->curlService = (new CurlService())->pro();
                break;
            case "skuPhotoFix":
                $this->curlService = (new CurlService())->pro();
                break;
            default:
                $this->curlService = (new CurlService())->test();
                break;
        }
        return $this;
    }

    public function getCurlService(): CurlService{
        return $this->curlService;
    }
}