<?php

spl_autoload_register(function ($class) {
    require_once $class . '.php';
});

class Connection extends LinkDb
{
    /**
     * @var string $table 查询表名
     */
    protected $table;

    /**
     * @var string $fields 查询字段名
     */
    protected $fields = '*';

    /**
     * @var array $where 查询语句
     */
    protected $where;

    /**
     * @var string $statement 构建SQL语句
     */
    protected $statement;

    /**
     * @var string $order 构建SQL ORDER语句
     */
    protected $order;

    /**
     * @var string $sql 最终执行语句
     */
    protected string $sql;

    /**
     * @var $result mysqli_result
     */
    protected mysqli_result $result;


    public function __construct()
    {
        $this->init();
    }

    /**
     * 设置表名
     * @param $table
     * @return $this
     */
    public function table($table): Connection
    {
        is_string($table) && $this->table = $table;
        return $this;
    }

    /**
     * 设置查询字段名‘field1,field2,field3...’
     * @param string $fields
     * @return $this
     */
    public function fields($fields = '*'): Connection
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * 设置查询条件
     * @param $field
     * @param $op '=','!=','>'....
     * @param $where
     * @return $this
     */
    public function where($field, $op, $where): Connection
    {
        if (!empty($this->statement)) {
            $this->statement .= " AND $field $op $where";
        } else {
            $this->statement = "$field $op $where";
        }
        return $this;
    }

    /**
     * 设置排序
     * @param $field
     * @param string $order
     * @return $this
     */
    public function order($field, $order = 'ASC'): Connection
    {
        $this->order = "$field $order";
        return $this;
    }

    /**
     * 执行语句
     * @return $this
     */
    public function select(): Connection
    {
        $sql = "SELECT $this->fields FROM $this->table ";
        if ($this->statement) {
            $sql .= " WHERE $this->statement";
        }
        if ($this->order) {
            $sql .= " ORDER BY $this->order";
        }
        $this->sql = $sql;

        $this->result = $this->query();

        return $this;
    }

    /**
     * 输出数组
     * @return array
     */
    public function toArray(): array
    {
        if ($this->result->num_rows > 0) {
            $this->fields == '*' && $this->fields = $this->setAllFields();
            $temp = [];
            while ($row = $this->result->fetch_assoc()) {
                $temp[] = $row;
            }
            return $temp;
        } else {
            return [];
        }
    }

    /**
     * 获取执行语句
     * @return string
     */
    public function getLastSql(): string
    {
        return $this->sql;
    }

}

$result = (new Connection())->table('stories')
    ->fields('*')
    ->where('is_show', '=', 1)
    ->where('is_hot', '=', 12)
    ->order('created_at', 'DESC')
    ->select();
//echo $result->getLastSql();
var_dump($result->toArray());