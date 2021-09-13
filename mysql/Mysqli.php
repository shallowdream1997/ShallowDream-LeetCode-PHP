<?php


namespace app\common\pattern;
spl_autoload_register(function($class){
    require_once $class . ".php";
});

class Mysqli implements IDatabase
{
    protected $conn;

    public function connect($host, $username, $password, $dbname)
    {
        // TODO: Implement connect() method.
        $conn = mysqli_connect($host, $username, $password, $dbname);
        $this->conn = $conn;
    }

    public function query($sql)
    {
        // TODO: Implement query() method.
        return mysqli_query($this->conn, $sql);
    }

    public function close()
    {
        // TODO: Implement close() method.
        mysqli_close($this->conn);
    }

}