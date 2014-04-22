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
    protected $configTypeDefinitions = array();


    public function __construct($client)
    {
        $this->client = $client;
    }


    public function getContentTypes()
    {
        return $this->client->getContentTypeList();
    }


    public function getConfigTypes()
    {

        return $this->client->getConfigTypesList();
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


    public function getConfigTypeDefinition($configTypeName = null)
    {
        if (array_key_exists($configTypeName, $this->configTypeDefinitions))
        {
            return $this->configTypeDefinitions[$configTypeName];
        }

        if ($this->hasConfigType($configTypeName))
        {
            $cmdl                 = $this->client->getConfigCMDL($configTypeName);
            $configTypeDefinition = Parser::parseCMDLString($cmdl, $configTypeName, '', 'config');
            if ($configTypeDefinition)
            {
                $configTypeDefinition->setName($configTypeName);

                return $configTypeDefinition;
            }
        }

        return false;
    }


    public function hasContentType($contentTypeName)
    {
        return array_key_exists($contentTypeName, $this->client->getContentTypeList());
    }


    public function hasConfigType($configTypeName)
    {
        return array_key_exists($configTypeName, $this->client->getConfigTypesList());
    }


    public function selectContentType($contentTypeName)
    {
        if ($this->contentTypeName != $contentTypeName)
        {
            $this->contentTypeName       = $contentTypeName;
            $this->contentTypeDefinition = $this->getContentTypeDefinition($contentTypeName);
            return true;
        }
        return false;
    }


    public function getRecord($id, $workspace = 'default', $viewName = 'default', $language = 'default', $timeshift = 0)
    {
        if ($this->contentTypeDefinition)
        {
            return $this->client->getRecord($this->contentTypeDefinition, $id, $workspace, $viewName, $language, $timeshift);
        }

        return false;

    }


    public function getFirstRecord(ContentFilter $filter, $workspace = 'default', $viewName = 'default', $language = 'default', $order = 'id', $properties = array(), $timeshift = 0)
    {
        if ($this->contentTypeDefinition)
        {
            $records = $this->client->getRecords($this->contentTypeDefinition, $workspace, $viewName, $language, $order, $properties, 1, 1, $filter, null, $timeshift);
            if (count($records) == 1)
            {
                return array_shift($records);
            }
        }

        return false;
    }


    public function saveRecord(Record $record, $workspace = 'default', $viewName = 'default', $language = 'default')
    {
        return $this->client->saveRecord($record, $workspace, $viewName, $language);
    }


    public function getRecords($workspace = 'default', $viewName = 'default', $language = 'default', $order = 'id', $properties = array(), $limit = null, $page = 1, ContentFilter $filter = null, $subset = null, $timeshift = 0)
    {
        if ($this->contentTypeDefinition)
        {
            return $this->client->getRecords($this->contentTypeDefinition, $workspace, $viewName, $language, $order, $properties, $limit, $page, $filter, $subset, $timeshift);
        }

        return false;
    }


    public function countRecords($workspace = 'default', $viewName = 'default', $language = 'default', $order = 'id', $properties = array(), $limit = null, $page = 1, ContentFilter $filter = null, $timeshift = 0)
    {
        if ($this->contentTypeDefinition)
        {
            return $this->client->countRecords($this->contentTypeDefinition, $workspace, $viewName, $language, $order, $properties, $limit, $page, $filter, $timeshift);
        }

        return false;
    }


    public function getSubset($parentId, $includeParent = true, $depth = null, $workspace = 'default', $viewName = 'default', $language = 'default', $timeshift = 0)
    {
        if ($this->contentTypeDefinition)
        {
            return $this->client->getSubset($this->contentTypeDefinition, $parentId, $includeParent, $depth, $workspace, $viewName, $language, $timeshift);
        }

        return false;
    }


    public function sortRecords($list, $workspace = 'default', $language = 'default')
    {
        if ($this->contentTypeDefinition)
        {
            return $this->client->sortRecords($this->contentTypeDefinition, $list, $workspace, $language);
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


    public function getConfig($configTypeName)
    {
        return $this->client->getConfig($configTypeName);
    }


    public function saveConfig(Config $config, $workspace = 'default', $language = 'default')
    {
        return $this->client->saveConfig($config, $workspace, $language);
    }


    /**
     * @param string $path
     *
     * @return Folder|bool
     */
    public function getFolder($path = '')
    {
        return $this->client->getFolder($path);
    }


    public function getFile($id)
    {
        return $this->client->getFile($id);
    }


    public function getBinary(File $file)
    {
        return $this->client->getBinary($file);
    }


    public function saveFile($id, $binary)
    {
        return $this->client->saveFile($id, $binary);
    }


    public function deleteFile($id, $deleteEmptyFolder = true)
    {
        return $this->client->deleteFile($id, $deleteEmptyFolder);
    }


    public function createFolder($path)
    {
        return $this->client->createFolder($path);
    }


    public function deleteFolder($path, $deleteIfNotEmpty = false)
    {
        return $this->client->deleteFolder($path, $deleteIfNotEmpty);
    }
}
