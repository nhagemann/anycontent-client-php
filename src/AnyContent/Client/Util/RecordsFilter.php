<?php

declare(strict_types=1);

namespace AnyContent\Client\Util;

use AnyContent\Client\Record;
use AnyContent\Filter\Interfaces\Filter;
use AnyContent\Filter\PropertyFilter;

class RecordsFilter
{
    /**
     * @param Record[]      $records
     * @param string|Filter $filter
     *
     * @return array
     */
    public static function filterRecords(array $records, $filter)
    {
        if (is_string($filter)) {
            $filter = new PropertyFilter($filter);
        }

        $result = [ ];
        foreach ($records as $record) {
            if ($filter->match($record)) {
                $result[$record->getId()] = $record;
            }
        }

        return $result;
    }
}
