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


    public function __construct($client)
    {
        $this->client = $client;
    }


    public function getContentTypes()
    {
        return $this->client->getContentTypeList();
    }


    public function getContentTypeDefinition($contentTypeName)
    {
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
        return in_array($contentTypeName, $this->client->getContentTypeList());
    }
}