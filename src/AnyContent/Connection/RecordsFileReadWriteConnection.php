<?php

namespace AnyContent\Connection;

use AnyContent\AnyContentClientException;
use AnyContent\Client\Config;
use AnyContent\Client\DataDimensions;
use AnyContent\Client\Record;
use AnyContent\Connection\Interfaces\WriteConnection;

class RecordsFileReadWriteConnection extends RecordsFileReadOnlyConnection implements WriteConnection
{
    public function saveRecord(Record $record, DataDimensions $dataDimensions = null)
    {
        if (!$dataDimensions) {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        $records = [$record];

        $recordIds = $this->saveRecords($records, $dataDimensions);

        return array_pop($recordIds);
    }

    /**
     * @param Record[] $records
     *
     * @return mixed
     * @throws AnyContentClientException
     */
    public function saveRecords(array $records, DataDimensions $dataDimensions = null)
    {
        if (count($records) > 0) {
            $record          = reset($records);
            $contentTypeName = $record->getContentTypeName();

            if (!$dataDimensions) {
                $dataDimensions = $this->getCurrentDataDimensions();
            }

            $recordIds  = [ ];
            $allRecords = $this->getAllRecords($contentTypeName, $dataDimensions);

            foreach ($records as $record) {
                $record = $this->finalizeRecord($record, $dataDimensions);

                if ($record->getID() == '') {
                    $nextId = 1;
                    if (count($allRecords) > 0) {
                        $nextId = max(array_keys($allRecords)) + 1;
                    }
                    $record->setID($nextId);
                    $record->setRevision(0);
                }

                $mergedRecord = $this->mergeExistingRecord($record, $dataDimensions);

                $mergedRecord->setRevision($mergedRecord->getRevision() + 1);
                $record->setRevision($mergedRecord->getRevision());
                $mergedRecord->setLastChangeUserInfo($this->userInfo);
                $record->setLastChangeUserInfo($this->userInfo);

                $allRecords[$mergedRecord->getID()] = $mergedRecord;
                $recordIds[]                        = $mergedRecord->getID();
            }

            $data = json_encode(['records' => $allRecords], JSON_PRETTY_PRINT);

            if ($this->writeData($this->getConfiguration()->getUriRecords($contentTypeName), $data)) {
                $this->stashAllRecords($allRecords, $dataDimensions);

                return $recordIds;
            }
            throw new AnyContentClientException('Error when saving records of content type ' . $this->getCurrentContentTypeName());
        }

        return [ ];
    }

    public function deleteRecord($recordId, $contentTypeName = null, DataDimensions $dataDimensions = null)
    {
        if (!$dataDimensions) {
            $dataDimensions = $this->getCurrentDataDimensions();
        }
        if (!$contentTypeName) {
            $contentTypeName = $this->getCurrentContentTypeName();
        }

        $recordIds = $this->deleteRecords([$recordId], $contentTypeName, $dataDimensions);
        if (count($recordIds) == 1) {
            return array_shift($recordIds);
        }

        return false;
    }

    public function deleteRecords(array $recordsIds, $contentTypeName = null, DataDimensions $dataDimensions = null)
    {
        if (!$dataDimensions) {
            $dataDimensions = $this->getCurrentDataDimensions();
        }
        if (!$contentTypeName) {
            $contentTypeName = $this->getCurrentContentTypeName();
        }

        $result = [ ];

        $allRecords = $this->getAllRecords($contentTypeName, $dataDimensions);

        foreach ($recordsIds as $recordId) {
            if (array_key_exists($recordId, $allRecords)) {
                unset($allRecords[$recordId]);

                $result[] = $recordId;
            }
        }

        if (count($result) > 0) {
            $data = json_encode(['records' => $allRecords]);

            if ($this->writeData($this->getConfiguration()->getUriRecords($contentTypeName), $data)) {
                $this->stashAllRecords($allRecords, $dataDimensions);

                return $result;
            }
            throw new AnyContentClientException('Error when deleting records of content type ' . $contentTypeName);
        }

        return $result;
    }

    public function deleteAllRecords($contentTypeName = null, DataDimensions $dataDimensions = null)
    {
        if (!$dataDimensions) {
            $dataDimensions = $this->getCurrentDataDimensions();
        }
        if (!$contentTypeName) {
            $contentTypeName = $this->getCurrentContentTypeName();
        }

        $allRecords = $this->getAllRecords($contentTypeName, $dataDimensions);

        $data = json_encode(['records' => [ ]]);

        if ($this->writeData($this->getConfiguration()->getUriRecords($contentTypeName), $data)) {
            $this->unstashAllRecords($contentTypeName, $dataDimensions, $this->getRecordClassForContentType($this->getCurrentContentTypeName()));

            return array_keys($allRecords);
        }
        throw new AnyContentClientException('Error when deleting records of content type ' . $contentTypeName);
    }

    public function saveConfig(Config $config, DataDimensions $dataDimensions = null)
    {
        if (!$dataDimensions) {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        $config = $this->finalizeRecord($config, $dataDimensions);

        $configTypeName = $config->getConfigTypeName();

        $mergedConfig = $this->mergeExistingConfig($config, $dataDimensions);

        $mergedConfig->setRevision($mergedConfig->getRevision() + 1);
        $config->setRevision($mergedConfig->getRevision());
        $mergedConfig->setLastChangeUserInfo($this->userInfo);
        $config->setLastChangeUserInfo($this->userInfo);

        $data = json_encode($mergedConfig, JSON_PRETTY_PRINT);

        if ($this->writeData($this->getConfiguration()->getUriConfig($configTypeName, $dataDimensions), $data)) {
            return true;
        }
        throw new AnyContentClientException('Error when saving record of config type ' . $configTypeName);
    }

    protected function writeData($fileName, $data)
    {
        return file_put_contents($fileName, $data);
    }
}
