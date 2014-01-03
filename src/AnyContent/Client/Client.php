<?php

namespace AnyContent\Client;

use AnyContent\Client\AnyContentClientException;

use CMDL\Util;
use CMDL\ContentTypeDefinition;
use AnyContent\Client\Record;
use AnyContent\Client\Repository;
use AnyContent\Client\UserInfo;
use AnyContent\Client\ContentFilter;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\ArrayCache;

class Client
{

    const RECORDS_ORDER_MODE_LIST = 1;
    const RECORDS_ORDER_MODE_TREE = 2;

    const MAX_TIMESHIFT = 315532800; // roundabout 10 years, equals to 1.1.1980
    /**
     * @var \Guzzle\Http\Client;
     */
    protected $guzzle;

    protected $repositoryInfo = null;

    protected $contentTypeList = null;

    /**
     * @var Cache;
     */
    protected $cache;

    protected $cachePrefix = '';

    protected $cacheSecondsCMDL = 3600;
    protected $cacheSecondsInfo = 15;
    protected $cacheSecondsDefault = 600;


    /**
     * @param        $url
     * @param null   $user
     * @param null   $password
     * @param string $authType "Basic" (default), "Digest", "NTLM", or "Any".
     */
    public function __construct($url, $apiUser = null, $apiPassword = null, $authType = 'Basic', Cache $cache = null, $secondsIgnoringEventuallyCMDLUpdates = 3600, $secondsIgnoringEventuallyConcurrentWriteRequests = 15, $secondsStoringRecordsInCache = 600)
    {
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
            //$this->cache = new ArrayCache();
            //$this->cache = new \Doctrine\Common\Cache\ApcCache();

            $memcache = new \Memcached();
            $memcache->addServer('localhost', 11211);

            $cacheDriver = new \Doctrine\Common\Cache\MemcachedCache();
            $cacheDriver->setMemcached($memcache);

            $this->cache = $cacheDriver;
        }
        $this->cacheSecondsCMDL    = $secondsIgnoringEventuallyCMDLUpdates;
        $this->cacheSecondsInfo    = $secondsIgnoringEventuallyConcurrentWriteRequests;
        $this->cacheSecondsDefault = $secondsStoringRecordsInCache;

        $this->cachePrefix = 'client_' . md5($url . $apiUser . $apiPassword);

        //$request = $this->guzzle->get('');

        //$result = $request->send()->json();

        //$this->repositoryInfo  = $result;
        $result                = $this->getRepositoryInfo();
        $this->contentTypeList = array();
        foreach ($result['content'] as $name => $item)
        {
            $this->contentTypeList[$name] = $item['title'];
        }
    }


    public function setUserInfo(UserInfo $userInfo)
    {
        $this->guzzle->setDefaultOption('query', array( 'userinfo' => array( 'username' => $userInfo->getUsername(), 'firstname' => $userInfo->getFirstname(), 'lastname' => $userInfo->getLastname() ) ));
    }


    public function getRepository()
    {
        return new Repository($this);
    }


    public function getRepositoryInfo($workspace = 'default', $language = 'none', $timeshift = 0)
    {

        if ($timeshift == 0 OR $timeshift > self::MAX_TIMESHIFT)
        {
            $cacheToken = $this->cachePrefix . '_info_' . $workspace . '_' . $language . '_' . $timeshift;

            if ($this->cache->contains($cacheToken))
            {
                return $this->cache->fetch($cacheToken);
            }
        }

        /*
        if ($workspace == 'default' AND $language == 'none' AND $timeshift == 0 AND $this->repositoryInfo != null)
        {
            return $this->repositoryInfo;
        }     */

        $url = 'info/' . $workspace;

        $options = array( 'query' => array( 'language' => $language, 'timeshift' => $timeshift ) );
        $request = $this->guzzle->get($url, null, $options);

        $result = $request->send()->json();

        if ($this->cacheSecondsInfo != 0)
        {
            if ($timeshift == 0)
            {
                $this->cache->save($cacheToken, $result, $this->cacheSecondsInfo);
            }
            if ($timeshift > self::MAX_TIMESHIFT)
            {
                // timeshifted info result can get stored longer, since they won't change in the future, but they have to be absolute (>MAX_TIMESHIFT)
                $this->cache->save($cacheToken, $result, $this->cacheSecondsDefault);
            }
        }

        return $result;
    }


    public function getLastChangeTimestamp(ContentTypeDefinition $contentTypeDefinition, $workspace = 'default', $language = 'none', $timeshift = 0)
    {
        $info = $this->getRepositoryInfo($workspace, $language, $timeshift);

        if (array_key_exists($contentTypeDefinition->getName(), $info['content']))
        {
            return ($info['content'][$contentTypeDefinition->getName()]['lastchange_content']);
        }

        return time();
    }


    public function getContentTypeList()
    {
        return $this->contentTypeList;
    }


    public function getCMDL($contentTypeName)
    {
        if (array_key_exists($contentTypeName, $this->contentTypeList))
        {
            $cacheToken = $this->cachePrefix . '_cmdl_' . $contentTypeName;

            if ($this->cache->contains($cacheToken))
            {
                return $this->cache->fetch($cacheToken);
            }

            $request = $this->guzzle->get('cmdl/' . $contentTypeName);
            $result  = $request->send()->json();

            if ($this->cacheSecondsCMDL != 0)
            {
                $this->cache->save($cacheToken, $result['cmdl'], $this->cacheSecondsCMDL);
            }

            return $result['cmdl'];
        }
        else
        {
            throw new AnyContentClientException('', AnyContentClientException::ANYCONTENT_UNKNOW_CONTENT_TYPE);
        }

    }


    public function saveRecord(Record $record, $workspace = 'default', $clippingName = 'default', $language = 'none')
    {
        $contentTypeName = $record->getContentType();

        $url = 'content/' . $contentTypeName . '/records/' . $workspace . '/' . $clippingName;

        $json = json_encode($record);

        $request = $this->guzzle->post($url, null, array( 'record' => $json, 'language' => $language ));

        $result = $request->send()->json();

        // repository info has changed
        $cacheToken = $this->cachePrefix . '_info_' . $workspace . '_' . $language . '_0';
        $this->cache->delete($cacheToken);

        return (int)$result;

    }


    public function getRecord(ContentTypeDefinition $contentTypeDefinition, $id, $workspace = 'default', $clippingName = 'default', $language = 'none', $timeshift = 0)
    {

        $url = 'content/' . $contentTypeDefinition->getName() . '/record/' . $id . '/' . $workspace . '/' . $clippingName;

        $options = array( 'query' => array( 'language' => $language, 'timeshift' => $timeshift ) );
        $request = $this->guzzle->get($url, null, $options);

        $result = $request->send()->json();

        $record = $this->createRecordFromJSONResult($contentTypeDefinition, $result['record'], $clippingName, $workspace, $language);

        return $record;
    }


    public function deleteRecord(ContentTypeDefinition $contentTypeDefinition, $id, $workspace = 'default', $language = 'none')
    {
        $url     = 'content/' . $contentTypeDefinition->getName() . '/record/' . $id . '/' . $workspace;
        $options = array( 'query' => array( 'language' => $language ) );
        $request = $this->guzzle->delete($url, null, $options);

        $result = $request->send()->json();

        // repository info has changed
        $cacheToken = $this->cachePrefix . '_info_' . $workspace . '_' . $language . '_0';
        $this->cache->delete($cacheToken);

        return $result;
    }


    public function getRecords(ContentTypeDefinition $contentTypeDefinition, $workspace = 'default', $clippingName = 'default', $language = 'none', $order = 'id', $properties = array(), $limit = null, $page = 1, ContentFilter $filter = null, $timeshift = 0)
    {
        $result = $this->requestRecords($contentTypeDefinition, $workspace, $clippingName, $language, $order, $properties, $limit, $page, $filter, $timeshift);

        $records = array();

        foreach ($result['records'] as $item)
        {
            $record = $this->createRecordFromJSONResult($contentTypeDefinition, $item, $clippingName, $workspace, $language);

            $records[$record->getID()] = $record;
        }

        return $records;
    }


    public function countRecords(ContentTypeDefinition $contentTypeDefinition, $workspace = 'default', $clippingName = 'default', $language = 'none', $order = 'id', $properties = array(), $limit = null, $page = 1, ContentFilter $filter = null, $timeshift = 0)
    {
        $result = $this->requestRecords($contentTypeDefinition, $workspace, $clippingName, $language, $order, $properties, $limit, $page, $filter, $timeshift);
        if ($result)
        {
            return $result['info']['count'];
        }

        return false;
    }


    public function requestRecords(ContentTypeDefinition $contentTypeDefinition, $workspace = 'default', $clippingName = 'default', $language = 'none', $order = 'id', $properties = array(), $limit = null, $page = 1, ContentFilter $filter = null, $timeshift = 0)
    {
        if ($timeshift == 0 OR $timeshift > self::MAX_TIMESHIFT)
        {
            $timestamp = $this->getLastChangeTimestamp($contentTypeDefinition, $workspace, $language, $timeshift);

            $filterToken ='';
            $propertiesToken = json_encode($properties);
            if ($filter)
            {
                $filterToken = md5(json_encode($filter->getConditionsArray()));
            }

            $cacheToken = $this->cachePrefix . '_records_' . $contentTypeDefinition->getName().'_'.$timestamp . '_' . $workspace . '_' . $clippingName . '_' . $language . '_' . $timeshift . '_' . md5($order . $propertiesToken . $limit . $page . $filterToken);

            if ($this->cache->contains($cacheToken))
            {
                return $this->cache->fetch($cacheToken);
            }
        }

        $url = 'content/' . $contentTypeDefinition->getName() . '/records/' . $workspace . '/' . $clippingName;

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
            $queryParams['filter'] = $filter->getConditionsArray();
        }

        $options = array( 'query' => $queryParams );

        $request = $this->guzzle->get($url, null, $options);

        $result = $request->send()->json();

        if ($timeshift == 0 OR $timeshift > self::MAX_TIMESHIFT)
        {
            $this->cache->save($cacheToken, $result, $this->cacheSecondsDefault);
        }

        return $result;

    }


    /* public function addContentQuery()
     {

     }


     public function startContentQueries()
     {

     }


     public function contentQueriesAreRunning()
     {

     }


     public function saveRecordsOrder($order, $mode = self::RECORDS_ORDER_MODE_LIST)
     {
         array( 5, 6, 8, 2 );
         array( 1 => 2, 2 => 0, 3 => 4 ); // parents
     }*/

    protected function createRecordFromJSONResult($contentTypeDefinition, $result, $clippingName, $workspace, $language)
    {
        $record = new Record($contentTypeDefinition, $result['properties']['name'], $clippingName, $workspace, $language);
        $record->setID($result['id']);
        $record->setRevision($result['info']['revision']);

        foreach ($result['properties'] AS $property => $value)
        {
            $record->setProperty($property, $value);
        }

        $record->setCreationUserInfo(new UserInfo($result['info']['creation']['username'], $result['info']['creation']['firstname'], $result['info']['creation']['lastname'], $result['info']['creation']['timestamp']));
        $record->setLastChangeUserInfo(new UserInfo($result['info']['lastchange']['username'], $result['info']['lastchange']['firstname'], $result['info']['lastchange']['lastname'], $result['info']['lastchange']['timestamp']));

        return $record;
    }

}