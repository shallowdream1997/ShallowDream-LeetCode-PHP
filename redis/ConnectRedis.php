<?php


class ConnectRedis
{
    private Redis $redis;

    public function __construct()
    {
        $this->redis = new Redis();
        $this->redis->pconnect('127.0.0.1');
    }

    public function setKey($key,$string,$times = 5*3600)
    {
        $this->redis->set($key,$string,$times);
        return $this;
    }

    public function getKey($key)
    {
        return $this->redis->get($key);
    }

    /**
     * 验证键是否存在，存在返回true
     * @param $key
     * @return $this
     * @throws Exception
     */
    public function existKey($key)
    {
        $bool = $this->redis->exists($key);
        if ($bool){
            return $this;
        }
        throw new Exception('键值不存在');
    }

    public function incrKey($key,$number)
    {
        $this->redis->incrBy($key,$number);
        return $this;
    }

    /**
     * 批量设置 key=>value
     * @param $keyArray
     * @return $this
     */
    public function mSetKey($keyArray)
    {
        $this->redis->msetnx($keyArray);
        return $this;
    }

//    public function mSetKey()
//    {
//        $mget = $this->redis->mget(array('number','key')); // 批量获取键值,返回一个数组
//
//        $this->redis->mset(array('key0' => 'value0', 'key1' => 'value1')); // 批量设置键值
//
//        $this->redis->msetnx(array('key0' => 'value0', 'key1' => 'value1'));
//
//        // 批量设置键值，类似将setnx()方法批量操作
//
//        $this->redis->append('key', '-Smudge'); //原键值TK，将值追加到键值后面，键值为TK-Smudge
//
//        $this->redis->getRange('key', 0, 5); // 键值截取从0位置开始到5位置结束
//
//        $this->redis->getRange('key', -6, -1); // 字符串截取从-6(倒数第6位置)开始到-1(倒数第1位置)结束
//
//        $this->redis->setRange('key', 0, 'Smudge');
//
//        // 键值中替换字符串，0表示从0位置开始
//
//        //有多少个字符替换多少位置，其中汉字占2个位置
//
//        $this->redis->strlen('key'); //键值长度
//
//        $this->redis->getBit('key');
//
//        $this->redis->setBit('key');
//    }
}

$conn = new ConnectRedis();
$bubble = $conn->mSetKey(['quick'=>44,'bubble'=>43]);


var_dump($bubble);