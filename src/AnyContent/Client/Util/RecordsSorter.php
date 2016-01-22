<?php

namespace AnyContent\Client\Util;

use AnyContent\Client\Record;

class RecordsSorter
{

    /**
     * @param Record[] $records
     *
     * @return array
     */
    public static function orderRecords(array $records, $order)
    {

        if (!is_array($order))
        {
            $order = [ $order ];
        }

        $instructions = [ ];
        foreach ($order as $property)
        {
            $property  = trim($property);
            $sortorder = substr($property, -1);

            switch ($sortorder)
            {
                case '-';
                case '+':
                    $property = substr($property, 0, -1);
                    break;
                default:
                    $sortorder = '+';
                    break;

            }

            $instructions[] = [ 'property' => $property, 'order' => $sortorder ];

        }

        uasort($records, function (Record $a, Record $b) use ($instructions)
        {

            foreach ($instructions as $instruction)
            {
                $property = $instruction['property'];
                $order    = $instruction['order'];

                switch ($property)
                {
                    case '.id':
                        $valueA = $a->getId();
                        $valueB = $b->getId();
                        break;
                    case '.info.creation.username':
                        $valueA = $a->getCreationUserInfo()->getUsername();
                        $valueB = $b->getCreationUserInfo()->getUsername();
                        break;
                    case '.info.creation.firstname':
                        $valueA = $a->getCreationUserInfo()->getFirstname();
                        $valueB = $b->getCreationUserInfo()->getFirstname();
                        break;
                    case '.info.creation.lastname':
                        $valueA = $a->getCreationUserInfo()->getLastname();
                        $valueB = $b->getCreationUserInfo()->getLastname();
                        break;
                    case '.info.creation.timestamp':
                        $valueA = $a->getCreationUserInfo()->getTimestamp();
                        $valueB = $b->getCreationUserInfo()->getTimestamp();
                        break;
                    case '.info.lastchange.username':
                        $valueA = $a->getLastChangeUserInfo()->getUsername();
                        $valueB = $b->getLastChangeUserInfo()->getUsername();
                        break;
                    case '.info.lastchange.firstname':
                        $valueA = $a->getLastChangeUserInfo()->getFirstname();
                        $valueB = $b->getLastChangeUserInfo()->getFirstname();
                        break;
                    case '.info.lastchange.lastname':
                        $valueA = $a->getLastChangeUserInfo()->getLastname();
                        $valueB = $b->getLastChangeUserInfo()->getLastname();
                        break;
                    case '.info.lastchange.timestamp':
                        $valueA = $a->getLastChangeUserInfo()->getTimestamp();
                        $valueB = $b->getLastChangeUserInfo()->getTimestamp();
                        break;
                    default:
                        $valueA = $a->getProperty($property);
                        $valueB = $b->getProperty($property);
                        break;
                }

                if ($order == '+')
                {
                    if ($valueA < $valueB)
                    {
                        return -1;
                    }
                    if ($valueA > $valueB)
                    {
                        return 1;
                    }
                }
                else
                {
                    if ($valueA > $valueB)
                    {
                        return -1;
                    }
                    if ($valueA < $valueB)
                    {
                        return 1;
                    }
                }

            }

        });

        return $records;
    }


    /**
     * @param Record[] $records
     *
     * @return array
     */
    public static function sortRecords(array $records, $parentId = 0, $includeParent = false, $depth = null)
    {
        $list = [ ];
        $map  = [ ];

        $records = self::orderRecords($records, 'position');

        foreach ($records as $record)
        {
            if ($record->getParent() === 0 || $record->getParent() > 0) // include 0 and numbers, exclude null and ''
            {
                $map[$record->getId()] = $record;
                $list[]                = [ 'id' => $record->getId(), 'parentId' => $record->getParent() ];
            }
        }

        $util      = new AdjacentList2NestedSet($list);
        $nestedSet = $util->getNestedSet();

        $result = [ ];

        if ($parentId != 0)
        {
            $root = $nestedSet[$parentId];
            if ($depth != null)
            {
                $depth = $depth + $root['level'];
            }

            if ($includeParent)
            {
                $result[$parentId] = $map[$parentId];
                $result[$parentId]->setLevel($root['level']);
            }
        }

        foreach ($nestedSet as $id => $positioning)
        {

            if ($depth === null || $positioning['level'] <= $depth)
            {
                if ($parentId == 0 || ($positioning['left'] > $root['left'] && $positioning['right'] < $root['right']))
                {
                    $result[$id] = $map[$id];
                    $result[$id]->setLevel($positioning['level']);
                }
            }
        }

        return $result;

    }
}



