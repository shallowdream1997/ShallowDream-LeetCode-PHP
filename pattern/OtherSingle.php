<?php


/**
 * 单例模式
 * 单例模式确保某个类只有一个实例，而且自行实例化并向整个系统提供这个实例。
 * 单例模式可以避免大量的new操作。因为每一次new操作都会消耗系统和内存的资源。
 *
 * 单例模式有以下3个特点：
 * 1．只能有一个实例。
 * 2．必须自行创建这个实例。
 * 3．必须给其他对象提供这一实例。
 *
 * Class OtherSingle
 * @package app\common
 */
class OtherSingle
{
    static private ?OtherSingle $instance = null;

    private $name;

    //构造函数私有化，防止外部调用
    private function __construct($name)
    {
        $this->name = $name;
    }

    //克隆函数私有化，防止外部克隆对象
    private function __clone()
    {
    }

    static public function getInstance($name): OtherSingle
    {
        if (!self::$instance) {
            self::$instance = new self($name);
        }
        return self::$instance;
    }

    public function getName()
    {
        return $this->name;
    }
}


var_dump(OtherSingle::getInstance(1)->getName());
var_dump(OtherSingle::getInstance(4)->getName());

