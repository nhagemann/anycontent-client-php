<?php

namespace AnyContent\Client\Util;

use AnyContent\Client\Record;
use AnyContent\Client\Repository;

class MenuBuilder
{
    public static function getBreadcrumb(Repository $repository, $contentTypeName, $recordId): array
    {
        $repository->selectContentType($contentTypeName);

        return $repository->getSortedRecords($recordId, true, 0, 99);
    }

    /**
     * @param Repository $repository
     * @param $contentTypeName
     * @param $recordId
     * @param array $options currently only one option available: levels = array containing menu levels, that should get expanded
     * @return Record[]
     */
    public static function getExpandedMenu(Repository $repository, $contentTypeName, $recordId, $options = [])
    {
        $path = self::getBreadcrumb($repository, $contentTypeName, $recordId);

        $repository->selectContentType($contentTypeName);

        $result = [];

        // Add all same level pages within path

        $i = 0;
        $id = null;
        /**
         * @var int $id
         * @var Record $record
         */
        foreach ($path as $id => $record) {
            $i++;
            $expand = true;

            if (array_key_exists('levels', $options)) {
                $expand = false;
                if (in_array($i, $options['levels'])) {
                    $expand = true;
                }
            }

            if ($expand) {
                $records = $repository->getSortedRecords($record->getParent(), false, 1);
                foreach ($records as $record) {
                    $result[$record->getId()] = $record;
                }
            } else {
                $result[$record->getId()] = $record;
            }
        }

        // Add children

        if ($id !== null) {
            $records = $repository->getSortedRecords($id, false, 1);
            foreach ($records as $record) {
                $result[$record->getId()] = $record;
            }
        }

        foreach ($result as $record) {
            $record->setRepository($repository);
        }

        return RecordsSorter::sortRecords($result);
    }
}
