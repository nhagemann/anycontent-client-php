<?php
namespace AnyContent\Connection\Util;

use KVMLogger\KVMLogger;

class Database
{

    /** @var  \PDO */
    protected $pdo;

    protected $queryCounter = 0;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }


    /**
     * @return \PDO
     */
    public function getConnection()
    {
        return $this->pdo;
    }


    /**
     * @param       $sql
     * @param array $params
     *
     * @return \PDOStatement
     */
    public function execute($sql, $params = array())
    {
        $kvm = KVMLogger::instance('anycontent-database');

        /** @var \PDO $db */
        $dbh = $this->getConnection();

        $stmt = $dbh->prepare($sql);

        $kvm->startStopWatch('anycontent-query-execution-time');

        $stmt->execute($params);

        $duration = $kvm->getDuration('anycontent-query-execution-time');
        $message  = $kvm->createLogMessage($this->debugQuery($sql, $params), [ 'duration' => $duration ]);
        $kvm->debug($message);
        
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

        if ($update)
        {
            $sql .= ' ON DUPLICATE KEY UPDATE `' . join('` = ? , `', array_keys($update)) . '` = ?';
            $values = array_merge($values, array_values($update));
        }

        $stmt = $this->execute($sql, $values);

        return $stmt;
    }


    public function update($tableName, $update, $where = false)
    {
        $values = array_values($update);

        $sql = ' UPDATE `' . $tableName;
        $sql .= '` SET `' . join('` = ? , `', array_keys($update)) . '` = ?';

        if ($where)
        {
            $sql .= ' WHERE `' . join('` = ? AND `', array_keys($where)) . '` = ?';
            $values = array_merge($values, array_values($where));
        }

        $stmt = $this->execute($sql, $values);

        return $stmt;
    }


    public function fetchOne($tableName, $where = array())
    {

        $sql = 'SELECT * FROM `' . $tableName;
        $sql .= '` WHERE `' . join('` = ? AND `', array_keys($where)) . '` = ?';
        $params = array_values($where);

        $stmt = $this->execute($sql, $params);

        return $stmt->fetch();

    }


    public function fetchOneSQL($sql, $params = array())
    {
        $stmt = $this->execute($sql, $params);

        return $stmt->fetch();
    }


    public function fetchColumn($tableName, $column, $where = array())
    {
        $sql = 'SELECT * FROM `' . $tableName;
        $sql .= '` WHERE `' . join('` = ? AND `', array_keys($where)) . '` = ?';
        $params = array_values($where);

        $stmt = $this->execute($sql, $params);

        return $stmt->fetchColumn($column);

    }


    public function fetchColumnSQL($sql, $column, $params = array())
    {

        $stmt = $this->execute($sql, $params);

        return $stmt->fetchColumn($column);

    }


    public function fetchAll($tableName, $where = array())
    {

        $sql = 'SELECT * FROM `' . $tableName;
        $sql .= '` WHERE `' . join('` = ? AND , `', array_keys($where)) . '` = ?';
        $params = array_values($where);

        $stmt = $this->execute($sql, $params);

        $rows = $stmt->fetchAll();

        return $rows;

    }


    public function fetchAllSQL($sql, $params = array())
    {

        $stmt = $this->execute($sql, $params);

        $rows = $stmt->fetchAll();

        return $rows;

    }


    /**
     * http://stackoverflow.com/questions/210564/getting-raw-sql-query-string-from-\PDO-prepared-statements
     */
    public function debugQuery($sql, $params = array())
    {
        $keys = array();

        # build a regular expression for each parameter
        foreach ($params as $key => &$value)
        {
            if (is_string($key))
            {
                $keys[] = '/:' . $key . '/';
            }
            else
            {
                $keys[] = '/[?]/';
            }
            $value = '[#' . $value . '#]';
        }

        $query = preg_replace($keys, $params, $sql, 1, $count);

        return $query;
    }


    /**
     * @return int
     */
    public function getQueryCounter()
    {
        return $this->queryCounter;
    }


}