<?php

namespace AnyContent\Client;

use CMDL\ContentTypeDefinition;
use CMDL\Parser;
use CMDL\Util;

class CXIO
{

    public static $db = null;


    public static function getDatabase()
    {

        if (!self::$db)
        {

            self::$db = new CXIODatabase();
        }

        return self::$db;
    }


    public static function getMaxTimeshift()
    {
        // roundabout 10 years, equals to 1.1.1980

        return number_format(315532800, 4, '.', '');
    }


    public static function getTimeshiftTimestamp($timeshift = 0)
    {
        if ($timeshift < self::getMaxTimeshift())
        {
            return number_format(microtime(true) - $timeshift, 4, '.', '');
        }

        return $timeshift;
    }


    protected static function getRecordDataStructureFromRow($contentTypeDefinition, $row, $repositoryName, $contentTypeName, $viewName)
    {
        $record               = array();
        $record['id']         = $row['id'];
        $record['properties'] = array();

        $properties = $contentTypeDefinition->getProperties($viewName);
        foreach ($properties as $property)
        {
            $record['properties'][$property] = $row['property_' . $property];
        }
        $record['info']                       = array();
        $record['info']['revision']           = $row['revision'];
        $record['info']['revision_timestamp'] = $row['validfrom_timestamp'];
        $record['info']['hash']               = $row['hash'];

        $record['info']['creation']['timestamp'] = $row['creation_timestamp'];
        $record['info']['creation']['username']  = $row['creation_username'];
        $record['info']['creation']['firstname'] = $row['creation_firstname'];
        $record['info']['creation']['lastname']  = $row['creation_lastname'];

        $record['info']['lastchange']['timestamp'] = $row['lastchange_timestamp'];
        $record['info']['lastchange']['username']  = $row['lastchange_username'];
        $record['info']['lastchange']['firstname'] = $row['lastchange_firstname'];
        $record['info']['lastchange']['lastname']  = $row['lastchange_lastname'];

        $record['info']['position']  = $row['position'];
        $record['info']['parent_id'] = $row['parent_id'];
        $record['info']['level']     = $row['position_level'];

        return $record;
    }



    protected static function countRecords($repositoryName, ContentTypeDefinition $contentTypeDefinition, $workspace = 'default', $language = 'default', $timeshift = 0)
    {

        $db = self::getDatabase();

        $contentTypeName = $contentTypeDefinition->getName();

        $tableName = $repositoryName . '$' . $contentTypeName;

        if ($tableName != Util::generateValidIdentifier($repositoryName) . '$' . Util::generateValidIdentifier($contentTypeName))
        {
            throw new \Exception ('Invalid repository and/or content type name(s).', self::INVALID_NAMES);
        }


        $sql = 'SELECT COUNT(*) AS C FROM ' . $tableName . ' WHERE workspace = ? AND language = ? AND deleted = 0 AND validfrom_timestamp <= ? AND validuntil_timestamp > ? ';

        $timestamp = self::getTimeshiftTimestamp($timeshift);


        $params   = array();
        $params[] = $workspace;
        $params[] = $language;
        $params[] = $timestamp;
        $params[] = $timestamp;


        $row = $db->fetchOneSQL($sql,$params);
        $count = $row['C'];

        $sql  = 'SELECT MAX(validfrom_timestamp) AS T FROM ' . $tableName . ' WHERE workspace = ? AND language = ? AND validfrom_timestamp <= ? AND validuntil_timestamp > ? ';
        $row = $db->fetchOneSQL($sql,$params);
        $timestamp = $row['T'];
        if (!$timestamp)
        {
            $timestamp = 0;
        }

        return array( 'count' => $count, 'lastchange' => $timestamp );
     }

    public static function getCMDL(Client $client, $contentTypeName)
    {
        $db = self::getDatabase();

        $clientUrl      = $client->getUrl();
        $p              = strrpos($clientUrl, '/');
        $repositoryName = substr($clientUrl, $p + 1);

        $sql = 'SELECT * FROM _cmdl_ WHERE repository = ? AND `name` = ? AND data_type=?';

        $row = $db->fetchOneSQL($sql, [ $repositoryName, $contentTypeName, 'content' ]);

        return [ 'cmdl' => $row['cmdl'] ];

        //$request = $client->getGuzzle()->get('content/' . $contentTypeName . '/cmdl');
        //$result  = $request->send()->json();

        return $result;
    }


    public static function getRepositoryInfo(Client $client, $workspace, $language, $timeshift)
    {
        $clientUrl      = $client->getUrl();
        $p              = strrpos($clientUrl, '/');
        $repositoryName = substr($clientUrl, $p + 1);

        $db = self::getDatabase();


        $sql = 'SELECT COUNT(*) AS C FROM _cmdl_ WHERE repository = ?';

        $row = $db->fetchOneSQL($sql, [ $repositoryName ]);

        /*if ($row['C']==0)
        {
            throw new AnyContentClientException();
        }*/



        $result = [ 'content' => [ ], 'config' => [ ], 'files' => true, 'admin' => false ];



        $sql = 'SELECT * FROM _cmdl_ WHERE repository = ? AND data_type=? ORDER BY name';

        $rows = $db->fetchAllSQL($sql, [ $repositoryName, 'content' ]);

        foreach ($rows as $row)
        {
            try
            {
                $parser     = new Parser();
                $definition = $parser->parseCMDLString($row['cmdl'], $row['name']);

                $item = [ ];

                $stats = self::countRecords($repositoryName, $definition, $workspace, $language, $timeshift);

                $item['title']              = $definition->getTitle();
                $item['lastchange_content'] = $stats['lastchange'];
                $item['lastchange_cmdl']    = $row['lastchange_timestamp'];
                $item['count']              = $stats['count'];
                $item['description']        = $definition->getDescription();

                $result['content'][$row['name']] = $item;
            }
            catch (\Exception $e)
            {

            }

        }


        $sql = 'SELECT * FROM _cmdl_ WHERE repository = ? AND data_type=? ORDER BY name';

        $rows = $db->fetchAllSQL($sql, [ $repositoryName, 'config' ]);

        foreach ($rows as $row)
        {
            try
            {
                $parser     = new Parser();
                $definition = $parser->parseCMDLString($row['cmdl'], $row['name']);

                $timestamp = self::getTimeshiftTimestamp($timeshift);
                $tableName = $repositoryName . '$$config';

                $sql      = 'SELECT * FROM ' . $tableName . ' WHERE id = ? AND workspace = ? AND language = ? AND validfrom_timestamp <= ? AND validuntil_timestamp > ?';
                $config = $db->fetchOneSQL($sql,[$row['name'],$workspace,$language,$timestamp,$timestamp]);

                $item = [ ];

                $item['title']              = $definition->getTitle();
                $item['lastchange_config'] = 0;
                if ($config)
                {
                    $item['lastchange_config'] = $config['validfrom_timestamp'];
                }
                $item['lastchange_cmdl']    = $row['lastchange_timestamp'];
                $item['description']        = $definition->getDescription();

                $result['config'][$row['name']] = $item;
            }
            catch (\Exception $e)
            {

            }

        }

        return $result;
    }


    public static function getRecord(Client $client, ContentTypeDefinition $contentTypeDefinition, $id, $workspace = 'default', $viewName = 'default', $language = 'default', $timeshift = 0)
    {
        $db        = self::getDatabase();
        $timestamp = self::getTimeshiftTimestamp($timeshift);

        $clientUrl = $client->getUrl();
        $p         = strrpos($clientUrl, '/');

        $repositoryName  = substr($clientUrl, $p + 1);
        $contentTypeName = $contentTypeDefinition->getName();

        $tableName = $repositoryName . '$' . $contentTypeName;

        $sql = 'SELECT * FROM ' . $tableName . ' WHERE id = ? AND workspace = ? AND language = ? AND deleted = 0 AND validfrom_timestamp <= ? AND validuntil_timestamp > ?';

        $params   = array();
        $params[] = $id;
        $params[] = $workspace;
        $params[] = $language;
        $params[] = $timestamp;
        $params[] = $timestamp;

        $row = $db->fetchOneSQL($sql, $params);

        if (!$row)
        {
            return false;
        }

        $record               = self::getRecordDataStructureFromRow($contentTypeDefinition, $row, $repositoryName, $contentTypeName, $viewName);
        $info                 = array();
        $info['repository']   = $repositoryName;
        $info['content_type'] = $contentTypeName;
        $info['workspace']    = $workspace;
        $info['view']         = $viewName;
        $info['language']     = $language;

        return array( 'info' => $info, 'record' => $record );
    }


    public static function getRecords(Client $client, ContentTypeDefinition $contentTypeDefinition, $workspace = 'default', $viewName = 'default', $language = 'default', $order = 'id', $properties = array(), $limit = null, $page = 1, $filter = null, $subset = null, $timeshift = 0)
    {

    }

}