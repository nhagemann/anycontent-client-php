<?php

namespace AnyContent\Client;

use AnyContent\Client\AnyContentClientException;

use CMDL\Util;
use CMDL\ContentTypeDefinition;
use AnyContent\Client\Record;

class Client
{

    /**
     * @var \Guzzle\Http\Client;
     */
    protected $guzzle;

    protected $contentTypeList = null;


    /**
     * @param        $url
     * @param null   $user
     * @param null   $password
     * @param string $authType "Basic" (default), "Digest", "NTLM", or "Any".
     */
    public function __construct($url, $apiUser = null, $apiPassword = null, $authType = 'Basic')
    {
        // Create a client and provide a base URL
        $this->guzzle = new \Guzzle\Http\Client($url);

        if ($apiUser != null)
        {
            $this->guzzle->setDefaultOption('auth', array( $apiUser, $apiPassword, $authType ));
        }

        $request = $this->guzzle->get('');

        $result = $request->send()->json();

        $this->contentTypeList = $result;
    }


    public function setUserInfo($username, $firstname, $lastname)
    {
        $this->guzzle->setDefaultOption('query', array( 'userinfo' => array( 'username' => $username, 'firstname' => $firstname, 'lastname' => $lastname ) ));
    }


    public function getCMDL($contentTypeName)
    {
        if (array_key_exists($contentTypeName, $this->contentTypeList))
        {
            $request = $this->guzzle->get('cmdl/' . $contentTypeName);
            $result  = $request->send()->json();

            return $result['cmdl'];
        }
        else
        {
            throw AnyContentClientException('', AnyContentClientException::ANYCONTENT_UNKNOW_CONTENT_TYPE);
        }

    }


    public function saveRecord(Record $record, $workspace = 'default', $clippingName = 'default', $language = 'none')
    {
        $contentTypeName = $record->getContentType();

        $url = 'content/' . $contentTypeName . '/' . $workspace . '/' . $clippingName;

        $json = json_encode($record);

        $request = $this->guzzle->post($url, null, array( 'record' => $json, 'language' => $language ));

        $result = $request->send()->json();

        return (int)$result;

    }


    public function getRecord(ContentTypeDefinition $contentTypeDefinition, $id, $workspace = 'default', $clippingName = 'default', $language = 'none', $timeshift = 0)
    {

        $url = 'content/' . $contentTypeDefinition->getName() . '/' . $id . '/' . $workspace . '/' . $clippingName;

        $request = $this->guzzle->get($url, null, array( 'language' => $language, 'timeshift' => $timeshift ));

        $result = $request->send()->json();

        $record = new Record($contentTypeDefinition, $result['properties']['name'], $clippingName, $workspace, $language);
        $record->setID($result['id']);
        $record->setRevision($result['info']['revision']);

        foreach ($result['properties'] AS $property => $value)
        {
            $record->setProperty($property, $value);
        }

        return $record;
    }


    public function getRecords(ContentTypeDefinition $contentTypeDefinition, $workspace = 'default', $clippingName = 'default', $language = 'none', $order = 'id', $properties = array(), $limit = null, $page = 1, $timeshift = 0)
    {
        $url = 'content/' . $contentTypeDefinition->getName() . '/' . $workspace . '/' . $clippingName;

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

        $request = $this->guzzle->get($url, null, $queryParams);
        $result  = $request->send()->json();

        $records = array();

        foreach ($result as $item)
        {
            $record = new Record($contentTypeDefinition, $item['properties']['name'], $clippingName, $workspace, $language);
            $record->setID($item['id']);
            $record->setRevision($item['info']['revision']);

            foreach ($item['properties'] AS $property => $value)
            {
                $record->setProperty($property, $value);
            }

            $records[$record->getID()] = $record;
        }

        return $records;

    }

}