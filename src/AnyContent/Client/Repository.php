<?php

namespace AnyContent\Client;

use CMDL\Parser;
use CMDL\CMDLParserException;
use CMDL\Util;

use CMDL\ContentTypeDefinition;

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


    public function getRecord($id, $workspace = 'default', $clippingName = 'default', $language = 'none', $timeshift = 0)
    {
        if ($this->contentTypeDefinition)
        {
            return $this->client->getRecord($this->contentTypeDefinition, $id, $workspace, $clippingName, $language, $timeshift);
        }

        return false;

    }


    public function getRecords($workspace = 'default', $clippingName = 'default', $language = 'none', $order = 'id', $properties = array(), $limit = null, $page = 1, $timeshift = 0)
    {
        if ($this->contentTypeDefinition)
        {
            return $this->client->getRecords($this->contentTypeDefinition, $workspace, $clippingName, $language, $order, $properties, $limit, $page, $timeshift);
        }

        return false;
    }
}
