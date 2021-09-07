<?php


class LinkDb
{
    private $hostname = 'localhost';
    private $database = 'aglaravel';
    private $username = 'root';
    private $password = 'root';
    private $hostport = '3306';

    protected $table;
    /**
     * @var $connect mysqli
     */
    protected $connect;

    protected function init()
    {
        $this->connect = new mysqli($this->hostname, $this->username, $this->password, $this->database, $this->hostport);
        if (!$this->connect) {
            die('连接失败');
        }
    }

    /**
     * 查询表所有的字段
     * @return string
     */
    protected function setAllFields(): string
    {
        $query = "SHOW FULL COLUMNS FROM $this->table";
        $result = $this->connect->query($query);
        $fields = [];
        while ($row = $result->fetch_row()) {
            $fields[] = $row[0];
        }
        $string = implode(",", $fields);
        return $string;
    }
}