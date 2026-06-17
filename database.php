<?php
/**
 * 数据库连接类
 */

class Database {
    private static $instance = null;
    private $pdo;
    private $connected = false;

    private function __construct() {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4';
        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            // 尝试选择数据库
            $this->pdo->exec("USE `" . DB_NAME . "`");
            $this->connected = true;
        } catch (PDOException $e) {
            $this->connected = false;
            $this->pdo = null;
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function isConnected() {
        return $this->connected;
    }

    public function getPdo() {
        return $this->pdo;
    }

    public function query($sql, $params = []) {
        if (!$this->connected || !$this->pdo) {
            return false;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll($sql, $params = []) {
        $result = $this->query($sql, $params);
        return $result ? $result->fetchAll() : [];
    }

    public function fetchOne($sql, $params = []) {
        $result = $this->query($sql, $params);
        return $result ? $result->fetch() : false;
    }

    public function fetchColumn($sql, $params = []) {
        $result = $this->query($sql, $params);
        return $result ? $result->fetchColumn() : false;
    }

    public function insert($table, $data) {
        if (!$this->connected || !$this->pdo) return false;
        $keys = array_keys($data);
        $fields = implode(', ', array_map(function($k) { return "`$k`"; }, $keys));
        $placeholders = implode(', ', array_fill(0, count($keys), '?'));
        $sql = "INSERT INTO `$table` ($fields) VALUES ($placeholders)";
        $this->query($sql, array_values($data));
        return $this->pdo->lastInsertId();
    }

    public function update($table, $data, $where, $whereParams = []) {
        if (!$this->connected || !$this->pdo) return 0;
        $sets = implode(', ', array_map(function($k) { return "`$k` = ?"; }, array_keys($data)));
        $sql = "UPDATE `$table` SET $sets WHERE $where";
        $params = array_merge(array_values($data), $whereParams);
        $result = $this->query($sql, $params);
        return $result ? $result->rowCount() : 0;
    }

    public function delete($table, $where, $params = []) {
        if (!$this->connected || !$this->pdo) return 0;
        $sql = "DELETE FROM `$table` WHERE $where";
        $result = $this->query($sql, $params);
        return $result ? $result->rowCount() : 0;
    }

    public function count($table, $where = '1', $params = []) {
        $sql = "SELECT COUNT(*) FROM `$table` WHERE $where";
        $result = $this->fetchColumn($sql, $params);
        return $result !== false ? (int)$result : 0;
    }
}

function db() {
    return Database::getInstance();
}
