<?php

/**
 * 注册模式
 * 注册模式，解决全局共享和交换对象。已经创建好的对象，挂在到某个全局可以使用的数组上，在需要使用的时候，直接从该数组上获取即可。将对象注册到全局的树上。任何地方直接去访问。
 * Class Register
 * @package app\common\pattern
 */
class Register
{
    protected static $objects;

    /**
     * 将对象注册到全局的树上
     * @param $alias
     * @param $object
     */
    public function set($alias, $object)
    {
        //将对象放到树上
        self::$objects[$alias] = $object;
    }

    static public function get($name)
    {
        return isset(self::$objects[$name]) ? self::$objects[$name] : '未注册';//获取某个注册到树上的对象
    }

    public function __unset($name)
    {
        // TODO: Implement __unset() method.
        unset(self::$objects[$name]);//移除某个注册到树上的对象。
    }

    public function getNameToKing($name)
    {
        return self::$objects[$name].' 在唱歌';
    }
}