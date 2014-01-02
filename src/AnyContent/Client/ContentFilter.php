<?php

namespace AnyContent\Client;

use AnyContent\Client\AnyContentClientException;
use CMDL\ContentTypeDefinition;

class ContentFilter
{
    protected $contentTypeDefinition = null;

    protected $conditionsArray = null;

    protected $block = 0;

    public function __construct(ContentTypeDefinition $contentTypeDefinition)
    {
        $this->contentTypeDefinition = $contentTypeDefinition;
    }

    public function addCondition($property, $operator, $comparison)
    {
        $options = array( '=', '>', '<', '<=', '>=', '<>', '><', '={}', '>{}', '<{}', '<={}', '>={}', '<>{}', '><{}' );

        if (!in_array($operator, $options))
        {
            throw new AnyContentClientException ('Invalid filter condition operator.', AnyContentClientException::CLIENT_UNKNOWN_FILTER_CONDITION_OPERATOR);
        }

        if (!$this->conditionsArray)
        {
            $this->conditionsArray                 = array();
            $this->conditionsArray[++$this->block] = array();
        }
        $this->conditionsArray[$this->block][] = array( $property, $operator, $comparison );

    }


    public function nextConditionsBlock()
    {
        if (!$this->conditionsArray)
        {
            $this->conditionsArray                 = array();
            $this->conditionsArray[++$this->block] = array();
        }
        else
        {
            $this->conditionsArray[++$this->block] = array();
        }
    }


    public function setConditionsArray($conditionsArray)
    {
        $this->conditionsArray = $conditionsArray;
    }


    public function getConditionsArray()
    {
        if (!$this->conditionsArray)
        {
            return array();
        }

        return $this->conditionsArray;
    }

}

