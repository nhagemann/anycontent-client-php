<?php

declare(strict_types=1);

namespace AnyContent\Connection;

use AnyContent\AnyContentClientException;
use AnyContent\Client\Config;
use AnyContent\Client\DataDimensions;
use AnyContent\Client\Record;
use AnyContent\Connection\Configuration\ContentArchiveConfiguration;
use AnyContent\Connection\Configuration\RecordFilesConfiguration;
use AnyContent\Connection\Configuration\RecordsFileConfiguration;
use AnyContent\Connection\Configuration\RecordsFileHttpConfiguration;
use AnyContent\Connection\Interfaces\ReadOnlyConnection;

class RecordsFileReadOnlyConnection extends AbstractConnection implements ReadOnlyConnection
{
    public function getCMDLForContentType($contentTypeName)
    {
        // RecordsFileReadOnlyConnection is extended by connections of type RecordFiles, ContentArchive and RecordsFileHTTP
        assert(
            $this->getConfiguration() instanceof RecordsFileConfiguration ||
            $this->getConfiguration() instanceof RecordFilesConfiguration ||
            $this->getConfiguration() instanceof ContentArchiveConfiguration ||
            $this->getConfiguration() instanceof RecordsFileHttpConfiguration
        );

        $fileName = $this->getConfiguration()->getUriCMDLForContentType($contentTypeName);

        return $this->readCMDL($fileName);
    }

    public function getCMDLForConfigType($configTypeName)
    {
        // RecordsFileReadOnlyConnection is extended by connections of type RecordFiles, ContentArchive and RecordsFileHTTP
        assert(
            $this->getConfiguration() instanceof RecordsFileConfiguration ||
            $this->getConfiguration() instanceof RecordFilesConfiguration ||
            $this->getConfiguration() instanceof ContentArchiveConfiguration ||
            $this->getConfiguration() instanceof RecordsFileHttpConfiguration
        );

        $fileName = $this->getConfiguration()->getUriCMDLForConfigType($configTypeName);

        return $this->readCMDL($fileName);
    }

    public function countRecords(?string $contentTypeName = null, ?DataDimensions $dataDimensions = null): int
    {
        return count($this->getAllRecords($contentTypeName, $dataDimensions));
    }

    /**
     * @param $contentTypeName
     *
     * @return Record[]
     * @throws AnyContentClientException
     */
    public function getAllRecords($contentTypeName = null, DataDimensions $dataDimensions = null)
    {
        if ($contentTypeName == null) {
            $contentTypeName = $this->getCurrentContentTypeName();
        }

        if ($dataDimensions == null) {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        // RecordsFileReadOnlyConnection is extended by connections of type RecordFiles, ContentArchive and RecordsFileHTTP
        assert(
            $this->getConfiguration() instanceof RecordsFileConfiguration ||
            $this->getConfiguration() instanceof RecordFilesConfiguration ||
            $this->getConfiguration() instanceof ContentArchiveConfiguration ||
            $this->getConfiguration() instanceof RecordsFileHttpConfiguration
        );

        if ($this->getConfiguration()->hasContentType($contentTypeName)) {
            if ($this->hasStashedAllRecords($contentTypeName, $dataDimensions, $this->getRecordClassForContentType($contentTypeName))) {
                return $this->getStashedAllRecords($contentTypeName, $dataDimensions, $this->getRecordClassForContentType($contentTypeName));
            }
            $records = $this->getAllMultiViewRecords($contentTypeName, $dataDimensions);

            $records = $this->exportRecords($records, $dataDimensions);

            $this->stashAllRecords($records, $dataDimensions);

            return $records;
        }

        throw new AnyContentClientException('Unknown content type ' . $contentTypeName);
    }

    /**
     * @return Record[]
     * @throws AnyContentClientException
     */
    protected function getAllMultiViewRecords(string $contentTypeName, DataDimensions $dataDimensions): array
    {
        // RecordsFileReadOnlyConnection is extended by connections of type RecordFiles, ContentArchive and RecordsFileHTTP
        assert(
            $this->getConfiguration() instanceof RecordsFileConfiguration ||
            $this->getConfiguration() instanceof RecordFilesConfiguration ||
            $this->getConfiguration() instanceof ContentArchiveConfiguration ||
            $this->getConfiguration() instanceof RecordsFileHttpConfiguration
        );

        $data = $this->readRecords($this->getConfiguration()->getUriRecords($contentTypeName));

        if ($data) {
            $data = json_decode($data, true);

            $data['records'] = array_filter($data['records']);

            $definition = $this->getContentTypeDefinition($contentTypeName);

            return $this->getRecordFactory()
                            ->createRecordsFromJSONRecordsArray($definition, $data['records']);
        }

        return [ ];
    }

    public function getRecord(int|string $recordId, ?string $contentTypeName = null, ?DataDimensions $dataDimensions = null): Record|false
    {
        if ($contentTypeName == null) {
            $contentTypeName = $this->getCurrentContentTypeName();
        }

        $records = $this->getAllRecords($contentTypeName, $dataDimensions);

        if (array_key_exists($recordId, $records)) {
            return $records[$recordId];
        }

        return false;
    }

    protected function getMultiViewRecord($recordId, $contentTypeName, DataDimensions $dataDimensions)
    {
        $recordId = (int)$recordId;

        if ($contentTypeName == null) {
            $contentTypeName = $this->getCurrentContentTypeName();
        }

        $records = $this->getAllMultiViewRecords($contentTypeName, $dataDimensions);

        if (array_key_exists($recordId, $records)) {
            return $records[$recordId];
        }

        return false;
    }

    protected function mergeExistingRecord(Record $record, DataDimensions $dataDimensions)
    {
        if ($record->getID() != '') {
            $existingRecord = $this->getMultiViewRecord($record->getId(), $record->getContentTypeName(), $dataDimensions);
            if ($existingRecord) {
                $record->setRevision($existingRecord->getRevision());

                $existingProperties = $existingRecord->getProperties();
                $mergedProperties   = array_merge($existingProperties, $record->getProperties());

                $mergedRecord = clone $record;
                $mergedRecord->setProperties($mergedProperties);

                return $mergedRecord;
            }
        }

        return $record;
    }

    /**
     *
     * @return Config
     */
    public function getConfig($configTypeName = null, DataDimensions $dataDimensions = null)
    {
        if ($dataDimensions == null) {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        return $this->exportRecord($this->getMultiViewConfig($configTypeName, $dataDimensions), $dataDimensions);
    }

    protected function getMultiViewConfig($configTypeName, DataDimensions $dataDimensions)
    {
        $definition = $this->getConfigTypeDefinition($configTypeName);

        // RecordsFileReadOnlyConnection is extended by connections of type RecordFiles, ContentArchive and RecordsFileHTTP
        assert(
            $this->getConfiguration() instanceof RecordsFileConfiguration ||
            $this->getConfiguration() instanceof RecordFilesConfiguration ||
            $this->getConfiguration() instanceof ContentArchiveConfiguration ||
            $this->getConfiguration() instanceof RecordsFileHttpConfiguration
        );

        $data = $this->readConfig($this->getConfiguration()->getUriConfig($configTypeName, $dataDimensions));

        if ($data) {
            $data = json_decode($data, true);

            $config = $this->getRecordFactory()->createConfig($definition, $data['properties']);
            if (isset($data['info']['revision'])) {
                $config->setRevision($data['info']['revision']);
            }
        } else {
            $config = $this->getRecordFactory()->createConfig($definition);
        }

        return $config;
    }

    protected function mergeExistingConfig(Config $config, DataDimensions $dataDimensions)
    {
        $configTypeName = $config->getConfigTypeName();

        $existingConfig = $this->getMultiViewConfig($configTypeName, $dataDimensions);
        if ($existingConfig) {
            $config->setRevision($existingConfig->getRevision());

            $existingProperties = $existingConfig->getProperties();
            $mergedProperties   = array_merge($existingProperties, $config->getProperties());

            $mergedRecord = clone $config;
            $mergedRecord->setProperties($mergedProperties);

            return $mergedRecord;
        }

        return $config;
    }

    protected function fileExists($filename)
    {
        return file_exists($filename);
    }

    protected function readData($fileName)
    {
        if ($this->fileExists($fileName)) {
            return file_get_contents($fileName);
        }
        return false;
    }

    protected function readCMDL($filename)
    {
        return $this->readData($filename);
    }

    protected function readRecord($filename)
    {
        return $this->readData($filename);
    }

    protected function readConfig($filename)
    {
        return $this->readData($filename);
    }

    protected function readRecords($filename)
    {
        return $this->readData($filename);
    }

    public function getLastModifiedDate(string $contentTypeName = null, string $configTypeName = null, DataDimensions $dataDimensions = null): float
    {
        $t = 0;

        // RecordsFileReadOnlyConnection is extended by connections of type RecordFiles, ContentArchive and RecordsFileHTTP
        assert(
            $this->getConfiguration() instanceof RecordsFileConfiguration ||
            $this->getConfiguration() instanceof RecordFilesConfiguration ||
            $this->getConfiguration() instanceof ContentArchiveConfiguration ||
            $this->getConfiguration() instanceof RecordsFileHttpConfiguration
        );

        $configuration = $this->getConfiguration();

        if ($contentTypeName == null && $configTypeName == null) {
            foreach ($configuration->getContentTypeNames() as $contentTypeName) {
                $uri = $configuration->getUriCMDLForContentType($contentTypeName);

                $t = max((int)@filemtime($uri), $t);

                $uri = $configuration->getUriRecords($contentTypeName);

                $t = max((int)@filemtime($uri), $t);
            }
            foreach ($configuration->getConfigTypeNames() as $configTypeName) {
                $uri = $configuration->getUriCMDLForConfigType($configTypeName);

                $t = max((int)@filemtime($uri), $t);

                $uri = $configuration->getUriConfig($configTypeName, $dataDimensions);

                $t = max((int)@filemtime($uri), $t);
            }
        } elseif ($contentTypeName != null) {
            $uri = $configuration->getUriCMDLForContentType($contentTypeName);

            $t = max((int)@filemtime($uri), $t);

            $uri = $configuration->getUriRecords($contentTypeName);

            $t = max((int)@filemtime($uri), $t);
        } elseif ($configTypeName != null) {
            $uri = $configuration->getUriCMDLForConfigType($configTypeName);

            $t = max((int)@filemtime($uri), $t);

            $uri = $configuration->getUriConfig($configTypeName, $dataDimensions);

            $t = max((int)@filemtime($uri), $t);
        }

        return (float)$t;
    }

    public function getCMDLLastModifiedDate($contentTypeName = null, $configTypeName = null): float
    {
        $t = 0;

        // RecordsFileReadOnlyConnection is extended by connections of type RecordFiles, ContentArchive and RecordsFileHTTP
        assert(
            $this->getConfiguration() instanceof RecordsFileConfiguration ||
            $this->getConfiguration() instanceof RecordFilesConfiguration ||
            $this->getConfiguration() instanceof ContentArchiveConfiguration ||
            $this->getConfiguration() instanceof RecordsFileHttpConfiguration
        );

        $configuration = $this->getConfiguration();

        if ($contentTypeName == null && $configTypeName == null) {
            foreach ($configuration->getContentTypeNames() as $contentTypeName) {
                $uri = $configuration->getUriCMDLForContentType($contentTypeName);

                $t = max((int)@filemtime($uri), $t);
            }
            foreach ($configuration->getConfigTypeNames() as $configTypeName) {
                $uri = $configuration->getUriCMDLForConfigType($configTypeName);

                $t = max((int)@filemtime($uri), $t);
            }
        } elseif ($contentTypeName != null) {
            $uri = $configuration->getUriCMDLForContentType($contentTypeName);

            $t = max((int)@filemtime($uri), $t);
        } elseif ($configTypeName != null) {
            $uri = $configuration->getUriCMDLForConfigType($configTypeName);

            $t = max((int)@filemtime($uri), $t);
        }

        return (float)$t;
    }
}
