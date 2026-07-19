<?php
/**
 * MySQL 服务（PDO 封装）
 * 连接配置从 config/mysql.php 读取
 * Class MysqlService
 */
class MysqlService
{
    /** @var PDO */
    private $pdo;

    public function __construct($database = null)
    {
        $config = require dirname(__FILE__) . '/../../config/mysql.php';
        $db = $database ?: ($config['database'] ?? '');
        $dsn = "mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}";
        if (!empty($db)) {
            $dsn .= ";dbname={$db}";
        }
        $this->pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    /**
     * 查询并返回所有行
     * @param string $sql SQL语句（支持占位符）
     * @param array $params 绑定参数
     * @return array
     */
    public function query($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * 查询并返回一行
     * @param string $sql
     * @param array $params
     * @return array|null
     */
    public function queryOne($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * 执行写操作（INSERT/UPDATE/DELETE），返回影响行数
     * @param string $sql
     * @param array $params
     * @return int 影响行数
     */
    public function execute($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * 插入数据并返回 lastInsertId
     * @param string $sql
     * @param array $params
     * @return string
     */
    public function insert($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $this->pdo->lastInsertId();
    }

    /**
     * 获取 PDO 原始对象（用于事务等高级操作）
     * @return PDO
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * 开始事务
     */
    public function beginTransaction()
    {
        $this->pdo->beginTransaction();
    }

    /**
     * 提交事务
     */
    public function commit()
    {
        $this->pdo->commit();
    }

    /**
     * 回滚事务
     */
    public function rollback()
    {
        $this->pdo->rollBack();
    }
}
