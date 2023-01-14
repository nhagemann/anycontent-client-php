<?php

declare(strict_types=1);

namespace AnyContent\Client\Util;

class RecordsPager
{
    public static function sliceRecords(array $records, $page, $count)
    {
        $offset = $count * ($page - 1);

        return array_slice($records, $offset, $count, true);
    }
}
