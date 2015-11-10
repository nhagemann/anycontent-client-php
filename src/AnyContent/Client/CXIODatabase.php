<?php
namespace AnyContent\Client;

class CXIODatabase
{

    protected $app;

    protected $db;


    public function getConnection()
    {
        if (!$this->db)
        {

            // http://stackoverflow.com/questions/18683471/pdo-setting-pdomysql-attr-found-rows-fails
            $this->db = new \PDO('mysql:host=localhost;dbname=anycontent', 'hahnair', 'webdev', array( \PDO::MYSQL_ATTR_FOUND_ROWS => true ));

            $this->db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $this->db->exec("SET NAMES utf8");

        }

        return $this->db;
    }


    public function insert($tableName, $insert, $update = false)
    {
        /** @var PDO $db */
        $dbh = $this->getConnection();

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

        $stmt = $dbh->prepare($sql);

        return $stmt->execute($values);
    }


    public function update($tableName, $update, $where = false)
    {
        /** @var PDO $db */
        $dbh = $this->getConnection();

        $values = array_values($update);

        $sql = ' UPDATE `' . $tableName;
        $sql .= '` SET `' . join('` = ? , `', array_keys($update)) . '` = ?';

        if ($where)
        {
            $sql .= ' WHERE `' . join('` = ? AND `', array_keys($where)) . '` = ?';
            $values = array_merge($values, array_values($where));
        }

        $stmt = $dbh->prepare($sql);

        return $stmt->execute($values);
    }


    public function execute($pdoSql, $params = array())
    {
        /** @var PDO $db */
        $dbh = $this->getConnection();

        $stmt = $dbh->prepare($pdoSql);

        return $stmt->execute($params);
    }


    public function fetchOne($tableName, $where = array())
    {
        /** @var PDO $db */
        $dbh = $this->getConnection();
        $sql = 'SELECT * FROM `' . $tableName;
        $sql .= '` WHERE `' . join('` = ? AND `', array_keys($where)) . '` = ?';
        $params = array_values($where);
        $stmt   = $dbh->prepare($sql);

        $stmt->execute($params);

        return $stmt->fetch();

    }


    public function fetchOneSQL($pdoSql, $params = array())
    {
        /** @var PDO $db */
        $dbh = $this->getConnection();

        $stmt = $dbh->prepare($pdoSql);

        $stmt->execute($params);

        return $stmt->fetch();

    }


    public function fetchAll($tableName, $where = array())
    {
        /** @var PDO $db */
        $dbh = $this->getConnection();
        $sql = 'SELECT * FROM `' . $tableName;
        $sql .= '` WHERE `' . join('` = ? AND , `', array_keys($where)) . '` = ?';
        $params = array_values($where);
        $stmt   = $dbh->prepare($sql);

        $stmt->execute($params);

        $rows = $stmt->fetchAll();

        return $rows;

    }


    public function fetchAllSQL($pdoSql, $params = array())
    {
        /** @var PDO $db */
        $dbh = $this->getConnection();

        $stmt = $dbh->prepare($pdoSql);

        $stmt->execute($params);

        $rows = $stmt->fetchAll();

        return $rows;

    }


    /**
     * http://stackoverflow.com/questions/210564/getting-raw-sql-query-string-from-pdo-prepared-statements
     */
    public function debugPrintQuery($pdoSql, $params = array())
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
            $value = '#' . $value . '#';
        }

        $query = preg_replace($keys, $params, $pdoSql, 1, $count);

        return $query;
    }

}