<?php

declare(strict_types=1);

namespace AnyContent\Connection\Interfaces;

use AnyContent\Client\Config;
use AnyContent\Client\DataDimensions;
use AnyContent\Client\Record;

interface RevisionConnection extends ReadOnlyConnection
{
    /**
     * @param                     $recordId
     * @param                $contentTypeName
     * @param DataDimensions|null $dataDimensions
     *
     * @return Record[]|false
     */
    public function getRevisionsOfRecord($recordId, $contentTypeName = null, DataDimensions $dataDimensions = null): array|false;

    /**
     * @param                     $configTypeName
     * @param DataDimensions|null $dataDimensions
     *
     * @return Config[]|false
     */
    public function getRevisionsOfConfig($configTypeName, DataDimensions $dataDimensions = null): array|false;
}
