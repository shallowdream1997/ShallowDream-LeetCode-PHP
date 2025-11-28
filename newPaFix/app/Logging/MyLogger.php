<?php

declare(strict_types=1);

namespace App\Logging;

use LoggerOne\Formatter\CommonFormatter;
use LoggerOne\Handler\FileHandler;
use LoggerOne\Logger as LoggerOne;

/**
 * 简易日志工具，所有业务日志统一输出到 storage/logs
 */
class MyLogger
{
    private string $logFile;

    public function __construct(string $logFile = '')
    {
        $date = date('Ymd');
        $defaultDir = STORAGE_PATH . '/logs/default';

        $this->ensureDirectory($defaultDir);

        if (!empty($logFile)) {
            $subDir = dirname($logFile);
            if ($subDir !== '.' && $subDir !== DIRECTORY_SEPARATOR) {
                $this->ensureDirectory(STORAGE_PATH . '/logs/' . $subDir);
            }
            $this->logFile = STORAGE_PATH . '/logs/' . $logFile . '_' . $date . '.log';
        } else {
            $this->logFile = $defaultDir . '/' . $date . '.log';
        }
    }

    public function log(string $message): void
    {
        file_put_contents($this->logFile, $this->formatMessage($message), FILE_APPEND);
        error_log($message);
    }

    public function log2(string $message): void
    {
        $this->log($message);
    }

    public function log3(string $message = ''): void
    {
        $handler = new FileHandler($this->logFile);
        $formatter = new CommonFormatter();
        $logger = LoggerOne::getInstance();
        $logger->setHandler($handler);
        $logger->setFormatter($formatter);
        $logger->info($message);
    }

    private function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
    }

    private function formatMessage(string $message): string
    {
        return date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL;
    }
}
