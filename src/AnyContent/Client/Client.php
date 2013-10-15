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

    protected $user = null;

    protected $password = null;

    protected $contentTypeList = null;


    public function __construct($url, $user = null, $password = null)
    {
        // Create a client and provide a base URL
        $this->guzzle = new \Guzzle\Http\Client($url);

        $request = $this->guzzle->get('')->setAuth($this->user, $this->password);

        $result = $request->send()->json();

        $this->contentTypeList = $result;
    }

    public function getCMDL($contentTypeName)
    {
        if (array_key_exists($contentTypeName,$this->contentTypeList))
        {
            $request = $this->guzzle->get('cmdl/'.$contentTypeName)->setAuth($this->user, $this->password);
            $result = $request->send()->json();
            return $result['cmdl'];
        }
        else
        {
            throw AnyContentClientException('',AnyContentClientException::ANYCONTENT_UNKNOW_CONTENT_TYPE);
        }

    }


    public function saveRecord(Record $record)
    {
        $contentTypeName = $record->getContentType();
        $json = json_encode($record);
        $request = $this->guzzle->post('content/'.$contentTypeName,null,array('record'=>$json));

        $response = $request->send()->getBody();

        echo $response;

        //$result = $request->send()->json();
    }


    protected function record2JSON(Record $record)
    {

    }
}