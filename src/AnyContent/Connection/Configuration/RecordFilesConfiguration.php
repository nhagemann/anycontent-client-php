<?php

namespace AnyContent\Connection\Configuration;

use AnyContent\AnyContentClientException;
use AnyContent\Client\DataDimensions;
use AnyContent\Connection\RecordFilesReadOnlyConnection;
use AnyContent\Connection\RecordFilesReadWriteConnection;
use KVMLogger\KVMLogger;
use Symfony\Component\Filesystem\Filesystem;

class RecordFilesConfiguration extends AbstractConfiguration
{
    /**
     * @return RecordFilesConfiguration
     * @throws AnyContentClientException
     */
    public function addContentType($contentTypeName, $filenameCMDL, $folderRecords)
    {
        $fs = new Filesystem();

        if (!$fs->exists($filenameCMDL)) {
            throw new AnyContentClientException('File ' . $filenameCMDL . ' not found.');
        }

        if (!$fs->exists($folderRecords)) {
            KVMLogger::instance('anycontent')->warning('Folder ' . $folderRecords . ' not found.');
        }

        $this->contentTypes[$contentTypeName] = ['records' => $folderRecords, 'cmdl' => $filenameCMDL];

        return $this;
    }

    /**
     *
     * @return $this
     * @throws AnyContentClientException
     */
    public function addConfigType($configTypeName, $filenameCMDL, $filenameRecord)
    {
        $fs = new Filesystem();

        if (!$fs->exists($filenameCMDL)) {
            KVMLogger::instance('anycontent-connection')->info('File ' . $filenameCMDL . ' not found.');
        }

        if (!$fs->exists($filenameRecord)) {
            KVMLogger::instance('anycontent-connection')->info('File ' . $filenameRecord . ' not found.');
        }

        $this->configTypes[$configTypeName] = ['record' => $filenameRecord, 'cmdl' => $filenameCMDL];

        return $this;
    }

    public function getUriCMDLForContentType($contentTypeName)
    {
        if ($this->hasContentType($contentTypeName)) {
            return $this->contentTypes[$contentTypeName]['cmdl'];
        }

        throw new AnyContentClientException('Unknown content type ' . $contentTypeName);
    }

    public function getUriCMDLForConfigType($configTypeName)
    {
        if ($this->hasConfigType($configTypeName)) {
            return $this->configTypes[$configTypeName]['cmdl'];
        }

        throw new AnyContentClientException('Unknown config type ' . $configTypeName);
    }

    public function getFolderNameRecords($contentTypeName, DataDimensions $dataDimensions)
    {
        if ($this->hasContentType($contentTypeName)) {
            return $this->contentTypes[$contentTypeName]['records'];
        }

        throw new AnyContentClientException('Unknown content type ' . $contentTypeName);
    }

    public function getUriConfig($configTypeName, DataDimensions $dataDimensions)
    {
        if ($this->hasConfigType($configTypeName)) {
            return $this->configTypes[$configTypeName]['record'];
        }

        throw new AnyContentClientException('Unknown config type ' . $configTypeName);
    }

    public function createReadOnlyConnection()
    {
        return new RecordFilesReadOnlyConnection($this);
    }

    public function createReadWriteConnection()
    {
        return new RecordFilesReadWriteConnection($this);
    }
}
