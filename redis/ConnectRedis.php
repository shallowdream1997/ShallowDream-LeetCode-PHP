<?php


class ConnectRedis
{
    private Redis $redis;

    public function __construct()
    {
        $this->redis = new Redis();
        $this->redis->pconnect('127.0.0.1');
    }

    //$redis->setex('hobby', 60, 'fishing');//设置过期时间
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

    /**
     * 批量获取键值 key
     * @param $keyArray
     * @return array
     */
    public function mGetKey($keyArray)
    {
        return $this->redis->mget($keyArray);
    }

    public function appendKey($tK,$tKey)
    {
        $this->redis->append($tK,$tKey);
        return $this;
    }

    public function getRangeKey($key,$start,$end)
    {
        $this->redis->getRange($key, $start, $end);
        return $this;
    }

    public function setRangeKey($key,$offset,$value)
    {
        $this->redis->setRange($key, $offset, $value);
        return $this;
    }

    public function lPushKey($pushKey,$values)
    {
        $this->redis->lPush($pushKey,$values);
        return $this;
    }

    public function rPushKey($pushKey,$values)
    {
        $this->redis->rPush($pushKey,$values);
        return $this;
    }

    public function lRangeKey($key,$start,$offset)
    {
        return $this->redis->lRange($key,$start,$offset);
    }

    public function lRangeKeyFun($array)
    {
        call_user_func_array([$this->redis,'lPush'],$array);
        return $this;
    }

    public function lInsertKey($key,$position,$povot,$value)
    {
        $this->redis->lInsert($key, $position, $povot, $value);
        return $this;
    }

    /**
     * 出队
     * @param $key
     * @return bool|mixed
     */
    public function rPopKey($key)
    {
        return $this->redis->rPop($key);
    }

    /**
     * 集合
     * @param $array
     * @return $this
     */
    public function setAddKey($array)
    {
        call_user_func_array([$this->redis,'sAdd'],$array);
        return $this;
    }

    /**
     * 随机选取集合中的一个元素
     * @param $key
     * @return array|bool|mixed|string
     */
    public function sPopKey($key)
    {
        return $this->redis->sPop($key);
    }

    /**
     * 删除指定元素
     * @param $array
     * @return $this
     */
    public function sRemKey($array)
    {
        call_user_func_array([$this->redis,'sRem'],$array);
        return $this;
    }

    /**
     * 获取全部元素
     * @param $key
     * @return array
     */
    public function sMembersKey($key)
    {
        return $this->redis->sMembers($key);
    }

    /**
     * 统计集合中元素个数
     * @param $key
     * @return int
     */
    public function sCardKey($key)
    {
        return $this->redis->sCard($key);
    }

    /**
     * 并集
     * @param $key1
     * @param $key2
     * @return array
     */
    public function sUnionKey($key1,$key2)
    {
        return $this->redis->sUnion($key1,$key2);
    }

    /**
     * 差集
     * @param $key1
     * @param $key2
     * @return array
     */
    public function sDiffKey($key1,$key2)
    {
        return $this->redis->sDiff($key1,$key2);
    }

    /**
     * 交集
     * @param $key1
     * @param $key2
     * @return array
     */
    public function sInterKey($key1,$key2)
    {
        return $this->redis->sInter($key1,$key2);
    }


    /**
     * 有序集合
     * @param string $key key值
     * @param string $value 相当于键名
     * @param string $score 相当于键值
     * @return $this
     */
    public function zAddKey($key,$value,$score)
    {
        $this->redis->zAdd($key,$value,$score);
        return $this;
    }

    public function zRevRangeKey($key,$start,$end,$withscore = true)
    {
        return $this->redis->zRevRange($key, $start, $end, $withscore);
    }

    /**
     * 获取范围数字内的元素
     * @param $key
     * @param $start
     * @param $end
     * @return array
     */
    public function zRangeByScore($key,$start,$end)
    {
        return $this->redis->zRangeByScore($key, $start, $end, ['withscores' => true]);
    }


    /**
     * hash redis 相当于php 中的设置数组array
     */



    /**
     * hash哈希 Redis hash 是一个 string 类型的 field（字段） 和 value（值） 的映射表，hash 特别适合用于存储对象。
     * @param $Key
     * @param $name
     * @param $value
     * @return $this
     */
    public function hSet($Key,$name,$value)
    {
        $this->redis->hSet($Key,$name,$value);
        return $this;
    }

    /**
     * 批量设置哈希
     * @param $key
     * @param $array
     * @return $this
     */
    public function hMset($key,$array)
    {
        $this->redis->hMSet($key, $array);
        return $this;
    }

    /**
     * 获取键值所有元素
     * @param $key
     * @return array
     */
    public function hGetAll($key)
    {
        return $this->redis->hGetAll($key);
    }
}

$conn = new ConnectRedis();
$json = json_encode([1,345,6,6,2,62,6,7,3,12,4],JSON_UNESCAPED_UNICODE);
//$bubble = $conn->mSetKey(['quick'=>$json,'bubble'=>43])->mGetKey(['quick','bubble']);
//$bubble = $conn->lRangeKeyFun(['pushKeyFunc',41,33,61,56,23,62,168,70,37,12,14]);
//$da = $conn->lRangeKey('pushKeyFunc',3,4);
//$da = $conn->rPopKey('pushKeyFunc');
//$da = $conn->lInsertKey('pushKeyFunc',Redis::AFTER,14,564);
//$da = $conn->setAddKey(['sAddKey2','blai','oik','ingenue','naves','tian']);
//$da = $conn->sPopKey('sAddKey');
//$da = $conn->sRemKey(['sAddKey','greyer','black']);
//$da = $conn->sMembersKey('sAddKey');
//$count = $conn->sCardKey('sAddKey');
//$da = $conn->zAddKey('zAddKey',1,'darwin');
//$da = $conn->zAddKey('zAddKey',2,'crown');
//$da = $conn->zAddKey('zAddKey',3,'reborn');
//$da = $conn->zAddKey('zAddKey',4,'listing');
//$da = $conn->zAddKey('zAddKey',5,'testiest');
//$da = $conn
//    ->hSet('hSetKey','name','zag')
//    ->hSet('hSetKey','address','水岸')
//    ->hSet('hSetKey','password','acnaclaeihgabwjhbkzogeqegaldgage')
//    ->hSet('hSetKey','province_name','安徽')
//    ->hSet('hSetKey','city_name','六安');
//$u1 = [
//    'id'=> 1,
//    'name' => 'itbsl',
//    'age'  => 25,
//    'email' => 'itbsl@gmail.com',
//    'address' => '北京朝阳区大望路'
//];
//$da = $conn->hMSet('user:'.$u1['id'], $u1);
//
//$u2 = [
//    'id' => 2,
//    'name' => 'bashlog',
//    'age'  => 26,
//    'email' => 'bash@gmail.com',
//    'address' => '北京市海淀区西二旗'
//];
//$da = $conn->hMSet('user:'.$u2['id'], $u2);

$da = $conn->hGetAll('user:1');
var_dump($da);