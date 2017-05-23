<?php
namespace AnyContent\Connection\Configuration;

use AnyContent\AnyContentClientException;
use AnyContent\Connection\Util\Database;
use AnyContent\Client\DataDimensions;
use AnyContent\Connection\AbstractConnection;
use AnyContent\Connection\ContentArchiveReadOnlyConnection;
use AnyContent\Connection\ContentArchiveReadWriteConnection;
use AnyContent\Connection\MySQLSchemalessReadOnlyConnection;
use AnyContent\Connection\MySQLSchemalessReadWriteConnection;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class MySQLSchemalessConfiguration extends AbstractConfiguration
{

    /** @var  Database */
    protected $database;

    protected $pathCMDLFolderForContentTypes = null;

    protected $pathCMDLFolderForConfigTypes = null;

    protected $repositoryName = null;


    public function initDatabase($host, $dbName, $username, $password, $port = 3306)
    {
        // http://stackoverflow.com/questions/18683471/pdo-setting-pdomysql-attr-found-rows-fails
        $pdo = new \PDO('mysql:host=' . $host . ';port=' . $port . ';dbname=' . $dbName, $username, $password, array( \PDO::MYSQL_ATTR_FOUND_ROWS => true ));

        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $pdo->exec("SET NAMES utf8");

        $this->database = new Database($pdo);

        $this->ensureInfoTablesArePresent();
    }


    public function setCMDLFolder($pathContentTypes, $pathConfigTypes = null)
    {
        $this->pathCMDLFolderForContentTypes = $pathContentTypes;

        if ($pathConfigTypes)
        {
            $this->pathCMDLFolderForConfigTypes = $pathConfigTypes;
        }
        else
        {
            $this->pathCMDLFolderForConfigTypes = $pathContentTypes . '/config';
        }
    }


    /**
     * @return null
     */
    public function getRepositoryName()
    {
        if (!$this->repositoryName)
        {
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
        $stmt->execute(array( '_cmdl_' ));

        if ($stmt->rowCount() == 0)
        {
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

            try
            {
                $stmt->execute();
            }
            catch (\PDOException $e)
            {
                throw new AnyContentClientException('Could not create mandatory table _cmdl_');
            }

        }

        $sql = "Show Tables Like '_counter_'";

        $stmt = $this->getDatabase()->getConnection()->prepare($sql);
        $stmt->execute();

        if ($stmt->rowCount() == 0)
        {
            $sql = <<< TEMPLATE_COUNTERTABLE
CREATE TABLE `_counter_` (
  `repository` varchar(128) NOT NULL DEFAULT '',
  `content_type` varchar(128) NOT NULL DEFAULT '',
  `counter` bigint(20) DEFAULT 0,
  PRIMARY KEY (`repository`,`content_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
TEMPLATE_COUNTERTABLE;

            $stmt = $this->getDatabase()->getConnection()->prepare($sql);

            try
            {
                $stmt->execute();
            }
            catch (\PDOException $e)
            {
                throw new AnyContentClientException('Could not create mandatory table _counter_');
            }
        }




        $sql = "Show Tables Like '_update_'";

        $stmt = $this->getDatabase()->getConnection()->prepare($sql);
        $stmt->execute();

        if ($stmt->rowCount() == 0)
        {
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

            try
            {
                $stmt->execute();
            }
            catch (\PDOException $e)
            {
                throw new AnyContentClientException('Could not create mandatory table _update_');
            }
        }






    }


    public function addContentTypes($contentTypes = null)
    {

        if (!$this->getDatabase())
        {
            throw new AnyContentClientException('Database must be initalized first.');
        }
        if ($contentTypes == null)
        {
            if ($this->pathCMDLFolderForContentTypes != null) // file based content/config types definition
            {

                $finder = new Finder();

                $uri = 'file://' . $this->pathCMDLFolderForContentTypes;

                $finder->in($uri)->depth(0);

                /** @var SplFileInfo $file */
                foreach ($finder->files('*.cmdl') as $file)
                {
                    $contentTypeName = $file->getBasename('.cmdl');

                    $this->contentTypes[$contentTypeName] = [ ];

                }

            }
            else // database based content/config types definition
            {
                $repositoryName = $this->getRepositoryName();

                $sql = 'SELECT name, data_type FROM _cmdl_ WHERE repository = ?';

                $rows = $this->getDatabase()->fetchAllSQL($sql, [ $repositoryName ]);

                foreach ($rows as $row)
                {
                    if ($row['data_type'] == 'content')
                    {
                        $contentTypeName                      = $row['name'];
                        $this->contentTypes[$contentTypeName] = [ ];
                    }
                }
            }
        }
        else
        {
            foreach ($contentTypes as $contentTypeName)
            {
                $this->contentTypes[$contentTypeName] = [ ];
            }
        }
    }


    public function removeContentType($contentTypeName)
    {
        unset ($this->contentTypes[$contentTypeName]);
    }


    public function addConfigTypes($configTypes = null)
    {
        if (!$this->getDatabase())
        {
            throw new AnyContentClientException('Database must be initalized first.');
        }
        if ($configTypes == null)
        {
            if ($this->pathCMDLFolderForConfigTypes != null) // file based content/config types definition
            {

                $finder = new Finder();

                $uri = 'file://' . $this->pathCMDLFolderForConfigTypes;

                $finder->in($uri)->depth(0);

                /** @var SplFileInfo $file */
                foreach ($finder->files('*.cmdl') as $file)
                {
                    $configTypeName = $file->getBasename('.cmdl');

                    $this->configTypes[$configTypeName] = [ ];

                }

            }
            else // database based content/config types definition
            {
                $repositoryName = $this->getRepositoryName();

                $sql = 'SELECT name, data_type FROM _cmdl_ WHERE repository = ?';

                $rows = $this->getDatabase()->fetchAllSQL($sql, [ $repositoryName ]);

                foreach ($rows as $row)
                {
                    if ($row['data_type'] == 'config')
                    {
                        $configTypeName                     = $row['name'];
                        $this->configTypes[$configTypeName] = [ ];
                    }
                }
            }
        }
        else
        {
            foreach ($configTypes as $configTypeName)
            {
                $this->configTypes[$configTypeName] = [ ];
            }
        }
    }

    public function removeConfigType($configTypeName)
    {
        unset ($this->configTypes[$configTypeName]);
    }

    /**
     * @return Database
     */
    public function getDatabase()
    {
        return $this->database;
    }


    /**
     * @param Database $database
     */
    public function setDatabase($database)
    {
        $this->database = $database;
    }


    public function hasCMDLFolder()
    {
        return (boolean)($this->pathCMDLFolderForContentTypes || $this->pathCMDLFolderForConfigTypes);
    }


    /**
     * @return null
     */
    public function getPathCMDLFolderForContentTypes()
    {
        return $this->pathCMDLFolderForContentTypes;
    }


    /**
     * @return null
     */
    public function getPathCMDLFolderForConfigTypes()
    {
        return $this->pathCMDLFolderForConfigTypes;
    }


    public function apply(AbstractConnection $connection)
    {
        parent::apply($connection);

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

}