<?php

namespace AnyContent\Client;

use Doctrine\Common\Cache\CacheProvider;

/**
 * Simple MySQL based Cache Provider of no memory cache is available.
 *
 * CREATE TABLE `doctrine_cache` (
 * `id` varchar(255) NOT NULL DEFAULT '',
 * `data` longtext,
 * `lifetime` int(11) NOT NULL DEFAULT '0',
 * `creation` int(11) NOT NULL,
 * PRIMARY KEY (`id`),
 * KEY `id` (`id`,`lifetime`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 *
 *
 * @package AnyContent\Client
 */
class MySQLCache extends CacheProvider
{

    protected $dsn;

    protected $user;

    protected $password;

    protected $db;

    protected $tableName;


    public function __construct($host, $dbname, $tableName, $user, $password, $port)
    {

        $this->dsn = 'mysql:host=' . $host . ';dbname=' . $dbname . ';port=' . $port;

        $this->user     = $user;
        $this->password = $password;

        $this->tableName = $tableName;
    }


    public function getConnection()
    {
        if (!$this->db)
        {

            // http://stackoverflow.com/questions/18683471/pdo-setting-pdomysql-attr-found-rows-fails
            $this->db = new \PDO($this->dsn, $this->user, $this->password, array( \PDO::MYSQL_ATTR_FOUND_ROWS => true ));

            $this->db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $this->db->exec("SET NAMES utf8");

        }

        return $this->db;
    }


    /**
     * Fetches an entry from the cache.
     *
     * @param string $id The id of the cache entry to fetch.
     *
     * @return string|boolean The cached data or FALSE, if no cache entry exists for the given id.
     */
    protected function doFetch($id)
    {
        $now = time();

        /** @var PDO $db */
        $dbh = $this->getConnection();

        $sql = 'SELECT * FROM ' . $this->tableName . ' WHERE id=? AND (lifetime =0 OR lifetime > ?)';

        $stmt = $dbh->prepare($sql);

        $params   = array();
        $params[] = $id;
        $params[] = $now;

        $stmt->execute($params);

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row)
        {
            return unserialize($row['data']);
        }

        return false;
    }


    /**
     * Tests if an entry exists in the cache.
     *
     * @param string $id The cache id of the entry to check for.
     *
     * @return boolean TRUE if a cache entry exists for the given cache id, FALSE otherwise.
     */
    protected function doContains($id)
    {
        $now = time();

        /** @var PDO $db */
        $dbh = $this->getConnection();

        $sql = 'SELECT id FROM ' . $this->tableName . ' WHERE id=? AND (lifetime =0 OR lifetime > ?)';

        $stmt = $dbh->prepare($sql);

        $params   = array();
        $params[] = $id;
        $params[] = $now;

        $stmt->execute($params);

        return ((boolean)$stmt->fetchColumn());
    }


    /**
     * Puts data into the cache.
     *
     * @param string $id         The cache id.
     * @param string $data       The cache entry/data.
     * @param int    $lifeTime   The lifetime. If != 0, sets a specific lifetime for this
     *                           cache entry (0 => infinite lifeTime).
     *
     * @return boolean TRUE if the entry was successfully stored in the cache, FALSE otherwise.
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        $r = rand(1, 10000);

        if ($r == 9999)
        {
            $this->doGarbageCollection();
        }

        $now = time();
        if ($lifeTime != 0)
        {
            $lifeTime = $now + $lifeTime;
        }

        /** @var PDO $db */
        $dbh = $this->getConnection();

        $sql = 'INSERT INTO ' . $this->tableName . ' (id, data,lifetime,creation) VALUES ( ?, ?, ?, ?) ON DUPLICATE KEY UPDATE
data=?, lifetime=?, creation=?';

        $stmt = $dbh->prepare($sql);

        $params   = array();
        $params[] = $id;
        $params[] = serialize($data);
        $params[] = $lifeTime;
        $params[] = $now;
        $params[] = serialize($data);
        $params[] = $lifeTime;
        $params[] = $now;

        return $stmt->execute($params);
    }


    /**
     * Deletes a cache entry.
     *
     * @param string $id The cache id.
     *
     * @return boolean TRUE if the cache entry was successfully deleted, FALSE otherwise.
     */
    protected function doDelete($id)
    {
        /** @var PDO $db */
        $dbh = $this->getConnection();

        $sql = 'DELETE FROM ' . $this->tableName . ' WHERE id = ?';

        $stmt     = $dbh->prepare($sql);
        $params   = array();
        $params[] = $id;

        return $stmt->execute($params);
    }


    /**
     * Flushes all cache entries.
     *
     * @return boolean TRUE if the cache entry was successfully deleted, FALSE otherwise.
     */
    protected function doFlush()
    {
        /** @var PDO $db */
        $dbh = $this->getConnection();

        $sql = 'TRUNCATE ' . $this->tableName;;

        $stmt = $dbh->prepare($sql);

        return $stmt->execute();
    }


    /**
     * Retrieves cached information from the data store.
     *
     * @since 2.2
     *
     * @return array|null An associative array with server's statistics if available, NULL otherwise.
     */
    protected function doGetStats()
    {
        return false;
    }


    protected function doGarbageCollection()
    {
        $now = time();

        /** @var PDO $db */
        $dbh = $this->getConnection();

        $sql = 'DELETE FROM ' . $this->tableName . ' WHERE lifetime != 0 AND lifetime < ? ';

        $stmt = $dbh->prepare($sql);

        $params   = array();
        $params[] = $now;

        $stmt->execute($params);

        $sql = 'DELETE FROM ' . $this->tableName . ' WHERE lifetime = 0 AND  creation < ? ';

        $stmt = $dbh->prepare($sql);

        $params   = array();
        $params[] = $now - (3 * 24 * 3600);

        $stmt->execute($params);
    }
}