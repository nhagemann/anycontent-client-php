<?php

declare(strict_types=1);

namespace AnyContent\Client;

use AnyContent\AnyContentClientException;
use AnyContent\Client\Util\RecordsFilter;
use AnyContent\Client\Util\RecordsPager;
use AnyContent\Client\Util\RecordsSorter;
use AnyContent\Connection\Interfaces\AdminConnection;
use AnyContent\Connection\Interfaces\FileManager;
use AnyContent\Connection\Interfaces\FilteringConnection;
use AnyContent\Connection\Interfaces\ReadOnlyConnection;
use AnyContent\Connection\Interfaces\RevisionConnection;
use AnyContent\Connection\Interfaces\WriteConnection;
use CMDL\ConfigTypeDefinition;
use CMDL\ContentTypeDefinition;

class Repository implements FileManager, \JsonSerializable
{
    protected ReadOnlyConnection $readConnection;

    protected ?WriteConnection $writeConnection = null;

    protected ?FileManager $fileManager = null;

    protected DataDimensions $dataDimensions;

    protected UserInfo $userInfo;

    /** identifier */
    protected string $name;

    /** human readable title */
    protected string $title;

    /** url of repository */
    protected ?string $publicUrl = null;

    protected RecordFactory $recordFactory;

    public function __construct(
        $name,
        ReadOnlyConnection $readConnection,
        FileManager $fileManager = null,
        WriteConnection $writeConnection = null
    ) {
        $this->setName($name);

        $this->readConnection = $readConnection;

        $this->readConnection->apply($this);

        if ($writeConnection != null) {
            if ($writeConnection instanceof WriteConnection) {
                $this->writeConnection = $writeConnection;

                $this->writeConnection->apply($this);
            }
        } elseif ($readConnection instanceof WriteConnection) {
            $this->writeConnection = $readConnection;
        }

        $this->userInfo = new UserInfo();

        $this->fileManager = $fileManager;
    }

    public function getReadConnection(): ReadOnlyConnection
    {
        return $this->readConnection;
    }

    public function setReadConnection(ReadOnlyConnection $readConnection): void
    {
        $this->readConnection = $readConnection;
    }

    public function getWriteConnection(): WriteConnection
    {
        return $this->writeConnection;
    }

    public function setWriteConnection(WriteConnection $writeConnection): void
    {
        $this->writeConnection = $writeConnection;
    }

    public function hasFiles(): bool
    {
        return (bool)$this->fileManager;
    }

    public function getFileManager(): ?FileManager
    {
        return $this->fileManager;
    }

    /**
     * @param FileManager $fileManager
     */
    public function setFileManager($fileManager)
    {
        $this->fileManager = $fileManager;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasPublicUrl()
    {
        return (bool)$this->getPublicUrl();
    }

    public function getPublicUrl(): string
    {
        return $this->publicUrl;
    }

    public function setPublicUrl(string $publicUrl)
    {
        $this->publicUrl = $publicUrl;
    }

    public function getContentTypeNames()
    {
        return $this->readConnection->getContentTypeNames();
    }

    public function getConfigTypeNames()
    {
        return $this->readConnection->getConfigTypeNames();
    }

    /**
     * @return \CMDL\ContentTypeDefinition[]
     */
    public function getContentTypeDefinitions()
    {
        return $this->readConnection->getContentTypeDefinitions();
    }

    /**
     * @return \CMDL\ConfigTypeDefinition[]
     */
    public function getConfigTypeDefinitions()
    {
        return $this->readConnection->getConfigTypeDefinitions();
    }

    /**
     * allows to access content type titles without need to parse cmdl definition - if connection supports it
     *
     * @return array[]
     */
    public function getContentTypeList()
    {
        return $this->readConnection->getContentTypeList();
    }

    /**
     *  allows to access config type titles without need to parse cmdl definition - if connection supports it
     *
     * @return array[]
     */
    public function getConfigTypeList()
    {
        return $this->readConnection->getConfigTypeList();
    }

    /**
     * @param $contentTypeName
     *
     * @return bool
     */
    public function hasContentType($contentTypeName)
    {
        return $this->readConnection->hasContentType($contentTypeName);
    }

    /**
     * @param $configTypeName
     *
     * @return bool
     */
    public function hasConfigType($configTypeName)
    {
        return $this->readConnection->hasConfigType($configTypeName);
    }

    /**
     * @throws AnyContentClientException
     */
    public function getContentTypeDefinition(?string $contentTypeName = null): ContentTypeDefinition
    {
        if ($contentTypeName == null) {
            $contentTypeName = $this->getCurrentContentTypeName();
        }

        return $this->readConnection->getContentTypeDefinition($contentTypeName);
    }

    /**
     * @throws AnyContentClientException
     */
    public function getConfigTypeDefinition(string $configTypeName): ConfigTypeDefinition
    {
        return $this->readConnection->getConfigTypeDefinition($configTypeName);
    }

    /**
     *
     * @return \CMDL\ContentTypeDefinition
     * @throws AnyContentClientException
     */
    public function getCurrentContentTypeDefinition()
    {
        return $this->readConnection->getCurrentContentTypeDefinition();
    }

    public function getCurrentContentTypeName()
    {
        return $this->readConnection->getCurrentContentTypeName();
    }

    public function selectContentType($contentTypeName, $resetDataDimensions = false)
    {
        $this->readConnection->selectContentType($contentTypeName);

        if ($resetDataDimensions) {
            $this->reset();
        }

        return $this;
    }

    public function selectView($viewName)
    {
        $this->getCurrentDataDimensions()->setViewName($viewName);

        return $this;
    }

    public function setDataDimensions(DataDimensions $dataDimensions)
    {
        $this->dataDimensions = $dataDimensions;

        return $this;
    }

    public function selectDataDimensions($workspace, $language = null, $timeshift = null)
    {
        $dataDimension = $this->getCurrentDataDimensions();

        $dataDimension->setWorkspace($workspace);
        if ($language !== null) {
            $dataDimension->setLanguage($language);
        }
        if ($timeshift !== null) {
            $dataDimension->setTimeShift($timeshift);
        }

        return $this;
    }

    public function selectWorkspace($workspace)
    {
        $this->getCurrentDataDimensions()->setWorkspace($workspace);

        return $this;
    }

    public function selectLanguage($language)
    {
        $this->getCurrentDataDimensions()->setLanguage($language);

        return $this;
    }

    public function setTimeShift($timeshift)
    {
        $this->getCurrentDataDimensions()->setTimeShift($timeshift);

        return $this;
    }

    /**
     * Reset data dimensions to default values (workspace: default, language: default, view: default, no timeshift)
     *
     * @return $this
     */
    public function reset()
    {
        $this->dataDimensions = new DataDimensions();

        return $this;
    }

    public function getCurrentDataDimensions($decoupled = false)
    {
        if (!isset($this->dataDimensions)) {
            $this->reset();
        }

        if ($decoupled) {
            return clone $this->dataDimensions;
        }

        return $this->dataDimensions;
    }

    /**
     * @return RecordFactory
     */
    public function getRecordFactory()
    {
        if (!isset($this->recordFactory)) {
            $this->recordFactory = new RecordFactory(['validateProperties' => false]);
        }

        return $this->recordFactory;
    }

    public function createRecord($name = '', $recordId = null)
    {
        $record = $this->getRecordFactory()
                       ->createRecord(
                           $this->getContentTypeDefinition(),
                           [],
                           $this->getCurrentDataDimensions()
                                                                                  ->getViewName(),
                           $this->getCurrentDataDimensions()
                                ->getWorkspace(),
                           $this->getCurrentDataDimensions()
                           ->getLanguage()
                       );
        $record->setId($recordId);
        $record->setName($name);

        $userInfo = $this->getCurrentUserInfo();

        $record->setCreationUserInfo($userInfo);
        $record->setLastChangeUserInfo($userInfo);

        return $record;
    }

    public function getRecord(?int $recordId, $dataDimensions = null): Record|false
    {
        if ($recordId === null) {
            return false;
        }
        if ($dataDimensions == null) {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        $record = $this->readConnection->getRecord($recordId, $this->getCurrentContentTypeName(), $dataDimensions);

        if ($record) {
            $record->setRepository($this);
        }

        return $record;
    }

    /**
     *
     * @return Record[]
     */

    /**
     * @return Record[]
     */
    public function getRecords($filter = '', $order = ['.id'], int $page = 1, ?int $count = null, $dataDimensions = null): array
    {
        if ($dataDimensions == null) {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        if (!is_array($order)) {
            $order = [$order];
        }

        if ($this->readConnection instanceof FilteringConnection) {
            $records = $this->readConnection->getRecords(
                $this->getCurrentContentTypeName(),
                $dataDimensions,
                $filter,
                $page,
                $count,
                $order
            );
        } else {
            $records = $this->getAllRecords($dataDimensions);

            if ($filter != '') {
                $records = RecordsFilter::filterRecords($records, $filter);
            }

            $records = RecordsSorter::orderRecords($records, $order);

            if ($count != null) {
                $records = RecordsPager::sliceRecords($records, $page, $count);
            }
        }

        foreach ($records as $record) {
            $record->setRepository($this);
        }

        return $records;
    }

    /**
     * @param                $filter
     * @param                $order
     * @param int            $page
     * @param           $count
     * @param DataDimensions $dataDimensions
     *
     * @return bool|Record
     */
    public function getFirstRecord($filter = '', $order = ['.id'], $page = 1, $count = null, $dataDimensions = null)
    {
        $records = $this->getRecords($filter, $order, $page, $count, $dataDimensions);
        if ($records) {
            return array_shift($records);
        }

        return false;
    }

    protected function getAllRecords($dataDimensions = null)
    {
        if ($dataDimensions == null) {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        return $this->readConnection->getAllRecords($this->getCurrentContentTypeName(), $dataDimensions);
    }

    public function countRecords($filter = '')
    {
        if ($filter == '') {
            $dataDimensions = $this->getCurrentDataDimensions();

            return $this->readConnection->countRecords($this->getCurrentContentTypeName(), $dataDimensions);
        }

        return count($this->getRecords($filter));
    }

    public function getSortedRecords($parentId, $includeParent = false, $depth = null, $height = 0)
    {
        $records = $this->getRecords();

        return RecordsSorter::sortRecords($records, $parentId, $includeParent, $depth, $height);
    }

    public function saveRecord(Record $record)
    {
        if (!$this->writeConnection) {
            throw new AnyContentClientException('Current connection(s) doesn\'t support write operations.');
        }

        $dataDimensions = $this->getCurrentDataDimensions();

        $this->writeConnection->setUserInfo($this->getCurrentUserInfo());

        return $this->writeConnection->saveRecord($record, $dataDimensions);
    }

    public function saveRecords($records)
    {
        if (!$this->writeConnection) {
            throw new AnyContentClientException('Current connection(s) doesn\'t support write operations.');
        }

        $dataDimensions = $this->getCurrentDataDimensions();

        $this->writeConnection->setUserInfo($this->getCurrentUserInfo());

        return $this->writeConnection->saveRecords($records, $dataDimensions);
    }

    public function deleteRecord($recordId)
    {
        if (!$this->writeConnection) {
            throw new AnyContentClientException('Current connection(s) doesn\'t support write operations.');
        }

        $contentTypeName = $this->getCurrentContentTypeName();
        $dataDimensions  = $this->getCurrentDataDimensions();

        return $this->writeConnection->deleteRecord($recordId, $contentTypeName, $dataDimensions);
    }

    public function deleteRecords($recordIds)
    {
        if (!$this->writeConnection) {
            throw new AnyContentClientException('Current connection(s) doesn\'t support write operations.');
        }

        $contentTypeName = $this->getCurrentContentTypeName();
        $dataDimensions  = $this->getCurrentDataDimensions();

        return $this->writeConnection->deleteRecords($recordIds, $contentTypeName, $dataDimensions);
    }

    /**
     * Updates parent and positiong properties of all records of current content type
     *
     * @param array $sorting array [recordId=>parentId]
     */
    public function sortRecords(array $sorting)
    {
        if (!$this->writeConnection) {
            throw new AnyContentClientException('Current connection(s) doesn\'t support write operations.');
        }

        $records = $records = $this->getRecords();
        foreach ($records as $record) {
            $record->setPosition(null);
            $record->setParent(null);
        }

        $positions = [];
        foreach ($sorting as $recordId => $parentId) {
            if (!array_key_exists($parentId, $positions)) {
                $positions[$parentId] = 1;
            }

            $records[$recordId]->setPosition($positions[$parentId]++);
            $records[$recordId]->setParent($parentId);
        }

        return $this->saveRecords($records);
    }

    public function deleteAllRecords()
    {
        if (!$this->writeConnection) {
            throw new AnyContentClientException('Current connection(s) doesn\'t support write operations.');
        }

        $contentTypeName = $this->getCurrentContentTypeName();
        $dataDimensions  = $this->getCurrentDataDimensions();

        return $this->writeConnection->deleteAllRecords($contentTypeName, $dataDimensions);
    }

    public function getConfig($configTypeName)
    {
        $dataDimensions = $this->getCurrentDataDimensions();

        return $this->readConnection->getConfig($configTypeName, $dataDimensions);
    }

    public function saveConfig(Config $config)
    {
        if (!$this->writeConnection) {
            throw new AnyContentClientException('Current connection(s) doesn\'t support write operations.');
        }

        $dataDimensions = $this->getCurrentDataDimensions();

        $this->writeConnection->setUserInfo($this->getCurrentUserInfo());

        return $this->writeConnection->saveConfig($config, $dataDimensions);
    }

    /**
     * @param string $path
     *
     * @return Folder|bool
     */
    public function getFolder($path = '')
    {
        return $this->getFileManager()->getFolder($path);
    }

    /**
     * @param $id
     *
     * @return  File|bool
     */
    public function getFile($fileId)
    {
        return $this->getFileManager()->getFile($fileId);
    }

    public function getBinary(File $file)
    {
        return $this->getFileManager()->getBinary($file);
    }

    public function saveFile($fileId, $binary)
    {
        return $this->getFileManager()->saveFile($fileId, $binary);
    }

    public function deleteFile($fileId, $deleteEmptyFolder = true)
    {
        return $this->getFileManager()->deleteFile($fileId, $deleteEmptyFolder);
    }

    public function createFolder($path)
    {
        return $this->getFileManager()->createFolder($path);
    }

    public function deleteFolder($path, $deleteIfNotEmpty = false)
    {
        return $this->getFileManager()->deleteFolder($path, $deleteIfNotEmpty);
    }

    public function registerRecordClassForContentType($contentTypeName, $classname)
    {
        if ($this->hasContentType($contentTypeName)) {
            $this->getRecordFactory()->registerRecordClassForContentType($contentTypeName, $classname);

            return true;
        }

        return false;
    }

    public function getRecordClassForContentType($contentTypeName)
    {
        return $this->getRecordFactory()->getRecordClassForContentType($contentTypeName);
    }

    public function registerRecordClassForConfigType($configTypeName, $classname)
    {
        if ($this->hasConfigType($configTypeName)) {
            $this->getRecordFactory()->registerRecordClassForConfigType($configTypeName, $classname);

            return true;
        }

        return false;
    }

    public function getRecordClassForConfigType($contentTypeName)
    {
        return $this->getRecordFactory()->getRecordClassForContentType($contentTypeName);
    }

    /**
     * @return UserInfo
     */
    public function getCurrentUserInfo($decoupled = false)
    {
        $this->userInfo->setTimestampToNow();

        if ($decoupled) {
            return clone $this->userInfo;
        }

        return $this->userInfo;
    }

    /**
     * @param UserInfo $userInfo
     */
    public function setUserInfo($userInfo)
    {
        $this->userInfo = $userInfo;
    }

    /**
     * @param                     $recordId
     * @param                $contentTypeName
     *
     * @return Record[]
     */
    public function getRevisionsOfRecord($recordId, $contentTypeName = null)
    {
        if (!$this->readConnection instanceof RevisionConnection) {
            throw new AnyContentClientException('Current connection doesn\'t support revision operations.');
        }

        $contentTypeName = $this->getCurrentContentTypeName();
        $dataDimensions  = $this->getCurrentDataDimensions();

        return $this->readConnection->getRevisionsOfRecord($recordId, $contentTypeName, $dataDimensions);
    }

    /**
     * @param                     $configTypeName
     *
     * @return Config[]
     */
    public function getRevisionsOfConfig($configTypeName)
    {
        if (!$this->readConnection instanceof RevisionConnection) {
            throw new AnyContentClientException('Current connection doesn\'t support revision operations.');
        }

        $dataDimensions = $this->getCurrentDataDimensions();

        return $this->readConnection->getRevisionsOfConfig($configTypeName, $dataDimensions);
    }

    public function getLastModifiedDate(
        $contentTypeName = null,
        $configTypeName = null,
        DataDimensions $dataDimensions = null
    ) {
        if ($dataDimensions == null) {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        if ($this->writeConnection) {
            return $this->writeConnection->getLastModifiedDate($contentTypeName, $configTypeName, $dataDimensions);
        }

        return $this->readConnection->getLastModifiedDate($contentTypeName, $configTypeName, $dataDimensions);
    }

    public function isWritable()
    {
        return (bool)$this->writeConnection;
    }

    public function isAdministrable()
    {
        if ($this->isWritable()) {
            $connection = $this->getWriteConnection();

            if ($connection instanceof AdminConnection) {
                return true;
            }
        }

        return false;
    }

    public function supportsRevisions()
    {
        return $this->readConnection instanceof RevisionConnection;
    }

    public function jsonSerialize(): array
    {
        $repository = [];

        $current = false;

        try {
            $current = $this->getCurrentContentTypeDefinition();
        } catch (AnyContentClientException $e) {
            // content type might have been deleted in the meantime
        }

        $repository['content'] = [];
        foreach ($this->getContentTypeDefinitions() as $definition) {
            $contentTypeName = $definition->getName();

            $this->selectContentType($contentTypeName);

            $repository['content'][$contentTypeName]['title']              = $definition->getTitle();
            $repository['content'][$contentTypeName]['lastchange_content'] = $this->getLastModifiedDate($contentTypeName);
            $repository['content'][$contentTypeName]['lastchange_cmdl']    = $this->getReadConnection()
                                                                                  ->getCMDLLastModifiedDate($contentTypeName);
            $repository['content'][$contentTypeName]['count']              = $this->countRecords();
            $repository['content'][$contentTypeName]['description']        = $definition->getDescription();
        }

        $repository['config'] = [];
        foreach ($this->getConfigTypeDefinitions() as $definition) {
            $configTypeName = $definition->getName();

            $repository['config'][$configTypeName]['title']              = $definition->getTitle();
            $repository['config'][$configTypeName]['lastchange_content'] = $this->getLastModifiedDate(
                null,
                $configTypeName
            );
            $repository['config'][$configTypeName]['lastchange_cmdl']    = $this->getReadConnection()
                                                                                ->getCMDLLastModifiedDate(
                                                                                    null,
                                                                                    $configTypeName
                                                                                );
            $repository['config'][$configTypeName]['description']        = $definition->getDescription();
        }

        $repository['files'] = false;
        if ($this->getFileManager()) {
            $repository['files'] = true;
        }

        $repository ['admin'] = $this->isAdministrable();

        if ($current) {
            try {
                $this->selectContentType($current->getName());
            } catch (AnyContentClientException $e) {
                // content type might have been deleted in the meantime
            }
        }

        return $repository;
    }
}
