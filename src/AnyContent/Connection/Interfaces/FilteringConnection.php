<?php

declare(strict_types=1);

namespace AnyContent\Connection\Interfaces;

use AnyContent\Client\DataDimensions;
use AnyContent\Client\Record;

interface FilteringConnection
{
    /**
     * @return Record[]
     */
    public function getRecords($contentTypeName, DataDimensions $dataDimensions, $filter, $page = 1, $count = null, $order = ['.id']);
}
