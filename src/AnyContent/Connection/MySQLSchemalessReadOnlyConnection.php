<?php

namespace AnyContent\Connection;

use AnyContent\AnyContentClientException;

use AnyContent\Client\DataDimensions;
use AnyContent\Client\Record;

use AnyContent\Client\UserInfo;
use AnyContent\Client\Util\TimeShifter;
use AnyContent\Connection\Interfaces\ReadOnlyConnection;
use AnyContent\Connection\Util\Database;

use CMDL\Util;
use KVMLogger\KVMLogger;

class MySQLSchemalessReadOnlyConnection extends AbstractConnection implements ReadOnlyConnection
{

    /** @var  Database */
    protected $database;

    protected $checksContentTypeTableIsUpToDate = [ ];
    protected $checkConfigTypeTableIsPresent = false;


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


    /**
     * @param $contentTypeName
     *
     * @return string
     */
    public function getCMDLForContentType($contentTypeName)
    {
        if ($this->hasContentType($contentTypeName))
        {
            if ($this->getConfiguration()->hasCMDLFolder())
            {
                $path = $this->getConfiguration()
                             ->getPathCMDLFolderForContentTypes() . '/' . $contentTypeName . '.cmdl';
                if (file_exists($path))
                {
                    return file_get_contents($path);
                }

                throw new AnyContentClientException ('Could not fetch cmdl for content type ' . $contentTypeName . ' from ' . $path);
            }
            else
            {
                $sql = 'SELECT cmdl FROM _cmdl_ WHERE repository = ? AND name = ? AND data_type="content"';

                $row = $this->getDatabase()->fetchOneSQL($sql, [ $this->getRepository()->getName(), $contentTypeName ]);

                return $row['cmdl'];
            }

        }

        throw new AnyContentClientException ('Unknown content type ' . $contentTypeName);

    }


    /**
     * @param $configTypeName
     *
     * @return string
     */
    public function getCMDLForConfigType($configTypeName)
    {
        if ($this->getConfiguration()->hasConfigType($configTypeName))
        {
            if ($this->getConfiguration()->hasCMDLFolder())
            {
                $path = $this->getConfiguration()
                             ->getPathCMDLFolderForConfigTypes() . '/' . $configTypeName . '.cmdl';
                if (file_exists($path))
                {
                    return file_get_contents($path);
                }

                throw new AnyContentClientException ('Could not fetch cmdl for config type ' . $configTypeName . ' from ' . $path);
            }
            else
            {
                $sql = 'SELECT cmdl FROM _cmdl_ WHERE repository = ? AND name = ? AND data_type="config"';

                $row = $this->getDatabase()->fetchOneSQL($sql, [ $this->getRepository()->getName(), $configTypeName ]);

                return $row['cmdl'];
            }

        }

        throw new AnyContentClientException ('Unknown config type ' . $configTypeName);

    }


    protected function getContentTypeTableName($contentTypeName, $ensureContentTypeTableIsUpToDate = true)
    {
        $repository = $this->getRepository();

        $tableName = $repository->getName() . '$' . $contentTypeName;

        if ($tableName != Util::generateValidIdentifier($repository->getName()) . '$' . Util::generateValidIdentifier($contentTypeName))
        {
            throw new \Exception ('Invalid repository and/or content type name(s).');
        }

        if ($ensureContentTypeTableIsUpToDate == true)
        {
            $this->ensureContentTypeTableIsUpToDate($contentTypeName, false);
        }

        return $tableName;
    }


    public function ensureContentTypeTableIsUpToDate($contentTypeName)
    {
        if (in_array($contentTypeName, $this->checksContentTypeTableIsUpToDate))
        {
            return true;
        }

        $tableName = $this->getContentTypeTableName($contentTypeName, false);

        $contentTypeDefinition = $this->getContentTypeDefinition($contentTypeName);

        $sql = 'Show Tables Like ?';

        $stmt = $this->getDatabase()->getConnection()->prepare($sql);
        $stmt->execute(array( $tableName ));

        if ($stmt->rowCount() == 0)
        {

            $sql = <<< TEMPLATE_CONTENTTABLE

        CREATE TABLE %s (
          `id` int(11) unsigned NOT NULL,
          `hash` varchar(32) NOT NULL,
          `property_name` varchar(255) DEFAULT NULL,
          `workspace` varchar(255) NOT NULL DEFAULT 'default',
          `language` varchar(255) NOT NULL DEFAULT 'default',
          `property_subtype` varchar(255) DEFAULT NULL,
          `property_status` varchar(255) DEFAULT '1',
          `property_parent` int(11) DEFAULT NULL,
          `property_position` int(11) DEFAULT NULL,
          `parent_id` int(11) DEFAULT NULL,
          `position` int(11) DEFAULT NULL,
          `position_left` int(11) DEFAULT NULL,
          `position_right` int(11) DEFAULT NULL,
          `position_level` int(11) DEFAULT NULL,
          `revision` int(11) DEFAULT NULL,
          `deleted` tinyint(1) DEFAULT '0',
          `creation_timestamp` int(11) DEFAULT NULL,
          `creation_apiuser` varchar(255) DEFAULT NULL,
          `creation_clientip` varchar(255) DEFAULT NULL,
          `creation_username` varchar(255) DEFAULT NULL,
          `creation_firstname` varchar(255) DEFAULT NULL,
          `creation_lastname` varchar(255) DEFAULT NULL,
          `lastchange_timestamp` int(11) DEFAULT NULL,
          `lastchange_apiuser` varchar(255) DEFAULT NULL,
          `lastchange_clientip` varchar(255) DEFAULT NULL,
          `lastchange_username` varchar(255) DEFAULT NULL,
          `lastchange_firstname` varchar(255) DEFAULT NULL,
          `lastchange_lastname` varchar(255) DEFAULT NULL,
          `validfrom_timestamp` varchar(16) DEFAULT NULL,
          `validuntil_timestamp` varchar(16) DEFAULT NULL,
          KEY `id` (`id`),
          KEY `workspace` (`workspace`,`language`),
          KEY `validfrom_timestamp` (`validfrom_timestamp`,`validuntil_timestamp`,`id`,`deleted`)

         ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

TEMPLATE_CONTENTTABLE;

            $sql  = sprintf($sql, $tableName);
            $stmt = $this->getDatabase()->getConnection()->prepare($sql);

            try
            {
                $stmt->execute();
            }
            catch (\PDOException $e)
            {

                throw new AnyContentClientException('Could not create table schema for content type ' . $contentTypeName);
            }

        }

        $sql = sprintf('DESCRIBE %s', $tableName);

        $stmt = $this->getDatabase()->getConnection()->prepare($sql);
        $stmt->execute();

        $fields = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);

        $properties = array();

        foreach ($contentTypeDefinition->getProperties() as $property)
        {
            $properties[] = 'property_' . $property;
        }

        $newfields = array();
        foreach (array_diff($properties, $fields) as $field)
        {
            $newfields[] = 'ADD COLUMN `' . $field . '` LONGTEXT';
        }

        if (count($newfields) != 0)
        {
            $sql = sprintf('ALTER TABLE %s', $tableName);
            $sql .= ' ' . join($newfields, ',');
            $stmt = $this->getDatabase()->getConnection()->prepare($sql);
            try
            {
                $stmt->execute();
            }
            catch (\PDOException $e)
            {

                throw new AnyContentClientException('Could not update table schema for content type ' . $contentTypeName);
            }
        }

        $this->checksContentTypeTableIsUpToDate[] = $contentTypeName;

        return true;
    }


    protected function getConfigTypeTableName($ensureConfigTypeTableIsPresent = true)
    {
        $repository = $this->getRepository();

        $repositoryName = $repository->getName();

        $tableName = $repositoryName . '$$config';

        if ($tableName != Util::generateValidIdentifier($repositoryName) . '$$config')
        {
            throw new AnyContentClientException ('Invalid repository name ' . $repositoryName);
        }

        if ($ensureConfigTypeTableIsPresent == true)
        {
            $this->ensureConfigTypeTableIsPresent();
        }

        return $tableName;
    }


    public function ensureConfigTypeTableIsPresent()
    {
        if ($this->checkConfigTypeTableIsPresent == true)
        {
            return true;
        }

        $tableName = $this->getConfigTypeTableName(false);

        $sql = 'Show Tables Like ?';

        $stmt = $this->getDatabase()->getConnection()->prepare($sql);
        $stmt->execute(array( $tableName ));

        if ($stmt->rowCount() == 0)
        {

            $sql = <<< TEMPLATE_CONFIGTABLE

        CREATE TABLE %s (
          `id` varchar(255) NOT NULL,
          `hash` varchar(32) NOT NULL,
          `workspace` varchar(255) NOT NULL DEFAULT 'default',
          `language` varchar(255) NOT NULL DEFAULT 'default',
          `revision` int(11) DEFAULT NULL,
          `properties` LONGTEXT,
          `lastchange_timestamp` int(11) DEFAULT NULL,
          `lastchange_apiuser` varchar(255) DEFAULT NULL,
          `lastchange_clientip` varchar(255) DEFAULT NULL,
          `lastchange_username` varchar(255) DEFAULT NULL,
          `lastchange_firstname` varchar(255) DEFAULT NULL,
          `lastchange_lastname` varchar(255) DEFAULT NULL,
          `validfrom_timestamp` varchar(16) DEFAULT NULL,
          `validuntil_timestamp` varchar(16) DEFAULT NULL,
          KEY `id` (`id`),
          KEY `workspace` (`workspace`,`language`),
          KEY `validfrom_timestamp` (`validfrom_timestamp`,`validuntil_timestamp`,`id`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

TEMPLATE_CONFIGTABLE;

            $sql  = sprintf($sql, $tableName);
            $stmt = $this->getDatabase()->getConnection()->prepare($sql);

            try
            {

                $stmt->execute();

            }
            catch (\PDOException $e)
            {

                throw new AnyContentClientException('Could not create table  for config types of repository ' . $this->getRepository()
                                                                                                                     ->getName());
            }

        }
        $this->checkConfigTypeTableIsPresent = true;

        return true;
    }


    protected function createRecordFromRow($row, $contentTypeName, DataDimensions $dataDimensions)
    {
        $precalcuate = $this->precalculateCreateRecordFromRow($contentTypeName, $dataDimensions);

        /** @var Record $record */
        $record = $precalcuate['record'];

        $record->setId($row['id']);

        $properties = [ ];

        foreach ($precalcuate['properties'] as $property)
        {
            if (array_key_exists('property_' . $property, $row))
            {
                $properties[$property] = $row['property_' . $property];
            }
        }

        $record->setProperties($properties);

        $record->setRevision($row['revision']);
        $record->setPosition($row['position']);
        $record->setParent($row['parent_id']);
        $record->setLevel($row['position_level']);

        $userInfo = new UserInfo($row['creation_username'], $row['creation_firstname'], $row['creation_lastname'], $row['creation_timestamp']);
        $record->setCreationUserInfo($userInfo);

        $userInfo = new UserInfo($row['lastchange_username'], $row['lastchange_firstname'], $row['lastchange_lastname'], $row['lastchange_timestamp']);
        $record->setLastChangeUserInfo($userInfo);

        return $record;
    }


    protected function precalculateCreateRecordFromRow($contentTypeName, DataDimensions $dataDimensions)
    {
        $key = 'createrecordfromrow' . $contentTypeName . '-' . $dataDimensions->getViewName();
        if (array_key_exists($key, $this->precalculations))
        {
            $precalculate           = $this->precalculations[$key];
            $precalculate['record'] = clone$precalculate['record'];
        }
        else
        {
            $definition = $this->getContentTypeDefinition($contentTypeName);

            $precalculate                = [ ];
            $precalculate['properties']  = $definition->getProperties($dataDimensions->getViewName());
            $precalculate['record']      = $this->getRecordFactory()
                                                ->createRecord($definition, [ ], $dataDimensions->getViewName(), $dataDimensions->getWorkspace(), $dataDimensions->getLanguage());
            $this->precalculations[$key] = $precalculate;
        }

        return $precalculate;
    }


    /**
     * @param null $contentTypeName
     *
     * @return int
     */
    public function countRecords($contentTypeName = null, DataDimensions $dataDimensions = null)
    {
        if ($contentTypeName == null)
        {
            $contentTypeName = $this->getCurrentContentTypeName();
        }

        if ($dataDimensions == null)
        {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        $tableName = $this->getContentTypeTableName($contentTypeName);

        $sql = 'SELECT COUNT(*) AS C FROM ' . $tableName . ' WHERE workspace = ? AND language = ? AND deleted = 0 AND validfrom_timestamp <= ? AND validuntil_timestamp > ?';

        $timestamp = TimeShifter::getTimeshiftTimestamp($dataDimensions->getTimeShift());

        $row = $this->getDatabase()
                    ->fetchOneSQL($sql, [ $dataDimensions->getWorkspace(), $dataDimensions->getLanguage(), $timestamp, $timestamp ]);

        return $row['C'];

    }


    /**
     * @param null $contentTypeName
     *
     * @return Record[]
     */
    public function getAllRecords($contentTypeName = null, DataDimensions $dataDimensions = null)
    {
        if ($contentTypeName == null)
        {
            $contentTypeName = $this->getCurrentContentTypeName();
        }

        if ($dataDimensions == null)
        {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        $tableName = $this->getContentTypeTableName($contentTypeName);

        $sql = 'SELECT * FROM ' . $tableName . ' WHERE workspace = ? AND language = ? AND deleted = 0 AND validfrom_timestamp <= ? AND validuntil_timestamp > ?';

        $timestamp = TimeShifter::getTimeshiftTimestamp($dataDimensions->getTimeShift());

        $rows = $this->getDatabase()
                     ->fetchAllSQL($sql, [ $dataDimensions->getWorkspace(), $dataDimensions->getLanguage(), $timestamp, $timestamp ]);

        $records = [ ];
        foreach ($rows as $row)
        {
            $records[$row['id']] = $this->createRecordFromRow($row, $contentTypeName, $dataDimensions);
        }

        return $records;
    }


    /**
     * @param $recordId
     *
     * @return Record
     */
    public function getRecord($recordId, $contentTypeName = null, DataDimensions $dataDimensions = null)
    {
        if ($contentTypeName == null)
        {
            $contentTypeName = $this->getCurrentContentTypeName();
        }

        if ($dataDimensions == null)
        {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        $tableName = $this->getContentTypeTableName($contentTypeName);

        $sql = 'SELECT * FROM ' . $tableName . ' WHERE id = ? AND workspace = ? AND language = ? AND deleted = 0 AND validfrom_timestamp <= ? AND validuntil_timestamp > ?';

        $timestamp = TimeShifter::getTimeshiftTimestamp($dataDimensions->getTimeShift());

        $rows = $this->getDatabase()
                     ->fetchAllSQL($sql, [ $recordId, $dataDimensions->getWorkspace(), $dataDimensions->getLanguage(), $timestamp, $timestamp ]);

        if (count($rows) == 1)
        {
            return $this->createRecordFromRow(reset($rows), $contentTypeName, $dataDimensions);
        }

        return false;

    }


    /**
     *
     * @return Config
     */
    public function getConfig($configTypeName = null, DataDimensions $dataDimensions = null)
    {
        if ($dataDimensions == null)
        {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        return $this->exportRecord($this->getMultiViewConfig($configTypeName, $dataDimensions), $dataDimensions->getViewName());

    }


    protected function getMultiViewConfig($configTypeName, DataDimensions $dataDimensions)
    {

        $tableName = $this->getConfigTypeTableName();

        $database = $this->getDatabase();

        $timestamp = TimeShifter::getTimeshiftTimestamp($dataDimensions->getTimeShift());

        $sql = 'SELECT * FROM ' . $tableName . ' WHERE id = ? AND workspace = ? AND language = ? AND validfrom_timestamp <= ? AND validuntil_timestamp > ?';

        $rows = $database->fetchAllSQL($sql, [ $configTypeName, $dataDimensions->getWorkspace(), $dataDimensions->getLanguage(), $timestamp, $timestamp ]);

        if (count($rows) == 1)
        {
            $row    = reset($rows);
            $config = $this->createConfigFromRow($row, $configTypeName, $dataDimensions);
        }
        else
        {
            $definition = $this->getConfigTypeDefinition($configTypeName);
            $config     = $this->getRecordFactory()->createConfig($definition);

            KVMLogger::instance('anycontent-connection')
                     ->info('Config ' . $configTypeName . ' not found');
        }

        return $config;
    }


    protected function createConfigFromRow($row, $configTypeName, DataDimensions $dataDimensions)
    {
        $definition = $this->getConfigTypeDefinition($configTypeName);

        $config = $this->getRecordFactory()
                       ->createConfig($definition, [ ], $dataDimensions->getViewName(), $dataDimensions->getWorkspace(), $dataDimensions->getLanguage());

        $multiViewProperties = json_decode($row['properties'], true);
        $properties          = [ ];

        foreach ($definition->getProperties($dataDimensions->getViewName()) as $property)
        {
            if (array_key_exists($property, $multiViewProperties))
            {
                $properties[$property] = $multiViewProperties[$property];
            }
        }

        $config->setProperties($properties);

        $config->setRevision($row['revision']);

        $userInfo = new UserInfo($row['lastchange_username'], $row['lastchange_firstname'], $row['lastchange_lastname'], $row['lastchange_timestamp']);
        $config->setLastChangeUserInfo($userInfo);

        return $config;
    }


    public function getLastModifiedDate($contentTypeName = null, $configTypeName = null, DataDimensions $dataDimensions = null)
    {

        if ($dataDimensions == null)
        {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        $t = 0;

        $configuration = $this->getConfiguration();

        if ($contentTypeName == null && $configTypeName == null)
        {
            foreach ($configuration->getContentTypeNames() as $contentTypeName)
            {
                $t = max($t, $this->getLastModifedDateForContentType($contentTypeName, $dataDimensions));
            }

            foreach ($configuration->getConfigTypeNames() as $configTypeName)
            {
                $t = max($t, $this->getLastModifedDateForConfigType($configTypeName, $dataDimensions));
            }
        }
        elseif ($contentTypeName != null)
        {
            return $this->getLastModifedDateForContentType($contentTypeName, $dataDimensions);
        }
        elseif ($configTypeName != null)
        {
            return $this->getLastModifedDateForConfigType($configTypeName, $dataDimensions);
        }

        return $t;
    }


    protected function getLastModifedDateForContentType($contentTypeName, DataDimensions $dataDimensions)
    {

        $tableName = $this->getContentTypeTableName($contentTypeName);

        $sql = 'SELECT MAX(validfrom_timestamp) AS T FROM ' . $tableName . ' WHERE workspace = ? AND language = ? ';

        $row = $this->getDatabase()
                    ->fetchOneSQL($sql, [ $dataDimensions->getWorkspace(), $dataDimensions->getLanguage() ]);

        $t = $row['T'];

        $t = max($this->getCMDLLastModifiedDate($contentTypeName, null), $t);

        return $t;
    }


    protected function getLastModifedDateForConfigType($configTypeName, DataDimensions $dataDimensions)
    {

        $tableName = $this->getConfigTypeTableName();

        $sql = 'SELECT MAX(validfrom_timestamp) AS T FROM ' . $tableName . ' WHERE id = ? AND workspace = ? AND language = ? ';

        $row = $this->getDatabase()
                    ->fetchOneSQL($sql, [ $configTypeName, $dataDimensions->getWorkspace(), $dataDimensions->getLanguage() ]);

        $t = $row['T'];

        $t = max($this->getCMDLLastModifiedDate(null, $configTypeName), $t);

        return $t;
    }


    public function getCMDLLastModifiedDate($contentTypeName = null, $configTypeName = null)
    {
        $t = 0;

        if ($this->getConfiguration()->hasCMDLFolder())
        {
            if ($contentTypeName == null && $configTypeName == null)
            {
                foreach ($this->getConfiguration()->getContentTypeNames() as $contentTypeName)
                {
                    $uri = $this->getConfiguration()
                                ->getPathCMDLFolderForContentTypes() . '/' . $contentTypeName . '.cmdl';
                    $t   = max((int)@filemtime($uri), $t);
                }

                foreach ($this->getConfiguration()->getConfigTypeNames() as $configTypeName)
                {
                    $uri = $this->getConfiguration()
                                ->getPathCMDLFolderForConfigTypes() . '/' . $configTypeName . '.cmdl';
                    $t   = max((int)@filemtime($uri), $t);
                }
            }
            elseif ($contentTypeName != null)
            {
                $uri = $this->getConfiguration()
                            ->getPathCMDLFolderForContentTypes() . '/' . $contentTypeName . '.cmdl';
                $t   = max((int)@filemtime($uri), $t);
            }
            elseif ($configTypeName != null)
            {
                $uri = $this->getConfiguration()
                            ->getPathCMDLFolderForConfigTypes() . '/' . $configTypeName . '.cmdl';
                $t   = max((int)@filemtime($uri), $t);
            }
        }
        else
        {
            if ($contentTypeName == null && $configTypeName == null)
            {
                $sql = 'SELECT MAX(validfrom_timestamp) AS T FROM _cmdl_ WHERE repository = ?';

                $row = $this->getDatabase()->fetchOneSQL($sql, [ $this->getRepository()->getName() ]);

                $t = $row['T'];
            }
            elseif ($contentTypeName != null)
            {
                $sql = 'SELECT MAX(validfrom_timestamp) AS T FROM _cmdl_ WHERE repository = ? AND name = ? AND data_type="content"';

                $row = $this->getDatabase()->fetchOneSQL($sql, [ $this->getRepository()->getName(), $contentTypeName ]);

                $t = $row['T'];
            }
            elseif ($configTypeName != null)
            {
                $sql = 'SELECT MAX(validfrom_timestamp) AS T FROM _cmdl_ WHERE repository = ? AND name = ? AND data_type="config"';

                $row = $this->getDatabase()->fetchOneSQL($sql, [ $this->getRepository()->getName(), $configTypeName ]);

                $t = $row['T'];
            }
        }

        return $t;
    }

}