<?php

namespace AnyContent\Client;

use AnyContent\Client\AnyContentClientException;

use CMDL\Util;
use CMDL\ContentTypeDefinition;
use AnyContent\Client\Record;

class ContentQueryFilter
{

    public function __construct()
    {
        $this->creation['username'] = null;

        $this->creation['timestamp']   = array( null, null );
        $this->lastchange['username']  = null;
        $this->lastchange['timestamp'] = array( null, null );

    }


    public function setLastEditedBy($username)
    {
        $this->lastchange['username'] = $username;
    }


    public function setCreatedBy($username)
    {
        $this->creation['username'] = null;

    }


    public function setLastEditedBetween($start = 0, $stop = null)
    {
        if ($stop == null)
        {
            // 19.01.2038 - max unix timestamp
            $stop = 2147483647;
        }
        $this->lastchange['timestamp'] = array( $start, $stop );

    }


    public function setCreatedBetween($start = 0, $stop = null)
    {
        if ($stop == null)
        {
            // 19.01.2038 - max unix timestamp
            $stop = 2147483647;
        }
        $this->creation['timestamp'] = array( $start, $stop );
    }


    public function addPropertyCondition($property, $operator, $value)
    {
        if (in_array($operator, array( '=', '>', '<', '>=', '<=', '!=', 'CONTAINS' )))
        {
            $this->properties[] = array( $property, $operator, $value );
        }
        else
        {
            throw AnyContentClientException(AnyContentClientException::ANYCONTENT_UNKNOW_FILTER_OPERATOR, 'Unknown filter operator ' . $operator);
        }
    }
}