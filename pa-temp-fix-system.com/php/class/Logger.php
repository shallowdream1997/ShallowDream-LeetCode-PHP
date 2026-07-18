<?php
//require_once("../../vendor/autoload.php");
//
//use Monolog\Logger;
//use Monolog\Handler\StreamHandler;
//use LoggerOne\Logger as loggerOne;
//use LoggerOne\Handler\FileHandler;
//use LoggerOne\Formatter\CommonFormatter;

/**
 * 日志类
 * Class MyLogger
 */
class MyLogger {

    private $logFile;
    public function __construct($logFile = ""){
        $logDefaultFile = dirname(__FILE__) . "/../../php/log/default/".date('Ymd').".log";
        // 支持 "/" 分隔的子目录路径，如 "sp/keyword" → php/log/sp/keyword_YYYYMMDD.log
        $this->logFile = !empty($logFile) ? dirname(__FILE__) . "/../../php/log/".$logFile."_".date('Ymd').".log" : $logDefaultFile;
        $this->ensureLogDirectory();
    }

    public function log($message) {
        file_put_contents($this->logFile, date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, FILE_APPEND);
        error_log($message);
    }

    public function log2($message){
        file_put_contents($this->logFile, date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, FILE_APPEND);
        error_log($message);
    }

    public function log3($message = ""){
        // 创建自定义的文件处理器和格式化器
        $handler = new FileHandler($this->logFile);
        $formatter = new CommonFormatter();

        // 获取 LoggerOne 的实例
        $logger = LoggerOne::getInstance();

        // 设置处理器和格式化器
        $logger->setHandler($handler);
        $logger->setFormatter($formatter);

        // 写入日志
        $logger->info($message);
    }

    private function ensureLogDirectory() {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        @chmod($logDir, 0777);

        $rootLogDir = dirname(__FILE__) . "/../../php/log";
        if (!is_dir($rootLogDir)) {
            mkdir($rootLogDir, 0777, true);
        }
        @chmod($rootLogDir, 0777);
    }
}
