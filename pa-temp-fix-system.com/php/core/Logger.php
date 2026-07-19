<?php
/**
 * 日志类（改进版）
 * 支持日志级别：debug / info / warn / error
 * 向后兼容：log() / log2() 仍可用，内部委托到 info()
 * Class MyLogger
 */
class MyLogger
{
    const DEBUG = 0;
    const INFO = 1;
    const WARN = 2;
    const ERROR = 3;

    private $logFile;
    private $minLevel;

    public function __construct($logFile = "", $minLevel = null)
    {
        $logDefaultFile = dirname(__FILE__) . "/../log/default/" . date('Ymd') . ".log";
        // 支持 "/" 分隔的子目录路径，如 "sp/keyword" → php/log/sp/keyword_YYYYMMDD.log
        $this->logFile = !empty($logFile) ? dirname(__FILE__) . "/../log/" . $logFile . "_" . date('Ymd') . ".log" : $logDefaultFile;
        $this->minLevel = $minLevel !== null ? $minLevel : self::INFO;
        $this->ensureLogDirectory();
    }

    /**
     * 向后兼容：原 log() 方法
     * @param string $message
     */
    public function log($message)
    {
        $this->info($message);
    }

    /**
     * 向后兼容：原 log2() 方法
     * @param string $message
     */
    public function log2($message)
    {
        $this->info($message);
    }

    /**
     * DEBUG 级别日志
     * @param string $message
     */
    public function debug($message)
    {
        $this->write(self::DEBUG, 'DEBUG', $message);
    }

    /**
     * INFO 级别日志
     * @param string $message
     */
    public function info($message)
    {
        $this->write(self::INFO, 'INFO', $message);
    }

    /**
     * WARN 级别日志
     * @param string $message
     */
    public function warn($message)
    {
        $this->write(self::WARN, 'WARN', $message);
    }

    /**
     * ERROR 级别日志
     * @param string $message
     */
    public function error($message)
    {
        $this->write(self::ERROR, 'ERROR', $message);
    }

    /**
     * 写入日志
     * @param int $level
     * @param string $label
     * @param string $message
     */
    private function write($level, $label, $message)
    {
        if ($level < $this->minLevel) {
            return;
        }
        $line = date('Y-m-d H:i:s') . " [{$label}] {$message}" . PHP_EOL;
        file_put_contents($this->logFile, $line, FILE_APPEND);
        if ($level >= self::WARN) {
            error_log($message);
        }
    }

    /**
     * 确保日志目录存在
     */
    private function ensureLogDirectory()
    {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        @chmod($logDir, 0777);
    }
}
