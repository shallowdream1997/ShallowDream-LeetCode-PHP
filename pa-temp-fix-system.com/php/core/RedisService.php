<?php
/**
 * Redis 服务（配置化）
 * 连接配置从 config/redis.php 读取
 * Class RedisService
 */
class RedisService
{
    /**
     * redis本地调用
     * @var Redis
     */
    private $redis;

    public function __construct()
    {
        $config = require dirname(__FILE__) . '/../../config/redis.php';
        $this->redis = new Redis();
        $this->redis->connect($config['host'], $config['port'], $config['timeout'] ?? 0);
        if (!empty($config['password'])) {
            $this->redis->auth($config['password']);
        }
        if (isset($config['database']) && $config['database'] > 0) {
            $this->redis->select($config['database']);
        }
    }

    /**
     * hash类型 写入数据
     * @param string $key key值
     * @param string $keyName key-key
     * @param string $content key-value
     * @param null $time 有效期秒级
     * @return bool|int
     */
    public function hSet(string $key, string $keyName, string $content, $time = null)
    {
        $data = $this->redis->hSet($key, $keyName, $content);
        if ($time) {
            $this->redis->expire($key, $time);
        }
        return $data;
    }

    /**
     * hash类型 获取数据
     * @param string $key key值
     * @param string $hashKey key-key
     * @return string
     */
    public function hGet(string $key, string $hashKey): string
    {
        return $this->redis->hGet($key, $hashKey);
    }

    public function hGetAll($key): array
    {
        return $this->redis->hGetAll($key);
    }

    public function get($key)
    {
        return $this->redis->get($key);
    }

    public function set($key, $content, $time = null): bool
    {
        return $this->redis->set($key, $content, $time);
    }

    public function del($key1, ...$otherKeys): int
    {
        return $this->redis->del($key1, ...$otherKeys);
    }

    public function incr($key): int
    {
        return $this->redis->incr($key);
    }

    public function expire($key, $time): bool
    {
        return $this->redis->expire($key, $time);
    }
}
