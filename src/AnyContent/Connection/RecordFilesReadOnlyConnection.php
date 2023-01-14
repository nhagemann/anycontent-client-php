<?php

declare(strict_types=1);

namespace AnyContent\Connection;

use AnyContent\AnyContentClientException;
use AnyContent\Client\DataDimensions;
use AnyContent\Client\Record;
use AnyContent\Connection\Configuration\ContentArchiveConfiguration;
use AnyContent\Connection\Configuration\RecordFilesConfiguration;
use AnyContent\Connection\Interfaces\ReadOnlyConnection;
use KVMLogger\KVMLogger;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class RecordFilesReadOnlyConnection extends RecordsFileReadOnlyConnection implements ReadOnlyConnection
{
    public function countRecords(?string $contentTypeName = null, ?DataDimensions $dataDimensions = null): int
    {
        if ($contentTypeName == null) {
            $contentTypeName = $this->getCurrentContentTypeName();
        }
        if ($dataDimensions == null) {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        assert($this->getConfiguration() instanceof RecordFilesConfiguration || $this->getConfiguration() instanceof ContentArchiveConfiguration);
        $folder = $this->getConfiguration()->getFolderNameRecords($contentTypeName, $dataDimensions);

        $folder = realpath($folder);

        if ($folder) {
            $finder = new Finder();
            $finder->in($folder)->depth(0);

            return $finder->files()->name('*.json')->count();
        }

        return 0;
    }

    public function getRecord(int|string $recordId, ?string $contentTypeName = null, ?DataDimensions $dataDimensions = null): Record|false
    {
        if ($contentTypeName == null) {
            $contentTypeName = $this->getCurrentContentTypeName();
        }

        if ($dataDimensions == null) {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        assert($this->getConfiguration() instanceof RecordFilesConfiguration || $this->getConfiguration() instanceof ContentArchiveConfiguration);

        $folder = $this->getConfiguration()->getFolderNameRecords($contentTypeName, $dataDimensions);

        $fileName = $folder . '/' . $recordId . '.json';

        if ($this->fileExists($fileName)) {
            $data = $this->readRecord($fileName);

            if ($data) {
                $data = json_decode($data, true);

                $definition = $this->getContentTypeDefinition($contentTypeName);

                $record = $this->getRecordFactory()
                               ->createRecordFromJSON($definition, $data, $dataDimensions->getViewName(), $dataDimensions->getWorkspace(), $dataDimensions->getLanguage());

                $record =  $this->exportRecord($record, $dataDimensions);
                assert($record instanceof Record);
                return $record;
            }
        }

        KVMLogger::instance('anycontent-connection')
                 ->info('Record ' . $recordId . ' not found for content type ' . $this->getCurrentContentTypeName());

        return false;
    }

    /**
     * @param null $contentTypeName
     *
     * @return Record[]
     * @throws AnyContentClientException
     */
    protected function getAllMultiViewRecords($contentTypeName, DataDimensions $dataDimensions)
    {
        assert($this->getConfiguration() instanceof RecordFilesConfiguration || $this->getConfiguration() instanceof ContentArchiveConfiguration);

        $folder = $this->getConfiguration()->getFolderNameRecords($contentTypeName, $dataDimensions);

        if (file_exists($folder)) {
            $finder = new Finder();
            $finder->in($folder)->depth(0);

            $data = [ ];

            /** @var SplFileInfo $file */
            foreach ($finder->files()->name('*.json') as $file) {
                $data[] = json_decode($file->getContents(), true);
            }

            $definition = $this->getContentTypeDefinition($contentTypeName);

            return $this->getRecordFactory()
                            ->createRecordsFromJSONRecordsArray($definition, $data);
        }

        return [ ];
    }

    public function getLastModifiedDate($contentTypeName = null, $configTypeName = null, DataDimensions $dataDimensions = null)
    {
        if ($dataDimensions == null) {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        $t = 0;

        assert($this->getConfiguration() instanceof RecordFilesConfiguration || $this->getConfiguration() instanceof ContentArchiveConfiguration);

        $configuration = $this->getConfiguration();

        if ($contentTypeName == null && $configTypeName == null) {
            foreach ($configuration->getContentTypeNames() as $contentTypeName) {
                $t = max($t, $this->getLastModifedDateForContentType($contentTypeName, $dataDimensions));
            }

            foreach ($configuration->getConfigTypeNames() as $configTypeName) {
                $t = max($t, $this->getLastModifedDateForConfigType($configTypeName, $dataDimensions));
            }
        } elseif ($contentTypeName != null) {
            return $this->getLastModifedDateForContentType($contentTypeName, $dataDimensions);
        } elseif ($configTypeName != null) {
            return $this->getLastModifedDateForConfigType($configTypeName, $dataDimensions);
        }

        return $t;
    }

    protected function getLastModifedDateForContentType($contentTypeName, DataDimensions $dataDimensions)
    {
        $t      = 0;
        assert($this->getConfiguration() instanceof RecordFilesConfiguration || $this->getConfiguration() instanceof ContentArchiveConfiguration);

        $folder = $this->getConfiguration()->getFolderNameRecords($contentTypeName, $dataDimensions);

        if (file_exists($folder)) {
            $finder = new Finder();
            $finder->in($folder)->depth(0)->sort(
                function (SplFileInfo $a, SplFileInfo $b) {
                    return ($b->getMTime() - $a->getMTime());
                }
            );

            $iterator = $finder->getIterator();
            //$file     = reset($iterator);
            $file     = $iterator->current();

            if ($file) {
                $t = max($t, (int)$file->getMTime());
            }
        }

        $uri = $this->getConfiguration()->getUriCMDLForContentType($contentTypeName);
        $t   = max((int)@filemtime($uri), $t);

        return $t;
    }

    protected function getLastModifedDateForConfigType($configTypeName, DataDimensions $dataDimensions)
    {
        $t = 0;

        assert($this->getConfiguration() instanceof RecordFilesConfiguration || $this->getConfiguration() instanceof ContentArchiveConfiguration);

        $uri = $this->getConfiguration()->getUriConfig($configTypeName, $dataDimensions);
        $t   = max((int)@filemtime($uri), $t);

        $uri = $this->getConfiguration()->getUriCMDLForConfigType($configTypeName);
        $t   = max((int)@filemtime($uri), $t);

        return $t;
    }
}
