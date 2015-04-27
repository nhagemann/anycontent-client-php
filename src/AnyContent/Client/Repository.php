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

    protected $order = 'id';


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


    /**
     * @return string
     */
    public function getOrder()
    {
        return $this->order;
    }


    /**
     * @param string $order
     */
    public function setOrder($order)
    {
        $this->order = $order;

        return $this;
    }


    public function resetDimensions()
    {
        $this->workspace = 'default';
        $this->language  = 'default';
        $this->timeshift = 0;
        $this->viewName  = 'default';
        $this->order     = 'id';

        return $this;
    }


    public function getRecord($id, $workspace = null, $viewName = null, $language = null, $timeshift = null)
    {
        if ($this->contentTypeDefinition == null)
        {
            throw new AnyContentClientException('You must first select a content type (->selectContentType($contentTypeName))');
        }

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


    public function getFirstRecord(ContentFilter $filter, $workspace = null, $viewName = null, $language = null, $order = null, $properties = array(), $timeshift = null)
    {
        if ($this->contentTypeDefinition == null)
        {
            throw new AnyContentClientException('You must first select a content type (->selectContentType($contentTypeName))');
        }

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
        if ($order === null)
        {
            $order = $this->getOrder();
        }

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


    public function saveRecord(Record $record, $workspace = null, $viewName = null, $language = null)
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

        return $this->client->saveRecord($record, $workspace, $viewName, $language);
    }


    public function saveRecords(Array $records, $workspace = null, $viewName = null, $language = null)
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

        return $this->client->saveRecords($records, $workspace, $viewName, $language);
    }


    public function getRecordsAsRecordObjects($filter = null, $limit = null, $page = 1, $classname = null)
    {

        return $this->getRecords(null, null, null, null, array(), $limit, $page, $filter);
    }


    public function getRecordsAsIDNameList($filter = null, $limit = null, $page = 1)
    {
        if ($this->contentTypeDefinition == null)
        {
            throw new AnyContentClientException('You must first select a content type (->selectContentType($contentTypeName))');
        }

        $result = $this->client->rawFetchRecords($this->contentTypeDefinition, $this->getWorkspace(), $this->getViewName(), $this->getLanguage(), $this->getOrder(), array(), $limit, $page, $filter, null, $this->getTimeshift());

        if ($result && array_key_exists('records', $result))
        {
            $items = array();
            foreach ($result['records'] as $id => $record)
            {
                $items[$id] = @$record['properties']['name'];
            }

            return $items;

        }

        return false;
    }


    public function getRecordsAsPropertiesArray($filter = null, $limit = null, $page = 1)
    {
        if ($this->contentTypeDefinition == null)
        {
            throw new AnyContentClientException('You must first select a content type (->selectContentType($contentTypeName))');
        }

        $result = $this->client->rawFetchRecords($this->contentTypeDefinition, $this->getWorkspace(), $this->getViewName(), $this->getLanguage(), $this->getOrder(), array(), $limit, $page, $filter, null, $this->getTimeshift());

        if ($result && array_key_exists('records', $result))
        {
            $items = array();
            foreach ($result['records'] as $id => $record)
            {
                $items[$id] = $record['properties'];
            }

            return $items;

        }

        return false;
    }


    /**
     * @param null  $workspace
     * @param null  $viewName
     * @param null  $language
     * @param null  $order
     * @param array $properties
     * @param null  $limit
     * @param null  $page
     * @param null  $filter
     * @param null  $subset
     * @param null  $timeshift
     *
     * @return array|bool
     */
    public function getRecords($workspace = null, $viewName = null, $language = null, $order = null, $properties = array(), $limit = null, $page = 1, $filter = null, $subset = null, $timeshift = null)
    {
        if ($this->contentTypeDefinition == null)
        {
            throw new AnyContentClientException('You must first select a content type (->selectContentType($contentTypeName))');
        }

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
        if ($order === null)
        {
            $order = $this->getOrder();
        }
        if ($this->contentTypeDefinition)
        {
            return $this->client->getRecords($this->contentTypeDefinition, $workspace, $viewName, $language, $order, $properties, $limit, $page, $filter, $subset, $timeshift);
        }

        return false;
    }


    public function countRecords($workspace = null, $viewName = null, $language = null, $order = null, $properties = array(), $limit = null, $page = 1, $filter = null, $timeshift = null)
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
        if ($order === null)
        {
            $order = $this->getOrder();
        }

        if ($this->contentTypeDefinition)
        {
            return $this->client->countRecords($this->contentTypeDefinition, $workspace, $viewName, $language, $order, $properties, $limit, $page, $filter, $timeshift);
        }

        return false;
    }


    public function getSubset($parentId, $includeParent = true, $depth = null, $workspace = null, $viewName = null, $language = null, $timeshift = null)
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
            return $this->client->getSubset($this->contentTypeDefinition, $parentId, $includeParent, $depth, $workspace, $viewName, $language, $timeshift);
        }

        return false;
    }


    public function sortRecords($list, $workspace = null, $language = null)
    {
        if ($workspace === null)
        {
            $workspace = $this->getWorkspace();
        }
        if ($language === null)
        {
            $language = $this->getLanguage();
        }

        if ($this->contentTypeDefinition)
        {
            return $this->client->sortRecords($this->contentTypeDefinition, $list, $workspace, $language);
        }

        return false;

    }


    public function deleteRecord($id, $workspace = null, $language = null)
    {
        if ($workspace === null)
        {
            $workspace = $this->getWorkspace();
        }
        if ($language === null)
        {
            $language = $this->getLanguage();
        }

        if ($this->contentTypeDefinition)
        {
            return $this->client->deleteRecord($this->contentTypeDefinition, $id, $workspace, $language);
        }

        return false;

    }


    public function deleteRecords($workspace = null, $language = null)
    {
        if ($workspace === null)
        {
            $workspace = $this->getWorkspace();
        }
        if ($language === null)
        {
            $language = $this->getLanguage();
        }

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


    public function saveConfig(Config $config, $workspace = null, $language = null)
    {
        if ($workspace === null)
        {
            $workspace = $this->getWorkspace();
        }
        if ($language === null)
        {
            $language = $this->getLanguage();
        }

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
