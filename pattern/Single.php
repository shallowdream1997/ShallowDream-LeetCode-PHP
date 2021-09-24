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
 * Class Single
 * @package app\common
 */
class Single
{
    private $name;

    static private ?Single $instance = null;

    //构造函数私有化，防止外部调用
    private function __construct(){}

    //克隆函数私有化，防止外部克隆对象
    private function __clone(){}

    static public function getInstance(): Single
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
    public function getName()
    {
        return $this->name;
    }
}


$instance1 = Single::getInstance()->setName(1);
$instance2 = Single::getInstance()->setName(32);
var_dump($instance1->getName());
var_dump($instance2->getName());
