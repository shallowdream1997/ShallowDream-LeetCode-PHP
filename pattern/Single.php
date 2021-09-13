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

    private function __construct(){}

    static public $instance;

    static public function getInstance(): Single
    {
        if(!self::$instance){
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}
