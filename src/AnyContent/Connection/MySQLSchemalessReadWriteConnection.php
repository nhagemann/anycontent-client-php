<?php

namespace AnyContent\Connection;

use AnyContent\AnyContentClientException;

use AnyContent\Client\Config;
use AnyContent\Client\DataDimensions;
use AnyContent\Client\Record;

use AnyContent\Client\Util\TimeShifter;
use AnyContent\Connection\Interfaces\WriteConnection;

class MySQLSchemalessReadWriteConnection extends MySQLSchemalessReadOnlyConnection implements WriteConnection
{

    public function saveRecord(Record $record, DataDimensions $dataDimensions = null)
    {

        if ($dataDimensions == null)
        {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        $tableName = $this->getContentTypeTableName($record->getContentTypeName());

        $repositoryName = $this->getRepository()->getName();

        $contentTypeName = $record->getContentTypeName();

        $record = $this->finalizeRecord($record, $dataDimensions);

        $definition = $record->getContentTypeDefinition();

        $mode = 'insert';
        $record->setRevision(1);

        $values              = [ ];
        $values['revision']  = 1;
        $values['workspace'] = $dataDimensions->getWorkspace();
        $values['language']  = $dataDimensions->getLanguage();
        $values['deleted']   = 0;

        if ($record->getId() != '')
        {
            $sql = 'SELECT * FROM ' . $tableName . ' WHERE id = ? AND workspace = ? AND language = ? AND deleted = 0 AND validfrom_timestamp <= ? AND validuntil_timestamp > ?';

            $timestamp = TimeShifter::getTimeshiftTimestamp($dataDimensions->getTimeShift());

            $rows = $this->getDatabase()
                         ->fetchAllSQL($sql, [ $record->getId(), $dataDimensions->getWorkspace(), $dataDimensions->getLanguage(), $timestamp, $timestamp ]);

            if (count($rows) == 1)
            {
                $values             = reset($rows);
                $values['revision'] = $values['revision'] + 1;
                $record->setRevision($values['revision']);
                $mode = 'update';
            }

        }

        if ($mode == 'insert' AND $record->getId() == '')
        {
            // update counter for new record

            $sql = 'INSERT INTO _counter_ (repository,content_type,counter) VALUES (? , ? ,1) ON DUPLICATE KEY UPDATE counter=counter+1;';
            $this->getDatabase()->execute($sql, [ $repositoryName, $contentTypeName ]);

            $sql    = 'SELECT counter FROM _counter_ WHERE repository = ? AND content_type = ?';
            $nextId = $this->getDatabase()->fetchColumnSQL($sql, 0, [ $repositoryName, $contentTypeName ]);

            $record->setId($nextId);

            // make sure counter is always at least greater than the largest id, e.g. if the counter row got deleted

            $sql    = 'SELECT MAX(id)+1 FROM ' . $tableName;
            $nextId = $this->getDatabase()->fetchColumnSQL($sql, 0);

            if ($nextId > $record->getId())
            {
                $record->setId($nextId);

                $sql = 'INSERT INTO _counter_ (repository,content_type,counter) VALUES (? , ? ,?) ON DUPLICATE KEY UPDATE counter=?;';
                $this->getDatabase()->execute($sql, [ $repositoryName, $contentTypeName, $nextId, $nextId ]);

            }
        }

        $values['id'] = $record->getId();

        $timeshiftTimestamp = TimeShifter::getTimeshiftTimestamp();

        if ($mode == 'update')
        {
            // invalidate current revision

            $sql      = 'UPDATE ' . $tableName . ' SET validuntil_timestamp = ? WHERE id = ? AND workspace = ? AND language = ? AND deleted = 0 AND validfrom_timestamp <=? AND validuntil_timestamp >?';
            $params   = array();
            $params[] = $timeshiftTimestamp;
            $params[] = $record->getId();
            $params[] = $dataDimensions->getWorkspace();
            $params[] = $dataDimensions->getLanguage();
            $params[] = $timeshiftTimestamp;
            $params[] = $timeshiftTimestamp;

            $this->getDatabase()->execute($sql, $params);

        }

        if ($mode == 'insert')
        {
            $values['creation_timestamp'] = $timeshiftTimestamp;
            $values['creation_username']  = $this->userInfo->getUsername();
            $values['creation_firstname'] = $this->userInfo->getFirstname();
            $values['creation_lastname']  = $this->userInfo->getLastname();
        }

        $values['lastchange_timestamp'] = $timeshiftTimestamp;
        $values['lastchange_username']  = $this->userInfo->getUsername();
        $values['lastchange_firstname'] = $this->userInfo->getFirstname();
        $values['lastchange_lastname']  = $this->userInfo->getLastname();

        $values['validfrom_timestamp']  = $timeshiftTimestamp;
        $values['validuntil_timestamp'] = TimeShifter::getMaxTimestamp();

        foreach ($definition->getViewDefinition($dataDimensions->getViewName())->getProperties() as $property)
        {
            $values['property_' . $property] = $record->getProperty($property);
        }

        $values['parent_id'] = $record->getParent();
        $values['position']  = $record->getPosition();

        // TODO: Rebuild Nested Set On Change, but only if not within saveRecords
        //        if ($mode == 'update')
        //        {
        //
        //            $values['parent_id']      = $row['parent_id'];
        //            $values['position']       = $row['position'];
        //            $values['position_left']  = $row['position_left'];
        //            $values['position_right'] = $row['position_right'];
        //            $values['position_level'] = $row['position_level'];
        //
        //        }

        $this->getDatabase()->insert($tableName, $values);

        return $record->getId();

    }


    /**
     * @param Record[] $records
     *
     * @return mixed
     * @throws AnyContentClientException
     */
    public function saveRecords(array $records, DataDimensions $dataDimensions = null)
    {

        if (!$dataDimensions)
        {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        $recordIds = [ ];
        foreach ($records as $record)
        {
            $recordIds[] = $this->saveRecord($record, $dataDimensions);
        }

        return $recordIds;

    }


    public function deleteRecord($recordId, $contentTypeName = null, DataDimensions $dataDimensions = null)
    {
        $result = $recordId;

        if ($contentTypeName == null)
        {
            $contentTypeName = $this->getCurrentContentTypeName();
        }

        if (!$dataDimensions)
        {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        $tableName = $this->getContentTypeTableName($contentTypeName);

        $values = [ ];

        // get row of current revision

        $sql = 'SELECT * FROM ' . $tableName . ' WHERE id = ? AND workspace = ? AND language = ? AND deleted = 0 AND validfrom_timestamp <= ? AND validuntil_timestamp > ?';

        $timeshiftTimestamp = TimeShifter::getTimeshiftTimestamp($dataDimensions->getTimeShift());

        $rows = $this->getDatabase()
                     ->fetchAllSQL($sql, [ $recordId, $dataDimensions->getWorkspace(), $dataDimensions->getLanguage(), $timeshiftTimestamp, $timeshiftTimestamp ]);

        if (count($rows) == 1)
        {
            $values             = reset($rows);
            $values['revision'] = $values['revision'] + 1;

        }
        else
        {
            $result = false;
        }

        // invalidate current revision

        $sql = 'UPDATE ' . $tableName . ' SET validuntil_timestamp = ? WHERE id = ? AND workspace = ? AND language = ? AND deleted = 0 AND validfrom_timestamp <=? AND validuntil_timestamp >?';
        $this->getDatabase()
             ->execute($sql, [ $timeshiftTimestamp, $recordId, $dataDimensions->getWorkspace(), $dataDimensions->getLanguage(), $timeshiftTimestamp, $timeshiftTimestamp ]);

        // copy last revision row and mark record as deleted

        $values['deleted']              = 1;
        $values['lastchange_timestamp'] = $timeshiftTimestamp;
        $values['lastchange_username']  = $this->userInfo->getUsername();
        $values['lastchange_firstname'] = $this->userInfo->getFirstname();
        $values['lastchange_lastname']  = $this->userInfo->getLastname();

        $values['validfrom_timestamp']  = $timeshiftTimestamp;
        $values['validuntil_timestamp'] = TimeShifter::getMaxTimestamp();

        $this->getDatabase()->insert($tableName, $values);

        return $result;
    }


    public function deleteRecords(array $recordsIds, $contentTypeName = null, DataDimensions $dataDimensions = null)
    {
        if (!$dataDimensions)
        {
            $dataDimensions = $this->getCurrentDataDimensions();
        }
        if (!$contentTypeName)
        {
            $contentTypeName = $this->getCurrentContentTypeName();
        }

        $recordIds = [ ];
        foreach ($recordsIds as $recordId)
        {
            if ($this->deleteRecord($recordId, $contentTypeName, $dataDimensions))
            {
                $recordIds[] = $recordId;
            }
        }

        return $recordIds;

    }


    public function deleteAllRecords($contentTypeName = null, DataDimensions $dataDimensions = null)
    {
        if (!$dataDimensions)
        {
            $dataDimensions = $this->getCurrentDataDimensions();
        }
        if (!$contentTypeName)
        {
            $contentTypeName = $this->getCurrentContentTypeName();
        }
        $recordIds = [ ];

        $allRecords = $this->getAllRecords($contentTypeName, $dataDimensions);

        foreach ($allRecords as $record)
        {
            if ($this->deleteRecord($record->getId(), $contentTypeName, $dataDimensions))
            {
                $recordIds[] = $record->getId();
            }
        }

        return $recordIds;
    }


    public function saveConfig(Config $config, DataDimensions $dataDimensions = null)
    {
        if (!$dataDimensions)
        {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        $definition = $config->getConfigTypeDefinition();

        $configTypeName = $config->getConfigTypeName();

        $tableName = $this->getConfigTypeTableName();

        $values = [ ];

        $values['id']        = $configTypeName;
        $values['revision']  = 1;
        $values['workspace'] = $dataDimensions->getWorkspace();
        $values['language']  = $dataDimensions->getLanguage();

        // get row of current revision

        $sql = 'SELECT * FROM ' . $tableName . ' WHERE id = ? AND workspace = ? AND language = ? AND validfrom_timestamp <= ? AND validuntil_timestamp > ?';

        $timeshiftTimestamp = TimeShifter::getTimeshiftTimestamp();

        $rows = $this->getDatabase()
                     ->fetchAllSQL($sql, [ $configTypeName, $dataDimensions->getWorkspace(), $dataDimensions->getLanguage(), $timeshiftTimestamp, $timeshiftTimestamp ]);

        if (count($rows) == 1)
        {
            $values             = reset($rows);
            $values['revision'] = $values['revision'] + 1;

            $properties = array_merge(json_decode($values['properties'], true), $config->getProperties());

        }
        else
        {
            $properties = $config->getProperties();
        }

        // invalidate current revision

        $sql = 'UPDATE ' . $tableName . ' SET validuntil_timestamp = ? WHERE id = ? AND workspace = ? AND language = ? AND validfrom_timestamp <=? AND validuntil_timestamp >?';
        $this->getDatabase()
             ->execute($sql, [ $timeshiftTimestamp, $configTypeName, $dataDimensions->getWorkspace(), $dataDimensions->getLanguage(), $timeshiftTimestamp, $timeshiftTimestamp ]);


        $values['properties'] = json_encode($properties);

        $values['lastchange_timestamp'] = $timeshiftTimestamp;
        $values['lastchange_username']  = $this->userInfo->getUsername();
        $values['lastchange_firstname'] = $this->userInfo->getFirstname();
        $values['lastchange_lastname']  = $this->userInfo->getLastname();

        $values['validfrom_timestamp']  = $timeshiftTimestamp;
        $values['validuntil_timestamp'] = TimeShifter::getMaxTimestamp();

        $this->getDatabase()->insert($tableName, $values);

        return true;
    }
}