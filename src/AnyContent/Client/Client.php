<?php

namespace AnyContent\Client;

use AnyContent\Client\AnyContentClientException;

use CMDL\Parser;
use CMDL\Util;
use CMDL\ContentTypeDefinition;
use AnyContent\Client\Record;
use AnyContent\Client\Config;
use AnyContent\Client\Repository;
use AnyContent\Client\UserInfo;
use AnyContent\Client\ContentFilter;
use AnyContent\Client\Folder;
use AnyContent\Client\File;

use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\ArrayCache;
use Guzzle\Log\MessageFormatter;
use Guzzle\Parser\ParserRegistry;
use Guzzle\Plugin\Log\LogPlugin;
use Psr\Log\LoggerInterface;

class Client
{

    const RECORDS_ORDER_MODE_LIST = 1;
    const RECORDS_ORDER_MODE_TREE = 2;

    const MAX_TIMESHIFT = 315532800; // roundabout 10 years, equals to 1.1.1980
    /**
     * @var \Guzzle\Http\Client;
     */
    protected $guzzle;

    protected $url;

    protected $apiUser;

    protected $apiPassword;

    protected $repositoryInfo = null;

    protected $contentTypesList = null;

    protected $configTypesList = null;

    protected $contentTypeDefinition = array();
    protected $configTypeDefinitions = array();

    protected $contentRecordClassMap = array();

    /**
     * @var Cache;
     */
    protected $cache;

    protected $cachePrefix = '';

    protected $cacheSecondsData = 3600;
    protected $cacheSecondsIgnoreDataConcurrency = 15;

    protected $log = array();

    /** @var LoggerInterface */
    protected $logger = null;

    /**
     * @var bool
     */
    protected $validatePropertyNames = false;


    /** @var null|Repository */
    protected $repository = null;

    /**
     * @param                              $url
     * @param null                         $apiUser
     * @param null                         $apiPassword
     * @param string                       $authType                           "Basic" (default), "Digest", "NTLM", or "Any".
     * @param \Doctrine\Common\Cache\Cache $cache
     * @param int                          $cacheSecondsData
     * @param int                          $cacheSecondsIgnoreDataConcurrency  - raise, if your application is the only application, which makes content/config write requests on the connected repository
     * @param int                          $cacheSecondsIgnoreFilesConcurrency - raise, if your application is the only application, which makes file changes and/or you do have a slow file storage adapter on the connected repository
     *
     */
    public function __construct($url, $apiUser = null, $apiPassword = null, $authType = 'Basic', Cache $cache = null, $cacheSecondsData = 3600, $cacheSecondsIgnoreDataConcurrency = 1, $cacheSecondsIgnoreFilesConcurrency = 60, $msDelayBetweenRequests = 0)
    {
        $this->url         = $url;
        $this->apiUser     = $apiUser;
        $this->apiPassword = $apiPassword;

        // Create a client and provide a base URL
        $this->guzzle = new \Guzzle\Http\Client($url);

        if ($apiUser != null)
        {
            $this->guzzle->setDefaultOption('auth', array( $apiUser, $apiPassword, $authType ));
        }

        if ($cache)
        {
            $this->cache = $cache;
        }
        else
        {
            $this->cache = new ArrayCache();
        }

        $this->cacheSecondsData                   = $cacheSecondsData;
        $this->cacheSecondsIgnoreDataConcurrency  = $cacheSecondsIgnoreDataConcurrency;
        $this->cacheSecondsIgnoreFilesConcurrency = $cacheSecondsIgnoreFilesConcurrency;

        $this->cachePrefix = 'client_' . md5($url . $apiUser . $apiPassword);

        if (array_key_exists('XDEBUG_SESSION', $_COOKIE) && $_COOKIE['XDEBUG_SESSION'] != "")
        {
            $this->guzzle->setDefaultOption('cookies', array( 'XDEBUG_SESSION' => $_COOKIE['XDEBUG_SESSION'] ));
        }

        if ($msDelayBetweenRequests != 0)
        {
            $adapter   = new Decelerator($msDelayBetweenRequests);
            $logPlugin = new LogPlugin($adapter);
            $this->guzzle->addSubscriber($logPlugin);
        }

        $adapter   = new Logger($this);
        $logPlugin = new LogPlugin($adapter, '{url}|{code}|{total_time}');
        $this->guzzle->addSubscriber($logPlugin);
    }


    public function setTimeout($seconds)
    {
        $this->guzzle->setDefaultOption('timeout', $seconds);
    }


    public function clearTimeout()
    {
        $this->guzzle->setDefaultOption('timeout', null);
    }


    public function registerRecordClassForContentType($contentTypeName, $classname)
    {
        if ($this->hasContentType($contentTypeName))
        {
            $this->contentRecordClassMap[$contentTypeName] = $classname;

            return true;
        }

        return false;
    }


    public function getClassForContentType($contentTypeName)
    {
        if (array_key_exists($contentTypeName, $this->contentRecordClassMap))
        {
            return $this->contentRecordClassMap[$contentTypeName];
        }

        return 'AnyContent\Client\Record';
    }


    /**
     * @return boolean
     */
    public function isValidatePropertyNames()
    {
        return $this->validatePropertyNames;
    }


    /**
     * Activate, if json results from repository server might contain invalid properties - which would be an implementation error
     *
     * @param boolean $validatePropertyNames
     */
    public function setValidatePropertyNames($validatePropertyNames)
    {
        $this->validatePropertyNames = $validatePropertyNames;
    }


    /**
     * deletes temporarily collected info about the current repository
     *
     * if language and workspace are given, related cache token gets deleted too
     *
     * @param null $workspace
     * @param null $language
     */
    protected function deleteRepositoryInfo($workspace = null, $language = null)
    {
        if ($workspace != null and $language != null)
        {
            $cacheToken = $this->cachePrefix . '_info_' . $workspace . '_' . $language . '_0_' . $this->getHeartBeat();
            $this->cache->delete($cacheToken);
        }
        $this->contentTypesList = null;
        $this->configTypesList  = null;
    }


    public function setUserInfo(UserInfo $userInfo)
    {
        $this->guzzle->setDefaultOption('query', array( 'userinfo' => array( 'username' => $userInfo->getUsername(), 'firstname' => $userInfo->getFirstname(), 'lastname' => $userInfo->getLastname() ) ));
    }


    public function getRepository()
    {
        if (!$this->repository)
        {
            $this->repository = new Repository($this);
        }

        return $this->repository;
    }


    public function getRepositoryInfo($workspace = 'default', $language = 'default', $timeshift = 0)
    {

        $result = false;
        if ($timeshift == 0 OR $timeshift > self::MAX_TIMESHIFT)
        {
            $cacheToken = $this->cachePrefix . '_info_' . $workspace . '_' . $language . '_' . $timeshift . '_' . $this->getHeartBeat();

            if ($this->cache->contains($cacheToken))
            {
                $result = $this->cache->fetch($cacheToken);
            }
        }

        if ($result == false)
        {
            $url = 'info/' . $workspace;

            $options = array( 'query' => array( 'language' => $language, 'timeshift' => $timeshift ) );

            try
            {

                $request = $this->guzzle->get($url, null, $options);

                $result = $request->send()->json();

            }
            catch (\Exception $e)
            {

                $response = $request->getResponse();

                if ($response && $response->getStatusCode() == 404)
                {
                    throw new AnyContentClientException($e->getMessage(), AnyContentClientException::ANYCONTENT_UNKNOW_REPOSITORY);
                }
                else
                {
                    throw new AnyContentClientException($e->getMessage(), AnyContentClientException::CLIENT_CONNECTION_ERROR);
                }
            }

            if ($this->cacheSecondsIgnoreDataConcurrency != 0)
            {
                if ($timeshift == 0)
                {
                    $this->cache->save($cacheToken, $result, $this->cacheSecondsIgnoreDataConcurrency);
                }
                if ($timeshift > self::MAX_TIMESHIFT)
                {
                    // timeshifted info result can get stored longer, since they won't change in the future, but they have to be absolute (>MAX_TIMESHIFT)
                    $this->cache->save($cacheToken, $result, $this->cacheSecondsData);
                }
            }
        }

        $this->contentTypesList = array();
        foreach ($result['content'] as $name => $item)
        {
            $title = $item['title'];
            if ($title == '')
            {
                $title = $name;
            }
            $this->contentTypesList[$name] = $title;
        }
        $this->configTypesList = array();

        foreach ($result['config'] as $name => $item)
        {
            $title = $item['title'];
            if ($title == '')
            {
                $title = $name;
            }
            $this->configTypesList[$name] = $title;
        }

        return $result;
    }


    public function getLastContentTypeChangeTimestamp($contentTypeName, $workspace = 'default', $language = 'default', $timeshift = 0)
    {
        $info = $this->getRepositoryInfo($workspace, $language, $timeshift);

        if (array_key_exists($contentTypeName, $info['content']))
        {
            return ($info['content'][$contentTypeName]['lastchange_content'] . $info['content'][$contentTypeName]['lastchange_cmdl']);
        }

        return time();
    }


    public function getLastConfigTypeChangeTimestamp($configTypeName, $workspace = 'default', $language = 'default', $timeshift = 0)
    {
        $info = $this->getRepositoryInfo($workspace, $language, $timeshift);

        if (array_key_exists($configTypeName, $info['config']))
        {
            return ($info['config'][$configTypeName]['lastchange_config'] . $info['config'][$configTypeName]['lastchange_cmdl']);
        }

        return time();
    }


    /**
     * @deprecated
     * @return null
     */
    public function getContentTypeList()
    {
        if ($this->contentTypesList === null)
        {
            $this->getRepositoryInfo();

        }

        return $this->contentTypesList;
    }


    public function getContentTypesList()
    {
        if ($this->contentTypesList === null)
        {
            $this->getRepositoryInfo();
        }

        return $this->contentTypesList;
    }


    public function getConfigTypesList()
    {
        if ($this->configTypesList === null)
        {
            $this->getRepositoryInfo();
        }

        return $this->configTypesList;
    }


    public function getContentTypeDefinition($contentTypeName)
    {

        if ($this->hasContentType($contentTypeName))
        {
            $cmdl = $this->getCMDL($contentTypeName);

            $contentTypeDefinition = Parser::parseCMDLString($cmdl, $contentTypeName, '', 'content');
            if ($contentTypeDefinition)
            {
                $contentTypeDefinition->setName($contentTypeName);

                return $contentTypeDefinition;
            }
        }

        return false;
    }


    public function getConfigTypeDefinition($configTypeName)
    {
        if ($this->hasConfigType($configTypeName))
        {
            $cmdl = $this->getConfigCMDL($configTypeName);

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
        return array_key_exists($contentTypeName, $this->getContentTypesList());
    }


    public function hasConfigType($configTypeName)
    {
        return array_key_exists($configTypeName, $this->getConfigTypesList());
    }


    public function getCMDL($contentTypeName)
    {
        if (array_key_exists($contentTypeName, $this->getContentTypesList()))
        {
            $timestamp = $this->getLastContentTypeChangeTimestamp($contentTypeName);

            $cacheToken = $this->cachePrefix . '_cmdl_' . $contentTypeName . '_' . $timestamp . '_' . $this->getHeartBeat();

            if ($this->cache->contains($cacheToken))
            {
                return $this->cache->fetch($cacheToken);
            }

            try
            {

                $request = $this->guzzle->get('content/' . $contentTypeName . '/cmdl');
                $result  = $request->send()->json();
            }
            catch (\Exception $e)
            {
                throw new AnyContentClientException($e->getMessage(), AnyContentClientException::CLIENT_CONNECTION_ERROR);
            }

            if ($this->cacheSecondsData != 0)
            {
                $this->cache->save($cacheToken, $result['cmdl'], $this->cacheSecondsData);
            }

            return $result['cmdl'];
        }
        else
        {
            throw new AnyContentClientException('', AnyContentClientException::CLIENT_CONNECTION_ERROR);
        }

    }


    public function getConfigCMDL($configTypeName)
    {
        if (array_key_exists($configTypeName, $this->getConfigTypesList()))
        {

            $timestamp = $this->getLastConfigTypeChangeTimestamp($configTypeName);

            $cacheToken = $this->cachePrefix . '_config_cmdl_' . $configTypeName . '_' . $timestamp . '_' . $this->getHeartBeat();
            $this->getHeartBeat();;

            if ($this->cache->contains($cacheToken))
            {
                return $this->cache->fetch($cacheToken);
            }

            try
            {
                $request = $this->guzzle->get('config/' . $configTypeName . '/cmdl');
                $result  = $request->send()->json();
            }
            catch (\Exception $e)
            {
                throw new AnyContentClientException($e->getMessage(), AnyContentClientException::CLIENT_CONNECTION_ERROR);
            }

            if ($this->cacheSecondsData != 0)
            {
                $this->cache->save($cacheToken, $result['cmdl'], $this->cacheSecondsData);
            }

            return $result['cmdl'];
        }
        else
        {
            throw new AnyContentClientException('', AnyContentClientException::ANYCONTENT_UNKNOW_CONFIG_TYPE);
        }

    }


    public function saveRecord(Record $record, $workspace = 'default', $viewName = 'default', $language = 'default')
    {
        $contentTypeName = $record->getContentType();

        $url = 'content/' . $contentTypeName . '/records/' . $workspace . '/' . $viewName;

        $json = json_encode($record);

        $request = $this->guzzle->post($url, null, array( 'record' => $json, 'language' => $language ));

        $result = false;
        try
        {
            $result = $request->send()->json();

        }
        catch (\Exception $e)
        {
            throw new AnyContentClientException($e->getMessage(), AnyContentClientException::CLIENT_CONNECTION_ERROR);
        }

        // repository info has changed
        $this->deleteRepositoryInfo($workspace, $language);

        if ($result === false)
        {
            return false;
        }

        return (int)$result;
    }


    public function saveRecords($records, $workspace = 'default', $viewName = 'default', $language = 'default')
    {
        if (count($records) == 0)
        {
            return false;
        }
        $record          = $records[0];
        $contentTypeName = $record->getContentType();

        $url = 'content/' . $contentTypeName . '/records/' . $workspace . '/' . $viewName;

        $json = json_encode($records);

        $request = $this->guzzle->post($url, null, array( 'records' => $json, 'language' => $language ));

        $result = false;
        try
        {
            $result = $request->send()->json();

        }
        catch (\Exception $e)
        {
            throw new AnyContentClientException($e->getMessage(), AnyContentClientException::CLIENT_CONNECTION_ERROR);
        }

        // repository info has changed
        $this->deleteRepositoryInfo($workspace, $language);

        if ($result === false)
        {
            return false;
        }

        return $result;

    }


    public function saveConfig(Config $config, $workspace = 'default', $language = 'default')
    {
        $configTypeName = $config->getConfigType();

        $url = 'config/' . $configTypeName . '/record/' . $workspace;

        $json = json_encode($config);

        $request = $this->guzzle->post($url, null, array( 'record' => $json, 'language' => $language ));

        $result = false;
        try
        {
            $result = $request->send()->json();

        }
        catch (\Exception $e)
        {
            throw new AnyContentClientException($e->getMessage(), AnyContentClientException::CLIENT_CONNECTION_ERROR);
        }

        // repository info has changed
        $this->deleteRepositoryInfo($workspace, $language);

        if ($result === false)
        {
            return false;
        }

        return (boolean)$result;

    }


    public function getRecord(ContentTypeDefinition $contentTypeDefinition, $id, $workspace = 'default', $viewName = 'default', $language = 'default', $timeshift = 0)
    {
        if ($timeshift == 0 OR $timeshift > self::MAX_TIMESHIFT)
        {
            $timestamp = $this->getLastContentTypeChangeTimestamp($contentTypeDefinition->getName(), $workspace, $language, $timeshift);

            //$className = $this->getClassForContentType($contentTypeDefinition->getName());

            $cacheToken = $this->cachePrefix . '_record_' . $contentTypeDefinition->getName() . '_' . $id . '_' . md5($timestamp . '_' . $timeshift . '_' . $workspace . '_' . $viewName . '_' . $language) . '_' . $this->getHeartBeat();

            if ($this->cache->contains($cacheToken))
            {
                $json = $this->cache->fetch($cacheToken);
                $record = $this->createRecordFromJSONResult($contentTypeDefinition, $json, $viewName, $workspace, $language, $this->validatePropertyNames);
                return $record;
            }
        }

        $url = 'content/' . $contentTypeDefinition->getName() . '/record/' . $id . '/' . $workspace . '/' . $viewName;

        $options = array( 'query' => array( 'language' => $language, 'timeshift' => $timeshift ) );
        $request = $this->guzzle->get($url, null, $options);

        try
        {

            $result = $request->send()->json();

            if ($timeshift == 0 OR $timeshift > self::MAX_TIMESHIFT)
            {
                $this->cache->save($cacheToken, $result['record'], $this->cacheSecondsData);
            }

            $record = $this->createRecordFromJSONResult($contentTypeDefinition, $result['record'], $viewName, $workspace, $language, $this->validatePropertyNames);

            return $record;
        }
        catch (\Exception $e)
        {
            $response = $request->getResponse();
            if ($response && $response->getStatusCode() != 404)
            {
                throw new AnyContentClientException($e->getMessage(), AnyContentClientException::CLIENT_CONNECTION_ERROR);
            }
        }

        return false;
    }


    public function getConfig($configTypeName, $workspace = 'default', $language = 'default', $timeshift = 0)
    {
        //todo caching
        if (array_key_exists($configTypeName, $this->getConfigTypesList()))
        {

            $cmdl                 = $this->getConfigCMDL($configTypeName);
            $configTypeDefinition = Parser::parseCMDLString($cmdl, $configTypeName, '', 'config');
            if ($configTypeDefinition)
            {
                $config = new Config($configTypeDefinition, $workspace, $language);

                $url = 'config/' . $configTypeName . '/record/' . $workspace;

                $options = array( 'query' => array( 'language' => $language, 'timeshift' => $timeshift ) );
                $request = $this->guzzle->get($url, null, $options);

                try
                {
                    $result = $request->send()->json();
                    $config->setProperties($result['record']['properties']);
                    $config->setHash($result['record']['info']['hash']);
                    $config->setRevision($result['record']['info']['revision']);
                    $config->setRevisionTimestamp($result['record']['info']['revision_timestamp']);
                    $config->setLastChangeUserInfo(new UserInfo($result['record']['info']['lastchange']['username'], $result['record']['info']['lastchange']['firstname'], $result['record']['info']['lastchange']['lastname'], $result['record']['info']['lastchange']['timestamp']));

                }
                catch (\Exception $e)
                {
                    $config->setRevision(1);
                }

                return $config;
            }

        }
        else
        {
            throw new AnyContentClientException('', AnyContentClientException::ANYCONTENT_UNKNOW_CONFIG_TYPE);
        }

        return false;

    }


    public function deleteRecord(ContentTypeDefinition $contentTypeDefinition, $id, $workspace = 'default', $language = 'default')
    {
        $url     = 'content/' . $contentTypeDefinition->getName() . '/record/' . $id . '/' . $workspace;
        $options = array( 'query' => array( 'language' => $language ) );
        $request = $this->guzzle->delete($url, null, null, $options);

        try
        {
            $result = $request->send()->json();
        }
        catch (\Exception $e)
        {
            $response = $request->getResponse();
            if ($response && $response->getStatusCode() != 404)
            {
                throw new AnyContentClientException($e->getMessage(), AnyContentClientException::CLIENT_CONNECTION_ERROR);
            }
        }

        // repository info has changed
        $this->deleteRepositoryInfo($workspace, $language);

        return $result;
    }


    public function deleteRecords(ContentTypeDefinition $contentTypeDefinition, $workspace = 'default', $language = 'default')
    {
        $url     = 'content/' . $contentTypeDefinition->getName() . '/records/' . $workspace;
        $options = array( 'query' => array( 'language' => $language ) );
        $request = $this->guzzle->delete($url, null, null, $options);

        try
        {
            $result = $request->send()->json();
        }
        catch (\Exception $e)
        {
            $response = $request->getResponse();
            if ($response && $response->getStatusCode() != 404)
            {
                throw new AnyContentClientException($e->getMessage(), AnyContentClientException::CLIENT_CONNECTION_ERROR);
            }
        }

        // repository info has changed
        $this->deleteRepositoryInfo($workspace, $language);

        return $result;
    }


    public function getRecords(ContentTypeDefinition $contentTypeDefinition, $workspace = 'default', $viewName = 'default', $language = 'default', $order = 'id', $properties = array(), $limit = null, $page = 1, $filter = null, $subset = null, $timeshift = 0)
    {
        $result = $this->rawFetchRecords($contentTypeDefinition, $workspace, $viewName, $language, $order, $properties, $limit, $page, $filter, $subset, $timeshift);

        if ($timeshift == 0 OR $timeshift > self::MAX_TIMESHIFT)
        {
            $timestamp = $this->getLastContentTypeChangeTimestamp($contentTypeDefinition->getName(), $workspace, $language, $timeshift);

            $filterToken     = '';
            $propertiesToken = json_encode($properties);
            if ($filter)
            {
                if (is_object($filter))
                {
                    if (get_class($filter) == 'AnyContent\Client\ContentFilter')
                    {
                        $filterToken = md5(json_encode($filter->getConditionsArray()));
                    }
                }
                else
                {
                    $filterToken = md5($filter);
                }

            }
            $className  = $this->getClassForContentType($contentTypeDefinition->getName());
            $cacheToken = $this->cachePrefix . '_records_' . $contentTypeDefinition->getName() . '_' . md5($className . '_' . $timestamp . '_' . $workspace . '_' . $viewName . '_' . $language . '_' . $timeshift) . '_' . md5($order . $propertiesToken . $limit . $page . $filterToken . $subset) . '_' . $this->getHeartBeat();

            /*if ($this->cache->contains($cacheToken))
            {
                return $this->cache->fetch($cacheToken);
            } */
        }

        // The following operation is slow even on cached requests, therefore the retrieved objects are cached too

        $records = array();

        foreach ($result['records'] as $item)
        {
            $record = $this->createRecordFromJSONResult($contentTypeDefinition, $item, $viewName, $workspace, $language, $this->validatePropertyNames);

            $records[$record->getID()] = $record;
        }

        /*
        if ($timeshift == 0 OR $timeshift > self::MAX_TIMESHIFT)
        {
            $this->cache->save($cacheToken, $records, $this->cacheSecondsData);
        } */

        return $records;
    }


    public function countRecords(ContentTypeDefinition $contentTypeDefinition, $workspace = 'default', $viewName = 'default', $language = 'default', $order = 'id', $properties = array(), $limit = null, $page = 1, $filter = null, $subset = null, $timeshift = 0)
    {
        $result = $this->rawFetchRecords($contentTypeDefinition, $workspace, $viewName, $language, $order, $properties, $limit, $page, $filter, $subset, $timeshift);
        if ($result)
        {
            return $result['info']['count'];
        }

        return false;
    }


    public function getSubset(ContentTypeDefinition $contentTypeDefinition, $parentId, $includeParent = true, $depth = null, $workspace = 'default', $viewName = 'default', $language = 'default', $timeshift = 0)
    {
        $subset = (int)$parentId . ',' . (int)$includeParent;
        if ($depth != null)
        {
            $subset .= ',' . $depth;
        }

        $result = $this->rawFetchRecords($contentTypeDefinition, $workspace, $viewName, $language, 'id', array(), null, 1, null, $subset, $timeshift);

        $records = array();

        foreach ($result['records'] as $item)
        {
            $record = $this->createRecordFromJSONResult($contentTypeDefinition, $item, $viewName, $workspace, $language, $this->validatePropertyNames);

            $records[$record->getID()] = $record;
        }

        return $records;
    }


    public function rawFetchRecords(ContentTypeDefinition $contentTypeDefinition, $workspace = 'default', $viewName = 'default', $language = 'default', $order = 'id', $properties = array(), $limit = null, $page = 1, $filter = null, $subset = null, $timeshift = 0)
    {
        if ($timeshift == 0 OR $timeshift > self::MAX_TIMESHIFT)
        {
            $timestamp = $this->getLastContentTypeChangeTimestamp($contentTypeDefinition->getName(), $workspace, $language, $timeshift);

            $filterToken     = '';
            $propertiesToken = json_encode($properties);
            if ($filter)
            {
                if (is_object($filter))
                {
                    if (get_class($filter) == 'AnyContent\Client\ContentFilter')
                    {
                        $filterToken = md5(json_encode($filter->getConditionsArray()));
                    }
                }
                else
                {
                    $filterToken = md5($filter);
                }

            }
            //$className = $this->getClassForContentType($contentTypeDefinition->getName());

            $cacheToken = $this->cachePrefix . '_records-json_' . $contentTypeDefinition->getName() . '_' . md5( $timestamp . '_' . $timeshift . '_' . $workspace . '_' . $viewName . '_' . $language) . '_' . md5($order . $propertiesToken . $limit . $page . $filterToken . $subset) . '_' . $this->getHeartBeat();

            if ($this->cache->contains($cacheToken))
            {
                return $this->cache->fetch($cacheToken);
            }
        }

        $url = 'content/' . $contentTypeDefinition->getName() . '/records/' . $workspace . '/' . $viewName;

        $queryParams              = array();
        $queryParams['language']  = $language;
        $queryParams['timeshift'] = $timeshift;
        $queryParams['order']     = $order;
        if ($order == 'property')
        {
            $queryParams['properties'] = join(',', $properties);
        }
        if ($limit)
        {
            $queryParams['limit'] = $limit;
            $queryParams['page']  = $page;
        }
        if ($filter)
        {
            if (is_object($filter))
            {
                if (get_class($filter) == 'AnyContent\Client\ContentFilter')
                {
                    $queryParams['filter'] = $filter->getConditionsArray();
                }
            }
            else
            {
                $queryParams['filter'] = $filter;
            }

        }
        if ($subset)
        {
            $queryParams['subset'] = $subset;
        }

        $options = array( 'query' => $queryParams );

        try
        {
            $request = $this->guzzle->get($url, null, $options);

            $result = $request->send()->json();
        }
        catch (\Exception $e)
        {
            throw new AnyContentClientException($e->getMessage(), AnyContentClientException::CLIENT_CONNECTION_ERROR);
        }

        if ($result)
        {
            if ($timeshift == 0 OR $timeshift > self::MAX_TIMESHIFT)
            {

                $this->cache->save($cacheToken, $result, $this->cacheSecondsData);

                // Put up to 10 record objects of this result into cache, as retrieval will be very likely
                $i = 0;
                foreach ($result['records'] AS $item)
                {

                    //$className = $this->getClassForContentType($contentTypeDefinition->getName());

                    $cacheToken = $this->cachePrefix . '_record_' . $contentTypeDefinition->getName() . '_' . $item['id'] . '_' . md5( $timestamp . '_' . $timeshift . '_' . $workspace . '_' . $viewName . '_' . $language) . '_' . $this->getHeartBeat();

                    if (!$this->cache->contains($cacheToken))
                    {
                        //$record = $this->createRecordFromJSONResult($contentTypeDefinition, $item, $viewName, $workspace, $language, $this->validatePropertyNames);
                        $this->cache->save($cacheToken, $item, $this->cacheSecondsData);
                        $i++;
                    }
                    if ($i > 10)
                    {
                        break;
                    }
                }
            }
        }

        return $result;

    }


    /**
     *
     * list = array of arrays with keys id, parent_id
     *
     * @param ContentTypeDefinition $contentTypeDefinition
     * @param array                 $list
     * @param string                $workspace
     * @param string                $language
     */
    public function sortRecords(ContentTypeDefinition $contentTypeDefinition, $list = array(), $workspace = 'default', $language = 'default')
    {

        $url = 'content/' . $contentTypeDefinition->getName() . '/sort-records/' . $workspace;

        try
        {
            $request = $this->guzzle->post($url, null, array( 'language' => $language, 'list' => json_encode($list) ));
            $result  = $request->send()->json();
        }
        catch (\Exception $e)
        {
            throw new AnyContentClientException($e->getMessage(), AnyContentClientException::CLIENT_CONNECTION_ERROR);
        }

        // repository info has changed
        $this->deleteRepositoryInfo($workspace, $language);

        return $result;
    }


    /**
     * @param $contentTypeDefinition
     * @param $result
     * @param $viewName
     * @param $workspace
     * @param $language
     *
     * @return Record
     * @throws \CMDL\CMDLParserException
     */
    protected function createRecordFromJSONResult($contentTypeDefinition, $result, $viewName, $workspace, $language, $validateProperties = true)
    {
        $classname = $this->getClassForContentType($contentTypeDefinition->getName());

        /** @var Record $record */
        $record = new $classname($contentTypeDefinition, $result['properties']['name'], $viewName, $workspace, $language);
        $record->setID($result['id']);
        $record->setRevision($result['info']['revision']);
        $record->setRevisionTimestamp($result['info']['revision_timestamp']);
        $record->setHash($result['info']['hash']);
        $record->setPosition($result['info']['position']);
        $record->setLevelWithinSortedTree($result['info']['level']);
        $record->setParentRecordId($result['info']['parent_id']);

        if ($validateProperties)
        {
            foreach ($result['properties'] AS $property => $value)
            {
                $record->setProperty($property, $value);
            }
        }
        else
        {
            $record->setProperties($result['properties']);
        }

        $record->setCreationUserInfo(new UserInfo($result['info']['creation']['username'], $result['info']['creation']['firstname'], $result['info']['creation']['lastname'], $result['info']['creation']['timestamp']));
        $record->setLastChangeUserInfo(new UserInfo($result['info']['lastchange']['username'], $result['info']['lastchange']['firstname'], $result['info']['lastchange']['lastname'], $result['info']['lastchange']['timestamp']));

        return $record;
    }


    /**
     * @param string $path
     *
     * @return Folder|bool
     */
    public function getFolder($path = '')
    {
        $url = 'files';

        $path = trim($path, '/');

        if ($path != '')
        {
            $url .= '/' . $path;
        }

        try
        {

            $request = $this->guzzle->get($url);

            $result = $request->send()->json();

        }
        catch (\Exception $e)
        {
            $response = $request->getResponse();
            if ($response && $response->getStatusCode() != 404)
            {
                throw new AnyContentClientException($e->getMessage(), AnyContentClientException::CLIENT_CONNECTION_ERROR);
            }
        }

        if ($result)
        {
            $folder = new Folder($path, $result);

            return $folder;
        }

        return false;
    }


    public function getFile($id)
    {
        $id = trim(trim($id, '/'));

        if ($id != '')
        {
            $pathinfo = pathinfo($id);

            $folder = $this->getFolder($pathinfo['dirname']);

            if ($folder)
            {
                return $folder->getFile($id);
            }
        }

        return false;
    }


    public function getBinary(File $file, $forceRepositoryRequest = false)
    {
        $url = $file->getUrl('binary', false);
        if (!$url OR $forceRepositoryRequest)
        {
            $url = 'file/' . trim($file->getId(), '/');

        }

        try
        {

            $request = $this->guzzle->get($url);
            $result  = $request->send();

            if ($result)
            {
                return ((binary)$result->getBody());
            }
        }
        catch (\Exception $e)
        {
            $response = $request->getResponse();
            if ($response && $response->getStatusCode() != 404)
            {
                throw new AnyContentClientException($e->getMessage(), AnyContentClientException::CLIENT_CONNECTION_ERROR);
            }
        }

        return false;

    }


    public function saveFile($id, $binary)
    {
        $url = 'file/' . trim($id, '/');

        try
        {

            $request = $this->guzzle->post($url, null, $binary);

            $request->send();

            return true;
        }
        catch (\Exception $e)
        {
            $response = $request->getResponse();
            if ($response && $response->getStatusCode() != 404)
            {
                throw new AnyContentClientException($e->getMessage(), AnyContentClientException::CLIENT_CONNECTION_ERROR);
            }
        }

        return false;
    }


    public function deleteFile($id, $deleteEmptyFolder = true)
    {
        $url = 'file/' . trim($id, '/');

        try
        {
            $request = $this->guzzle->delete($url);

            $request->send();

            if ($deleteEmptyFolder)
            {
                $dirName = pathinfo($id, PATHINFO_DIRNAME);

                return $this->deleteFolder($dirName);
            }

            return true;
        }
        catch (\Exception $e)
        {
            $response = $request->getResponse();
            if ($response && $response->getStatusCode() != 404)
            {
                throw new AnyContentClientException($e->getMessage(), AnyContentClientException::CLIENT_CONNECTION_ERROR);
            }
        }

        return false;
    }


    public function createFolder($path)
    {
        $url = 'files/' . trim($path, '/');

        try
        {
            $request = $this->guzzle->post($url);

            $request->send();

            return true;
        }
        catch (\Exception $e)
        {
            throw new AnyContentClientException($e->getMessage(), AnyContentClientException::CLIENT_CONNECTION_ERROR);
        }

        return false;
    }


    public function deleteFolder($path, $deleteIfNotEmpty = false)
    {
        $folder = $this->getFolder($path);

        if ($folder)
        {
            if ($folder->isEmpty() || $deleteIfNotEmpty)
            {

                $url = 'files/' . trim($path, '/');

                try
                {
                    $request = $this->guzzle->delete($url);

                    $request->send();

                    return true;
                }
                catch (\Exception $e)
                {
                    $response = $request->getResponse();
                    if ($response && $response->getStatusCode() != 404)
                    {
                        throw new AnyContentClientException($e->getMessage(), AnyContentClientException::CLIENT_CONNECTION_ERROR);
                    }
                }
            }
        }

        return false;
    }


    public function saveContentTypeCMDL($contentTypeName, $cmdl, $locale = null)
    {

        $url = 'content/' . $contentTypeName . '/cmdl';

        try
        {
            $request = $this->guzzle->post($url, null, array( 'cmdl' => $cmdl, 'locale' => $locale ));

            $request->send();

            $this->deleteHeartBeat();
            $this->deleteRepositoryInfo();

            return true;
        }
        catch (\Exception $e)
        {
            throw new AnyContentClientException($e->getMessage(), AnyContentClientException::CLIENT_CONNECTION_ERROR);
        }

        return false;
    }


    public function deleteContentType($contentTypeName)
    {
        $url = 'content/' . $contentTypeName;

        try
        {
            $request = $this->guzzle->delete($url);

            $request->send();

            $this->deleteHeartBeat();
            $this->deleteRepositoryInfo();

            return true;
        }
        catch (\Exception $e)
        {
            throw new AnyContentClientException($e->getMessage(), AnyContentClientException::CLIENT_CONNECTION_ERROR);
        }

        return false;
    }


    public function saveConfigTypeCMDL($configTypeName, $cmdl, $locale = null)
    {

        $url = 'config/' . $configTypeName . '/cmdl';

        try
        {
            $request = $this->guzzle->post($url, null, array( 'cmdl' => $cmdl, 'locale' => $locale ));

            $request->send();

            $this->deleteHeartBeat();
            $this->deleteRepositoryInfo();

            return true;
        }
        catch (\Exception $e)
        {
            throw new AnyContentClientException($e->getMessage(), AnyContentClientException::CLIENT_CONNECTION_ERROR);
        }

        return false;
    }


    public function deleteConfigType($configTypeName)
    {
        $url = 'config/' . $configTypeName;

        try
        {
            $request = $this->guzzle->delete($url);

            $request->send();

            $this->deleteHeartBeat();
            $this->deleteRepositoryInfo();

            return true;
        }
        catch (\Exception $e)
        {
            throw new AnyContentClientException($e->getMessage(), AnyContentClientException::CLIENT_CONNECTION_ERROR);
        }

        return false;
    }


    /**
     * Global token added to every cache token. If any client with the same cache prefix calls a cmdl changing method,
     * the token gets deleted which feels like a full flush.
     *
     * @return bool|mixed|string
     */
    protected function getHeartBeat()
    {
        // maybe make content type config type sensitive

        $cacheToken = $this->cachePrefix . '_heartbeat';

        if ($this->cache->contains($cacheToken))
        {
            return $this->cache->fetch($cacheToken);
        }
        $heartbeat = md5(microtime());
        $this->cache->save($cacheToken, $heartbeat);

        return $heartbeat;
    }


    protected function deleteHeartBeat()
    {

        $cacheToken = $this->cachePrefix . '_heartbeat';

        $this->cache->delete($cacheToken);
    }


    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }


    /**
     * @return string
     */
    public function getApiUser()
    {
        return $this->apiUser;
    }


    /**
     * @return string
     */
    public function getApiPassword()
    {
        return $this->apiPassword;
    }


    /**
     * @return \Guzzle\Http\Client
     */
    public function getGuzzle()
    {
        return $this->guzzle;
    }


    public function log($url, $cache, $code, $time, $size)
    {
        $this->log[] = array( 'url' => $url, 'cache' => $cache, 'code' => $code, 'time' => $time, 'size' => $size );
        if ($this->logger)
        {
            $this->logger->debug('AnyContent\Client Request ' . count($this->log) . ' ' . $url);

            $path = parse_url($url, PHP_URL_PATH);

            if ($cache)
            {
                $this->logger->info('AnyContent\Client Cache Hit (' . $path . ')');
            }
            else
            {
                $this->logger->info('AnyContent\Client Cache Miss - Status Code ' . $code . ' ' . $time . 'ms ' . $size . ' bytes (' . $path . ')');
            }

        }
    }


    public function getLog()
    {
        return $this->log;
    }


    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

}
