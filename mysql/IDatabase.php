<?php


namespace app\common\pattern;

/**
 * 接口类命名
 * Interface IDatabase
 * @package app\common\pattern
 */
interface IDatabase
{
    public function connect($host,$username,$password,$dbname);
    public function query($sql);
    public function close();
}