<?php

namespace AnyContent\Client\Util;

use AnyContent\Client\Record;
use AnyContent\Client\Repository;

class MenuBuilder
{

    public static function getBreadcrumb(Repository $repository, $contentTypeName, $recordId)
    {
        $repository->selectContentType($contentTypeName);

        return $repository->getSortedRecords($recordId, true, 0, 99);
    }


    public static function getExpandedMenu(Repository $repository, $contentTypeName, $recordId)
    {

        $path = self::getBreadcrumb($repository, $contentTypeName, $recordId);

        $repository->selectContentType($contentTypeName);

        $result = [ ];


        // Add all same level pages within path

        /**
         * @var int    $id
         * @var Record $record
         */
        foreach ($path as $id => $record)
        {
            $records = $repository->getSortedRecords($record->getParent(), false, 1);
            foreach ($records as $record)
            {
                $result[$record->getId()] = $record;

            }
        }

        // Add children

        $records = $repository->getSortedRecords($id, false, 1);
        foreach ($records as $record)
        {
            $result[$record->getId()] = $record;

        }


        return RecordsSorter::sortRecords($result);

    }
}