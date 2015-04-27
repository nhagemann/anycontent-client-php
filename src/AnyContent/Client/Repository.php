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

    protected $configTypeName = '';

    protected $contentTypeDefinition = null;

    protected $workspace = 'default';

    protected $viewName = 'default';

    protected $language = 'default';

    protected $timeshift = 0;


    public function __construct($client)
    {
        $this->client = $client;
    }


    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }


    public function getRepositoryName()
    {
        $url   = trim($this->getClient()->getUrl(), '/');
        $parts = explode('/', $url);

        return array_pop($parts);
    }


    public function getContentTypes()
    {
        return $this->client->getContentTypesList();
    }


    public function getConfigTypes()
    {

        return $this->client->getConfigTypesList();
    }


    public function getContentTypeDefinition($contentTypeName = null)
    {
        if ($contentTypeName == null)
        {
            $contentTypeName = $this->contentTypeName;
        }

        return $this->client->getContentTypeDefinition($contentTypeName);
    }


    public function getConfigTypeDefinition($configTypeName = null)
    {
        if ($configTypeName == null)
        {
            $configTypeName = $this->configTypeName;
        }

        return $this->client->getConfigTypeDefinition($configTypeName);
    }


    public function hasContentType($contentTypeName)
    {
        return array_key_exists($contentTypeName, $this->client->getContentTypesList());
    }


    public function hasConfigType($configTypeName)
    {
        return array_key_exists($configTypeName, $this->client->getConfigTypesList());
    }


    public function selectContentType($contentTypeName)
    {
        if ($this->hasContentType($contentTypeName))
        {
            if ($this->contentTypeName != $contentTypeName)
            {
                $this->contentTypeName       = $contentTypeName;
                $this->contentTypeDefinition = $this->getContentTypeDefinition($contentTypeName);

            }

            return true;
        }

        return false;
    }


    public function selectConfigType($configTypeName)
    {
        if ($this->hasConfigType($configTypeName))
        {
            if ($this->configTypeName != $configTypeName)
            {
                $this->configTypeName = $configTypeName;

            }

            return true;
        }

        return false;
    }


    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }


    /**
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }


    /**
     * @return int
     */
    public function getTimeshift()
    {
        return $this->timeshift;

        return $this;
    }


    /**
     * @param int $timeshift
     */
    public function setTimeshift($timeshift)
    {
        $this->timeshift = $timeshift;

        return $this;
    }


    /**
     * @return string
     */
    public function getViewName()
    {
        return $this->viewName;

        return $this;
    }


    /**
     * @param string $viewName
     */
    public function setViewName($viewName)
    {
        $this->viewName = $viewName;
    }


    /**
     * @return string
     */
    public function getWorkspace()
    {
        return $this->workspace;
    }


    /**
     * @param string $workspace
     */
    public function setWorkspace($workspace)
    {
        $this->workspace = $workspace;

        return $this;
    }


    public function getRecord($id, $workspace = null, $viewName = null, $language = null, $timeshift = null)
    {
        if ($workspace === null)
        {
            $workspace = $this->getWorkspace();
        }
        if ($viewName === null)
        {
            $viewName = $this->getViewName();
        }
        if ($language === null)
        {
            $language = $this->getLanguage();
        }
        if ($timeshift === null)
        {
            $timeshift = $this->getTimeshift();
        }

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


    public function saveRecords(Array $records, $workspace = 'default', $viewName = 'default', $language = 'default')
    {
        return $this->client->saveRecords($records, $workspace, $viewName, $language);
    }


    public function getRecords($workspace = 'default', $viewName = 'default', $language = 'default', $order = 'id', $properties = array(), $limit = null, $page = 1, $filter = null, $subset = null, $timeshift = 0)
    {
        if ($this->contentTypeDefinition)
        {
            return $this->client->getRecords($this->contentTypeDefinition, $workspace, $viewName, $language, $order, $properties, $limit, $page, $filter, $subset, $timeshift);
        }

        return false;
    }


    public function countRecords($workspace = 'default', $viewName = 'default', $language = 'default', $order = 'id', $properties = array(), $limit = null, $page = 1, $filter = null, $timeshift = 0)
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


    public function deleteRecords($workspace = 'default', $language = 'default')
    {
        if ($this->contentTypeDefinition)
        {
            return $this->client->deleteRecords($this->contentTypeDefinition, $workspace, $language);
        }

        return false;

    }


    public function getConfig($configTypeName = null)
    {
        if ($configTypeName == null)
        {
            $configTypeName = $this->configTypeName;
        }

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
