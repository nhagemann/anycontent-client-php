<?php

declare(strict_types=1);

namespace AnyContent\Connection\Interfaces;

use AnyContent\Client\Config;
use AnyContent\Client\DataDimensions;
use AnyContent\Client\Record;
use AnyContent\Client\Repository;
use CMDL\ConfigTypeDefinition;
use CMDL\ContentTypeDefinition;
use Symfony\Component\Cache\Adapter\AdapterInterface;

interface ReadOnlyConnection
{
    /**
     * @return string[]
     */
    public function getContentTypeNames();

    /**
     * @return string[]
     */
    public function getConfigTypeNames();

    /**
     * @return array[]
     */
    public function getContentTypeList();

    /**
     * @return array[]
     */
    public function getConfigTypeList();

    /**
     * @return ContentTypeDefinition[]
     */
    public function getContentTypeDefinitions();

    /**
     * @return ConfigTypeDefinition[]
     */
    public function getConfigTypeDefinitions();

    public function getConfigTypeDefinition(string $configTypeName): ConfigTypeDefinition;

    /**
     * @param $contentTypeName
     *
     * @return mixed
     */
    public function hasContentType($contentTypeName);

    /**
     * @param $contentTypeName
     *
     * @return mixed
     */
    public function hasConfigType($contentTypeName);

    /**
     * @param $contentTypeName
     *
     * @return ReadOnlyConnection
     */
    public function selectContentType($contentTypeName);

    public function getContentTypeDefinition(string $contentTypeName): ContentTypeDefinition;

    /**
     * @return ContentTypeDefinition
     */
    public function getCurrentContentTypeDefinition();

    /**
     * @return string
     */
    public function getCurrentContentTypeName();

    /**
     * @param DataDimensions $dataDimensions
     *
     * @return ReadOnlyConnection
     */
    public function setDataDimensions(DataDimensions $dataDimensions);

    /**
     * @return DataDimensions
     */
    public function getCurrentDataDimensions();

    public function countRecords(?string $contentTypeName = null, ?DataDimensions $dataDimensions = null): int;

    /**
     * @param $contentTypeName
     *
     * @return Record[]
     */
    public function getAllRecords($contentTypeName = null, DataDimensions $dataDimensions = null);

    /**
     * @param $recordId
     *
     * @return Record
     */
    public function getRecord(int|string $recordId, ?string $contentTypeName = null, ?DataDimensions $dataDimensions = null): Record|false;

    public function getRecordClassForContentType($contentTypeName);

    /**
     *
     * @return Config
     */
    public function getConfig($configTypeName = null, DataDimensions $dataDimensions = null);

    public function getRecordClassForConfigType($configTypeName);

    public function apply(Repository $repository);

    /**
     * @return Repository
     */
    public function getRepository();

    /**
     * @param Repository $repository
     */
    public function setRepository($repository);

    /**
     * @return bool
     */
    public function hasRepository();

    public function setCacheAdapter(AdapterInterface $cacheAdapter);

    public function enableCMDLCaching($duration = 60, $checkLastModifiedDate = false);

    /**
     * Check for last content/config or cmdl change within repository or for a distinct content/config type
     */
    public function getLastModifiedDate(string $contentTypeName = null, string $configTypeName = null, DataDimensions $dataDimensions = null): float;

    /**
     * Check for last cmdl change within repository or for a distinct content/config type
     *
     * @param $contentTypeName
     * @param $configTypeName
     */
    public function getCMDLLastModifiedDate($contentTypeName = null, $configTypeName = null);
}
