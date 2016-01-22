<?php

namespace AnyContent\Connection;

use AnyContent\AnyContentClientException;
use AnyContent\Client\Config;
use AnyContent\Client\DataDimensions;
use AnyContent\Client\Record;

use AnyContent\Connection\Interfaces\WriteConnection;
use Symfony\Component\Filesystem\Filesystem;

class RecordFilesReadWriteConnection extends RecordFilesReadOnlyConnection implements WriteConnection
{

    public function saveRecord(Record $record, DataDimensions $dataDimensions = null)
    {

        if (!$dataDimensions)
        {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        if ($record->getID() == '')
        {
            $record->setId($this->getNextId($record->getContentTypeName(), $dataDimensions));
            $record->setRevision(1);

            $toBeSavedRecord = $record;
        }
        else
        {
            $mergedRecord = $this->mergeExistingRecord($record, $dataDimensions);

            $mergedRecord->setRevision($mergedRecord->getRevision() + 1);
            $record->setRevision($mergedRecord->getRevision());

            $toBeSavedRecord = $mergedRecord;
        }

        $toBeSavedRecord->setLastChangeUserInfo($this->userInfo);
        $record->setLastChangeUserInfo($this->userInfo);

        $filename = $this->getConfiguration()
                         ->getFolderNameRecords($toBeSavedRecord->getContentTypeName(), $dataDimensions);
        $filename .= '/' . $toBeSavedRecord->getID() . '.json';

        $data = json_encode($toBeSavedRecord, JSON_PRETTY_PRINT);

        $this->stashRecord($toBeSavedRecord, $dataDimensions);

        if (!$this->writeData($filename, $data))
        {
            throw new AnyContentClientException('Error when saving record of content type ' . $record->getContentTypeName());
        }

        return $toBeSavedRecord->getID();
    }


    protected function getNextId($contentTypeName, $dataDimensions)
    {

        $allRecords = $this->getAllRecords($contentTypeName, $dataDimensions);

        $nextId = 1;
        if (count($allRecords) > 0)
        {
            $nextId = max(array_keys($allRecords)) + 1;
        }

        return $nextId;
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

        if (!$dataDimensions)
        {
            $dataDimensions = $this->getCurrentDataDimensions();
        }
        if (!$contentTypeName)
        {
            $contentTypeName = $this->getCurrentContentTypeName();
        }

        $filename = realpath($this->getConfiguration()
                                  ->getFolderNameRecords($contentTypeName, $dataDimensions));
        $filename .= '/' . $recordId . '.json';

        $this->unstashRecord($contentTypeName, $recordId, $dataDimensions);

        if ($this->deleteData($filename))
        {
            return $recordId;
        }

        return false;
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

        $configTypeName = $config->getConfigTypeName();

        $mergedConfig = $this->mergeExistingConfig($config, $dataDimensions);

        $mergedConfig->setRevision($mergedConfig->getRevision() + 1);
        $config->setRevision($mergedConfig->getRevision());
        $mergedConfig->setLastChangeUserInfo($this->userInfo);
        $config->setLastChangeUserInfo($this->userInfo);

        $data = json_encode($mergedConfig, JSON_PRETTY_PRINT);

        if ($this->writeData($this->getConfiguration()->getUriConfig($configTypeName, $dataDimensions), $data))
        {
            return true;

        }
        throw new AnyContentClientException('Error when saving record of config type ' . $configTypeName);
    }


    protected function writeData($fileName, $data)
    {
        $fs = new Filesystem();

        $dir = pathinfo($fileName, PATHINFO_DIRNAME);
        if (!file_exists($dir))
        {
            $fs->mkdir($dir);
        }

        return file_put_contents($fileName, $data);
    }


    protected function deleteData($fileName)
    {
        if (file_exists($fileName))
        {
            return (unlink($fileName));
        }

        return false;
    }
}