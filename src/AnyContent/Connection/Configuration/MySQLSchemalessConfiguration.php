<?php

declare(strict_types=1);

namespace AnyContent\Connection\Configuration;

use AnyContent\AnyContentClientException;
use AnyContent\Connection\AbstractConnection;
use AnyContent\Connection\MySQLSchemalessReadOnlyConnection;
use AnyContent\Connection\MySQLSchemalessReadWriteConnection;
use AnyContent\Connection\Util\Database;
use PDO;
use Symfony\Component\Finder\Finder;

class MySQLSchemalessConfiguration extends AbstractConfiguration
{
    protected ?Database $database;

    protected ?string $pathCMDLFolderForContentTypes = null;

    protected ?string $pathCMDLFolderForConfigTypes = null;

    protected ?string $repositoryName = null;

    public function initDatabase($host, $dbName, $username, $password, $port = 3306)
    {
        // http://stackoverflow.com/questions/18683471/pdo-setting-pdomysql-attr-found-rows-fails
        $pdo = new PDO(
            'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $dbName,
            $username,
            $password,
            array(PDO::MYSQL_ATTR_FOUND_ROWS => true)
        );

        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec("SET NAMES utf8");

        $this->database = new Database($pdo);

        $this->ensureInfoTablesArePresent();
    }

    public function setCMDLFolder($pathContentTypes, $pathConfigTypes = null)
    {
        $this->pathCMDLFolderForContentTypes = $pathContentTypes;

        if ($pathConfigTypes) {
            $this->pathCMDLFolderForConfigTypes = $pathConfigTypes;
        } else {
            $this->pathCMDLFolderForConfigTypes = $pathContentTypes . '/config';
        }
    }

    public function getRepositoryName(): string
    {
        if (!$this->repositoryName) {
            throw new AnyContentClientException('Please provide repository name or set cmdl folder path.');
        }

        return $this->repositoryName;
    }

    /**
     * @param null $repositoryName
     */
    public function setRepositoryName($repositoryName)
    {
        $this->repositoryName = $repositoryName;
    }

    protected function ensureInfoTablesArePresent()
    {
        $sql = 'SHOW TABLES LIKE ?';

        $stmt = $this->getDatabase()->getConnection()->prepare($sql);
        $stmt->execute(array('_cmdl_'));

        if ($stmt->rowCount() == 0) {
            $sql = <<< TEMPLATE_CMDLTABLE
        CREATE TABLE `_cmdl_` (
        `repository` varchar(255) NOT NULL DEFAULT '',
        `data_type` ENUM('content', 'config', ''),
        `name` varchar(255) NOT NULL DEFAULT '',
        `cmdl` text,
        `lastchange_timestamp` varchar(16) DEFAULT 0,
        UNIQUE KEY `index1` (`repository`,`data_type`,`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

TEMPLATE_CMDLTABLE;

            $stmt = $this->getDatabase()->getConnection()->prepare($sql);

            try {
                $stmt->execute();
            } catch (\PDOException $e) {
                throw new AnyContentClientException('Could not create mandatory table _cmdl_');
            }
        }

        $sql = "Show Tables Like '_counter_'";

        $stmt = $this->getDatabase()->getConnection()->prepare($sql);
        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            $sql = <<< TEMPLATE_COUNTERTABLE
CREATE TABLE `_counter_` (
  `repository` varchar(128) NOT NULL DEFAULT '',
  `content_type` varchar(128) NOT NULL DEFAULT '',
  `counter` bigint(20) DEFAULT 0,
  PRIMARY KEY (`repository`,`content_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
TEMPLATE_COUNTERTABLE;

            $stmt = $this->getDatabase()->getConnection()->prepare($sql);

            try {
                $stmt->execute();
            } catch (\PDOException $e) {
                throw new AnyContentClientException('Could not create mandatory table _counter_');
            }
        }

        $sql = "Show Tables Like '_update_'";

        $stmt = $this->getDatabase()->getConnection()->prepare($sql);
        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            $sql = <<< TEMPLATE_UPDATETABLE
CREATE TABLE `_update_` (
  `repository` varchar(255) NOT NULL,
  `data_type` enum('content','config') NOT NULL,
  `name` varchar(255) NOT NULL,
  `workspace` varchar(255) NOT NULL DEFAULT 'default',
  `language` varchar(255) NOT NULL DEFAULT 'default',
  `lastchange_timestamp` varchar(16) DEFAULT 0,
  PRIMARY KEY (`repository`,`data_type`,`name`,`workspace`,`language`),
  KEY `lastchange` (`lastchange_timestamp`),
  KEY `workspace` (`workspace`,`language`,`data_type`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
TEMPLATE_UPDATETABLE;

            $stmt = $this->getDatabase()->getConnection()->prepare($sql);

            try {
                $stmt->execute();
            } catch (\PDOException $e) {
                throw new AnyContentClientException('Could not create mandatory table _update_');
            }
        }
    }

    public function addContentTypes($contentTypes = null)
    {
        if (!$this->getDatabase()) {
            throw new AnyContentClientException('Database must be initalized first.');
        }
        if ($contentTypes == null) {
            if ($this->pathCMDLFolderForContentTypes != null) { // file based content/config types definition
                $finder = new Finder();

                $uri = 'file://' . $this->pathCMDLFolderForContentTypes;

                $finder->in($uri)->depth(0);

                foreach ($finder->files()->name('*.cmdl') as $file) {
                    $contentTypeName = $file->getBasename('.cmdl');

                    $this->contentTypes[$contentTypeName] = [];
                }
            } else // database based content/config types definition
            {
                $repositoryName = $this->getRepositoryName();

                $sql = 'SELECT name, data_type FROM _cmdl_ WHERE repository = ?';

                $rows = $this->getDatabase()->fetchAllSQL($sql, [$repositoryName]);

                foreach ($rows as $row) {
                    if ($row['data_type'] == 'content') {
                        $contentTypeName                      = $row['name'];
                        $this->contentTypes[$contentTypeName] = [];
                    }
                }
            }
        } else {
            foreach ($contentTypes as $contentTypeName) {
                $this->contentTypes[$contentTypeName] = [];
            }
        }
    }

    public function removeContentType($contentTypeName)
    {
        unset($this->contentTypes[$contentTypeName]);
    }

    public function addConfigTypes($configTypes = null)
    {
        if (!$this->getDatabase()) {
            throw new AnyContentClientException('Database must be initalized first.');
        }
        if ($configTypes == null) {
            if ($this->pathCMDLFolderForConfigTypes != null) { // file based content/config types definition
                $finder = new Finder();

                $uri = 'file://' . $this->pathCMDLFolderForConfigTypes;

                if (file_exists($uri)) {
                    $finder->in($uri)->depth(0);

                    foreach ($finder->files()->name('*.cmdl') as $file) {
                        $configTypeName = $file->getBasename('.cmdl');

                        $this->configTypes[$configTypeName] = [];
                    }
                }
            } else // database based content/config types definition
            {
                $repositoryName = $this->getRepositoryName();

                $sql = 'SELECT name, data_type FROM _cmdl_ WHERE repository = ?';

                $rows = $this->getDatabase()->fetchAllSQL($sql, [$repositoryName]);

                foreach ($rows as $row) {
                    if ($row['data_type'] == 'config') {
                        $configTypeName                     = $row['name'];
                        $this->configTypes[$configTypeName] = [];
                    }
                }
            }
        } else {
            foreach ($configTypes as $configTypeName) {
                $this->configTypes[$configTypeName] = [];
            }
        }
    }

    public function removeConfigType($configTypeName)
    {
        unset($this->configTypes[$configTypeName]);
    }

    public function getDatabase(): ?Database
    {
        return $this->database;
    }

    public function setDatabase(Database $database): void
    {
        $this->database = $database;
    }

    public function hasCMDLFolder()
    {
        return (bool)($this->pathCMDLFolderForContentTypes || $this->pathCMDLFolderForConfigTypes);
    }

    public function getPathCMDLFolderForContentTypes()
    {
        return $this->pathCMDLFolderForContentTypes;
    }

    public function getPathCMDLFolderForConfigTypes()
    {
        return $this->pathCMDLFolderForConfigTypes;
    }

    public function apply(AbstractConnection $connection): void
    {
        parent::apply($connection);

        assert($connection instanceof MySQLSchemalessReadOnlyConnection || $connection instanceof MySQLSchemalessReadWriteConnection);

        $connection->setDatabase($this->getDatabase());
    }

    public function createReadOnlyConnection()
    {
        return new MySQLSchemalessReadOnlyConnection($this);
    }

    public function createReadWriteConnection()
    {
        return new MySQLSchemalessReadWriteConnection($this);
    }

    /**
     * Import content/config types from a repositories folder containing cmdl into the _cmdl_ table
     *
     *
     * Will purge existing entries
     *
     * e.g.:
     *
     * repository1
     *    - content1.cmdl
     *    - content2.cmdl
     *    -- config
     *       - config1.cmdl
     * repository2
     *  ...
     */
    public function importCMDL($path)
    {
        $connection = $this->getDatabase()->getConnection();

        // scan cmdl folder for repositories

        $finder1 = new Finder();
        $finder1->depth(0);
        $directories = $finder1->directories()->in($path);

        foreach ($directories as $directory) {
            $repositoryName = $directory->getFilename();

            $sql  = 'DELETE FROM _cmdl_ WHERE repository = ?';
            $stmt = $connection->prepare($sql);
            $stmt->execute([$repositoryName]);

            $path = realpath($directory->getPathname());

            $finder2 = new Finder();
            $finder2->depth(0);

            // transfer content types

            $finder2->files()->name('*.cmdl')->in($path);

            foreach ($finder2 as $file) {
                $contentTypeName = $file->getBasename('.cmdl');
                $cmdl = $file->getContents();

                $sql  = 'INSERT INTO _cmdl_ (repository,data_type,name,cmdl,lastchange_timestamp) VALUES(?,"content",?,?,?)';
                $stmt = $connection->prepare($sql);
                $stmt->execute([$repositoryName, $contentTypeName, $cmdl, time()]);
            }

            // transfer config types

            if (file_exists($path . '/config')) {
                $finder3 = new Finder();
                $finder3->depth(0);
                $finder3->files()->name('*.cmdl')->in($path . '/config');

                foreach ($finder3 as $file) {
                    $configTypeName = $file->getBasename('.cmdl');

                    $cmdl = $file->getContents();

                    $sql  = 'INSERT INTO _cmdl_ (repository,data_type,name,cmdl,lastchange_timestamp) VALUES(?,"config",?,?,?)';
                    $stmt = $connection->prepare($sql);
                    $stmt->execute([$repositoryName, $configTypeName, $cmdl, time()]);
                }
            }
        }
    }
}
