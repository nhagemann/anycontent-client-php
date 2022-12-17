<?php

namespace AnyContent\Client\Util;

class RecordsPager
{
    public static function sliceRecords(array $records, $page, $count)
    {

        $offset = $count * ($page - 1);

        $result = array_slice($records, $offset, $count, true);

        return $result;
    }
}
