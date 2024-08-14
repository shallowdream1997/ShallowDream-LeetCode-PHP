<?php
require_once("../requiredfile/requiredfile.php");

class OptionConfigController
{
    private $log;
    private $requestUtils;

    public function __construct()
    {
        $this->log = new MyLogger("option_val_list");
        $this->requestUtils = new RequestUtils("test");
    }

    /**
     * 同步生产环境option-val-list -> sit环境
     */
    public function syncOptionValListInfoToTest(){
        $optionNameList = [
            "campaign_salesman_index"
        ];

        $proRequestUtils = new RequestUtils("pro");
        foreach ($optionNameList as $optionName){
            $proInfo = $proRequestUtils->getOptionValListByName($optionName);
            if (!empty($proInfo)){
                $testInfo = $this->requestUtils->getOptionValListByName($optionName);
                if (!empty($testInfo)){
                    //
                    $this->requestUtils->deleteOptionValListInfo($testInfo);
                    $this->log("delete test {$optionName}");
                    $this->requestUtils->createOptionValListInfo($proInfo);
                    $this->log("create test {$optionName}");
                }else{
                    $this->requestUtils->createOptionValListInfo($proInfo);
                    $this->log("create test {$optionName}");
                }
            }
        }


    }

    public function getSystemUserNameList(){
        $return = $this->requestUtils->returnEmployeeByCompanySequenceId();
        foreach ($return as $userName => $info){
            $this->log($userName.'  '.$info['cName']);
        }
    }
    private function log(string $string = "")
    {
        $this->log->log2($string);
    }
}

$p = new OptionConfigController();
//$p->syncOptionValListInfoToTest();
$p->getSystemUserNameList();