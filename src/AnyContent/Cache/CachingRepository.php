<?php

declare(strict_types=1);

namespace AnyContent\Cache;

use AnyContent\Client\Config;
use AnyContent\Client\File;
use AnyContent\Client\Folder;
use AnyContent\Client\Record;
use AnyContent\Client\Repository;
use AnyContent\Client\Util\RecordsSorter;
use AnyContent\Filter\Interfaces\Filter;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class CachingRepository extends Repository
{
    /**
     * Items are cached with last modified date of content/config type. Cache doesn't have to be flushed, but last
     * modified dates must be retrieved regulary.
     */
    public const CACHE_STRATEGY_LASTMODIFIED = 1;

    /**
     * Every save operation leads to a full flash of the cache. Very fast, if you don't have too much
     * write operations. Only eventually consistent, if you have more than one writing client connecting to
     * your repositories.
     */
    public const CACHE_STRATEGY_EXPIRATION = 2;

    protected $cacheStrategy = self::CACHE_STRATEGY_EXPIRATION;

    protected $duration = 300;

    protected AdapterInterface $cacheAdapter;

    protected $cmdlCaching = false;

    protected $singleContentRecordCaching = true;

    protected $allContentRecordsCaching = true;

    protected $contentQueryRecordsCaching = true;

    protected $lastModified = 0;

    public function getCacheAdapter(): AdapterInterface
    {
        if (!isset($this->cacheAdapter)) {
            $this->cacheAdapter = new ArrayAdapter();
        }
        return $this->cacheAdapter;
    }

    public function setCacheAdapter(AdapterInterface $cacheAdapter): void
    {
        $this->cacheAdapter = $cacheAdapter;
    }

    public function selectExpirationCacheStrategy($duration = 300)
    {
        $this->cacheStrategy = self::CACHE_STRATEGY_EXPIRATION;
        $this->duration = $duration;

        if ($this->isCmdlCaching()) { // reflect strategy change within cmdl caching
            $this->enableCmdlCaching($this->cmdlCaching);
        }
    }

    public function selectLastModifiedCacheStrategy($duration = 300)
    {
        $this->cacheStrategy = self::CACHE_STRATEGY_LASTMODIFIED;
        $this->duration = $duration;

        if ($this->isCmdlCaching()) { // reflect strategy change within cmdl caching
            $this->enableCmdlCaching($this->cmdlCaching);
        }
    }

    public function hasLastModifiedCacheStrategy()
    {
        return $this->cacheStrategy == self::CACHE_STRATEGY_LASTMODIFIED ? true : false;
    }

    public function hasExpirationCacheStrategy()
    {
        return $this->cacheStrategy == self::CACHE_STRATEGY_EXPIRATION ? true : false;
    }

    /**
     * @return boolean
     */
    public function isCmdlCaching()
    {
        return $this->cmdlCaching;
    }

    /**
     * Allow connection to cache CMDL definitions. Adjustable via duration if you're not sure how likely CMDL changes occur.
     *
     * @param $duration
     */
    public function enableCmdlCaching($duration = null)
    {
        if ($duration == null) {
            $duration = $this->duration;
        }
        $this->cmdlCaching = $duration;

        $checkLastModifiedDate = false;
        if ($this->hasLastModifiedCacheStrategy()) {
            $checkLastModifiedDate = true;
        }

        $this->readConnection->enableCMDLCaching($duration, $checkLastModifiedDate);
        if ($this->writeConnection) {
            $this->writeConnection->enableCMDLCaching($duration, $checkLastModifiedDate);
        }
    }

    /**
     * @return boolean
     */
    public function isSingleContentRecordCaching()
    {
        return $this->singleContentRecordCaching;
    }

    public function enableSingleContentRecordCaching($duration = null)
    {
        if ($duration == null) {
            $duration = $this->duration;
        }
        $this->singleContentRecordCaching = $duration;
    }

    public function disableSingleContentRecordCaching(): void
    {
        $this->singleContentRecordCaching = false;
    }

    /**
     * @return boolean
     */
    public function isAllContentRecordsCaching()
    {
        return $this->allContentRecordsCaching;
    }

    public function enableAllContentRecordsCaching($duration = null)
    {
        if ($duration == null) {
            $duration = $this->duration;
        }
        $this->allContentRecordsCaching = $duration;
    }

    public function disableAllContentsRecordsCaching(): void
    {
        $this->allContentRecordsCaching = false;
    }

    /**
     * @return boolean
     */
    public function isContentQueryRecordsCaching()
    {
        return $this->contentQueryRecordsCaching;
    }

    public function enableContentQueryRecordsCaching($duration = null)
    {
        if ($duration == null) {
            $duration = $this->duration;
        }
        $this->contentQueryRecordsCaching = $duration;
    }

    public function disableContentQueryRecordsCaching(): void
    {
        $this->contentQueryRecordsCaching = false;
    }

    protected function createCacheKey($realm, array $params, $dataDimensions)
    {
        $cacheKey = '[' . $this->getName() . '][' . $realm . '][' . join(
            ';',
            $params
        ) . '][' . (string)$dataDimensions . ']';

        $cacheKey = str_replace(':', '>', $cacheKey);

        if ($this->hasLastModifiedCacheStrategy()) {
            $cacheKey = '[' . $this->getLastModifiedDate() . ']' . $cacheKey;
        }

        return $cacheKey;
    }

    protected function flushCacheBeforeChange()
    {
        if ($this->hasExpirationCacheStrategy()) {
            $this->getCacheAdapter()->clear();
        } else {
            $this->lastModified = $this->getLastModifiedDate();
        }
    }

    protected function flushCacheAfterChange()
    {
        if ($this->hasLastModifiedCacheStrategy()) {
            if ($this->lastModified == $this->getLastModifiedDate()) { // clear cache, if last modified date hasn't change, otherwise old values could be retrieved accidentially
                $this->getCacheAdapter()->clear();
            }
        }
    }

    /**
     * @param $recordId
     *
     * @return Record
     */
    public function getRecord(?int $recordId, $dataDimensions = null)
    {
        if ($dataDimensions == null) {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        if ($this->isSingleContentRecordCaching()) {
            $cacheKey = $this->createCacheKey(
                'record',
                [$this->getCurrentContentTypeName(), $recordId],
                $dataDimensions
            );

            $cacheItem = $this->getCacheAdapter()->getItem($cacheKey);

            if ($cacheItem->isHit()) {
                $data = json_decode($cacheItem->get(), true);
                $recordFactory = $this->getRecordFactory();
                $record = $recordFactory->createRecordFromJSON($this->getCurrentContentTypeDefinition(), $data);

                $record->setLanguage($dataDimensions->getLanguage());
                $record->setWorkspace($dataDimensions->getWorkspace());
                $record->setViewName($dataDimensions->getViewName());

                $record->setRepository($this);

                return $record;
            }

            $record = parent::getRecord($recordId, $dataDimensions);

            if ($record) {
                $data = json_encode($record);

                $cacheItem = $this->getCacheAdapter()->getItem($cacheKey);
                $cacheItem->set($data);
                $cacheItem->expiresAfter($this->duration);

                $this->getCacheAdapter()->save($cacheItem);
            }

            return $record;
        }

        return parent::getRecord($recordId, $dataDimensions);
    }

    /**
     *
     * @return Record[]
     */

    /**
     * @param string|Filter $filter
     * @param int $page
     * @param null $count
     * @param string|array $order
     *
     * @return Record[]
     */
    public function getRecords($filter = '', $order = ['.id'], $page = 1, $count = null, $dataDimensions = null)
    {
        if ($dataDimensions == null) {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        $caching = false;

        if ($this->isContentQueryRecordsCaching() && ($filter != '' || $count != null)) {
            $caching = true;
        }
        if ($this->isAllContentRecordsCaching() && ($filter == null && $count == null)) {
            $caching = true;
        }

        if ($caching) {
            if (!is_array($order)) {
                $order = [$order];
            }

            $cacheKey = $this->createCacheKey(
                'records-query',
                [$this->getCurrentContentTypeName(), $filter, $page, $count, join(',', $order)],
                $dataDimensions
            );

            $cacheItem = $this->getCacheAdapter()->getItem($cacheKey);
            if ($cacheItem->isHit()) {
                $data = json_decode($cacheItem->get(), true);

                $recordFactory = $this->getRecordFactory();
                $records = $recordFactory->createRecordsFromJSONRecordsArray(
                    $this->getCurrentContentTypeDefinition(),
                    $data
                );

                foreach ($records as $record) {
                    $record->setRepository($this);
                    $record->setLanguage($dataDimensions->getLanguage());
                    $record->setWorkspace($dataDimensions->getWorkspace());
                    $record->setViewName($dataDimensions->getViewName());
                }

                return $records;
            }

            $records = parent::getRecords($filter, $order, $page, $count, $dataDimensions);

            $data = json_encode($records);

            $cacheItem = $this->getCacheAdapter()->getItem($cacheKey);
            $cacheItem->set($data);
            $cacheItem->expiresAfter($this->duration);
            $this->getCacheAdapter()->save($cacheItem);

            return $records;
        }

        return parent::getRecords($filter, $order, $page, $count, $dataDimensions);
    }

    protected function getAllRecords($dataDimensions = null)
    {
        if ($dataDimensions == null) {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        if ($this->isAllContentRecordsCaching()) {
            $cacheKey = $this->createCacheKey(
                'records-query',
                [$this->getCurrentContentTypeName(), '', 1, null, '.id'],
                $dataDimensions
            );

            $cacheItem = $this->getCacheAdapter()->getItem($cacheKey);
            if ($cacheItem->isHit()) {
                $data = json_decode($cacheItem->get(), true);

                $recordFactory = $this->getRecordFactory();
                $records = $recordFactory->createRecordsFromJSONRecordsArray(
                    $this->getCurrentContentTypeDefinition(),
                    $data
                );

                foreach ($records as $record) {
                    $record->setRepository($this);
                    $record->setLanguage($dataDimensions->getLanguage());
                    $record->setWorkspace($dataDimensions->getWorkspace());
                    $record->setViewName($dataDimensions->getViewName());
                }

                return $records;
            }

            $records = parent::getAllRecords($dataDimensions);

            $data = json_encode($records);

            $cacheItem = $this->getCacheAdapter()->getItem($cacheKey);
            $cacheItem->set($data);
            $cacheItem->expiresAfter($this->duration);
            $this->getCacheAdapter()->save($cacheItem);

            return $records;
        }

        return parent::getAllRecords($dataDimensions);
    }

    public function countRecords($filter = '')
    {
        return parent::countRecords($filter);
    }

    public function getSortedRecords($parentId, $includeParent = false, $depth = null, $height = 0)
    {
        if ($this->isContentQueryRecordsCaching()) {
            $dataDimensions = $this->getCurrentDataDimensions();

            $cacheKey = $this->createCacheKey(
                'records-sort',
                [$this->getCurrentContentTypeName(), $parentId, (int)$includeParent, serialize($depth), $height],
                $dataDimensions
            );

            $cacheItem = $this->getCacheAdapter()->getItem($cacheKey);
            if ($cacheItem->isHit()) {
                $data = json_decode($cacheItem->get(), true);

                $recordFactory = $this->getRecordFactory();
                $records = $recordFactory->createRecordsFromJSONRecordsArray(
                    $this->getCurrentContentTypeDefinition(),
                    $data
                );

                RecordsSorter::sortRecords($records, $parentId, $includeParent, $depth, $height);

                foreach ($records as $record) {
                    $record->setRepository($this);
                }

                return $records;
            }

            $records = parent::getSortedRecords($parentId, $includeParent, $depth, $height);

            $data = json_encode($records);

            $cacheItem = $this->getCacheAdapter()->getItem($cacheKey);
            $cacheItem->set($data);
            $cacheItem->expiresAfter($this->duration);
            $this->getCacheAdapter()->save($cacheItem);

            return $records;
        }

        return parent::getSortedRecords($parentId, $includeParent, $depth, $height);
    }

    public function saveRecord(Record $record)
    {
        $this->flushCacheBeforeChange();

        $result = parent::saveRecord($record);

        $this->flushCacheAfterChange();

        return $result;
    }

    public function saveRecords($records)
    {
        $this->flushCacheBeforeChange();

        $result = parent::saveRecords($records);

        $this->flushCacheAfterChange();

        return $result;
    }

    public function deleteRecord($recordId)
    {
        $this->flushCacheBeforeChange();

        $result = parent::deleteRecord($recordId);

        $this->flushCacheAfterChange();

        return $result;
    }

    public function deleteRecords($recordIds)
    {
        $this->flushCacheBeforeChange();

        $result = parent::deleteRecords($recordIds);
        $this->flushCacheAfterChange();

        return $result;
    }

    /**
     * Updates parent and positiong properties of all records of current content type
     *
     * @param array $sorting array [recordId=>parentId]
     */
    public function sortRecords(array $sorting)
    {
        $this->flushCacheBeforeChange();

        $result = parent::sortRecords($sorting);
        $this->flushCacheAfterChange();

        return $result;
    }

    public function deleteAllRecords()
    {
        $this->flushCacheBeforeChange();

        $result = parent::deleteAllRecords();
        $this->flushCacheAfterChange();

        return $result;
    }

    public function getConfig($configTypeName)
    {
        $dataDimensions = $this->getCurrentDataDimensions();

        $cacheKey = $this->createCacheKey(
            'config',
            [$configTypeName],
            $dataDimensions
        );

        $cacheItem = $this->getCacheAdapter()->getItem($cacheKey);
        if ($cacheItem->isHit()) {
            $data = json_decode($cacheItem->get(), true);

            $recordFactory = $this->getRecordFactory();

            $config = $recordFactory->createRecordFromJSON(
                $this->getConfigTypeDefinition($configTypeName),
                $data,
                $dataDimensions->getViewName(),
                $dataDimensions->getWorkspace(),
                $dataDimensions->getLanguage()
            );

            $config->setRepository($this);
            return $config;
        }

        $config = parent::getConfig($configTypeName);

        if ($config) {
            $data = json_encode($config);

            $cacheItem = $this->getCacheAdapter()->getItem($cacheKey);
            $cacheItem->set($data);
            $cacheItem->expiresAfter($this->duration);
            $this->getCacheAdapter()->save($cacheItem);
        }

        $config->setRepository($this);
        return $config;
    }

    public function saveConfig(Config $config)
    {
        $this->flushCacheBeforeChange();

        $result = parent::saveConfig($config);

        $this->flushCacheAfterChange();

        return $result;
    }

    /**
     * @param string $path
     *
     * @return Folder|bool
     */
    public function getFolder($path = '')
    {
        return parent::getFolder($path);
    }

    /**
     * @param $id
     *
     * @return File|bool
     */
    public function getFile($fileId)
    {
        return parent::getFile($fileId);
    }

    public function saveFile($fileId, $binary)
    {
        return parent::saveFile($fileId, $binary);
    }

    public function deleteFile($fileId, $deleteEmptyFolder = true)
    {
        return parent::deleteFile($fileId, $deleteEmptyFolder);
    }

    public function createFolder($path)
    {
        return parent::createFolder($path);
    }

    public function deleteFolder($path, $deleteIfNotEmpty = false)
    {
        return parent::deleteFolder($path, $deleteIfNotEmpty);
    }
}
