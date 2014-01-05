<?php

namespace AnyContent\Client;

use CMDL\Parser;
use CMDL\CMDLParserException;
use CMDL\Util;

use CMDL\ContentTypeDefinition;

use AnyContent\Client\ContentFilter;

class Repository
{

    /** @var  Client */
    protected $client;

    protected $contentTypeName = '';
    protected $contentTypeDefinition = null;


    public function __construct($client)
    {
        $this->client = $client;
    }


    public function getContentTypes()
    {
        return $this->client->getContentTypeList();
    }


    public function getContentTypeDefinition($contentTypeName = null)
    {
        if ($contentTypeName == null AND $this->contentTypeDefinition)
        {
            return $this->contentTypeDefinition;
        }

        if ($this->hasContentType($contentTypeName))
        {
            $cmdl                  = $this->client->getCMDL($contentTypeName);
            $contentTypeDefinition = Parser::parseCMDLString($cmdl);
            if ($contentTypeDefinition)
            {
                $contentTypeDefinition->setName($contentTypeName);

                return $contentTypeDefinition;
            }
        }

        return false;
    }


    public function hasContentType($contentTypeName)
    {
        return array_key_exists($contentTypeName, $this->client->getContentTypeList());
    }


    public function selectContentType($contentTypeName)
    {
        if ($this->contentTypeName != $contentTypeName)
        {
            $this->contentTypeName       = $contentTypeName;
            $this->contentTypeDefinition = $this->getContentTypeDefinition($contentTypeName);
        }
    }


    public function getRecord($id, $workspace = 'default', $clippingName = 'default', $language = 'default', $timeshift = 0)
    {
        if ($this->contentTypeDefinition)
        {
            return $this->client->getRecord($this->contentTypeDefinition, $id, $workspace, $clippingName, $language, $timeshift);
        }

        return false;

    }


    public function saveRecord(Record $record, $workspace = 'default', $clippingName = 'default', $language = 'default')
    {
        return $this->client->saveRecord($record, $workspace, $clippingName, $language);
    }


    public function getRecords($workspace = 'default', $clippingName = 'default', $language = 'default', $order = 'id', $properties = array(), $limit = null, $page = 1, ContentFilter $filter = null, $timeshift = 0)
    {
        if ($this->contentTypeDefinition)
        {
            return $this->client->getRecords($this->contentTypeDefinition, $workspace, $clippingName, $language, $order, $properties, $limit, $page, $filter, $timeshift);
        }

        return false;
    }

    public function countRecords($workspace = 'default', $clippingName = 'default', $language = 'default', $order = 'id', $properties = array(), $limit = null, $page = 1, ContentFilter $filter = null, $timeshift = 0)
    {
        if ($this->contentTypeDefinition)
        {
            return $this->client->countRecords($this->contentTypeDefinition, $workspace, $clippingName, $language, $order, $properties, $limit, $page, $filter, $timeshift);
        }

        return false;
    }

    public function sortRecords($list, $workspace = 'default',  $language = 'default')
    {
        if ($this->contentTypeDefinition)
        {
            return $this->client->sortRecords($this->contentTypeDefinition,$list,$workspace,$language);
        }

        return false;

    }


    public function deleteRecord($id, $workspace = 'default', $language = 'default')
    {
        if ($this->contentTypeDefinition)
        {
            return $this->client->deleteRecord($this->contentTypeDefinition, $id, $workspace, $language);
        }

        return false;

    }
}
