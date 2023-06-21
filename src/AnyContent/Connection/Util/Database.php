<?php

declare(strict_types=1);

namespace AnyContent\Connection\Util;

use PDO;
use PDOStatement;

class Database
{
    protected PDO $pdo;

    protected int $queryCounter = 0;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    public function execute(string $sql, array $params = []): PDOStatement
    {
        $dbh = $this->getConnection();

        $stmt = $dbh->prepare($sql);

        $stmt->execute($params);

        $this->queryCounter++;

        return $stmt;
    }

    public function insert($tableName, $insert, $update = false)
    {
        $sql = 'INSERT INTO `' . $tableName;
        $sql .= '` (`' . join('`,`', array_keys($insert)) . '`)';
        $sql .= ' VALUES ( ?';
        $sql .= str_repeat(' , ?', count($insert) - 1);
        $sql .= ')';

        $values = array_values($insert);

        if ($update) {
            $sql .= ' ON DUPLICATE KEY UPDATE `' . join('` = ? , `', array_keys($update)) . '` = ?';
            $values = array_merge($values, array_values($update));
        }

        return $this->execute($sql, $values);
    }

    public function update($tableName, $update, $where = false)
    {
        $values = array_values($update);

        $sql = ' UPDATE `' . $tableName;
        $sql .= '` SET `' . join('` = ? , `', array_keys($update)) . '` = ?';

        if ($where) {
            $sql .= ' WHERE `' . join('` = ? AND `', array_keys($where)) . '` = ?';
            $values = array_merge($values, array_values($where));
        }

        return $this->execute($sql, $values);
    }

    public function fetchOne($tableName, $where = [])
    {
        $sql = 'SELECT * FROM `' . $tableName;
        $sql .= '` WHERE `' . join('` = ? AND `', array_keys($where)) . '` = ?';
        $params = array_values($where);

        $stmt = $this->execute($sql, $params);

        return $stmt->fetch();
    }

    public function fetchOneSQL($sql, $params = [])
    {
        $stmt = $this->execute($sql, $params);

        return $stmt->fetch();
    }

    public function fetchColumn($tableName, $column, $where = [])
    {
        $sql = 'SELECT * FROM `' . $tableName;
        $sql .= '` WHERE `' . join('` = ? AND `', array_keys($where)) . '` = ?';
        $params = array_values($where);

        $stmt = $this->execute($sql, $params);

        return $stmt->fetchColumn($column);
    }

    public function fetchColumnSQL($sql, $column, $params = [])
    {
        $stmt = $this->execute($sql, $params);

        return $stmt->fetchColumn($column);
    }

    public function fetchAll($tableName, $where = [])
    {
        $sql = 'SELECT * FROM `' . $tableName;
        $sql .= '` WHERE `' . join('` = ? AND , `', array_keys($where)) . '` = ?';
        $params = array_values($where);

        $stmt = $this->execute($sql, $params);

        return $stmt->fetchAll();
    }

    public function fetchAllSQL($sql, $params = [])
    {
        $stmt = $this->execute($sql, $params);

        return $stmt->fetchAll();
    }

    /**
     * http://stackoverflow.com/questions/210564/getting-raw-sql-query-string-from-\PDO-prepared-statements
     */
    public function debugQuery($sql, $params = [])
    {
        $keys = [];

        # build a regular expression for each parameter
        foreach ($params as $key => &$value) {
            if (is_string($key)) {
                $keys[] = '/:' . $key . '/';
            } else {
                $keys[] = '/[?]/';
            }
            $value = '[#' . $value . '#]';
        }

        return preg_replace($keys, $params, $sql, 1);
    }

    /**
     * @return int
     */
    public function getQueryCounter()
    {
        return $this->queryCounter;
    }
}
