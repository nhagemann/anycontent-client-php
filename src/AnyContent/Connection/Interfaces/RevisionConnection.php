<?php

namespace AnyContent\Connection\Interfaces;

use AnyContent\Client\Config;
use AnyContent\Client\DataDimensions;
use AnyContent\Client\Record;

interface RevisionConnection extends ReadOnlyConnection
{
    /**
     * @param                     $recordId
     * @param null                $contentTypeName
     * @param DataDimensions|null $dataDimensions
     *
     * @return Record[]
     */
    public function getRevisionsOfRecord($recordId, $contentTypeName = null, DataDimensions $dataDimensions = null);

    /**
     * @param                     $configTypeName
     * @param DataDimensions|null $dataDimensions
     *
     * @return Config[]
     */
    public function getRevisionsOfConfig($configTypeName, DataDimensions $dataDimensions = null);

}