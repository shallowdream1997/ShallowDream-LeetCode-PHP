<?php


namespace app\common\pattern;

spl_autoload_register(function ($class) {
    require_once $class . ".php";
});
class Mysql implements IDatabase
{
    protected $conn;

    public function connect($host, $username, $password, $dbname)
    {
        // TODO: Implement connect() method.
        $conn = mysql_connect($host, $username, $password);
        mysql_select_db($dbname, $conn);
        $this->conn = $conn;
    }

    public function query($sql)
    {
        // TODO: Implement query() method.
        $res = mysql_query($sql, $this->conn);
        return $res;
    }

    public function close()
    {
        // TODO: Implement close() method.
        mysql_close($this->conn);
    }
}