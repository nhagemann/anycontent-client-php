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


    public function saveRecord(Record $record, $clippingName = 'default', $workspace = 'default', $language = 'none')
    {
        $contentTypeName = $record->getContentType();

        $url = 'content/' . $contentTypeName . '/' . $clippingName . '/' . $workspace . '/' . $language;

        $json = json_encode($record);

        $request = $this->guzzle->post($url, null, array( 'record' => $json ));

        $response = $request->send()->getBody();

        echo $response;

        //$result = $request->send()->json();
    }


    protected function record2JSON(Record $record)
    {

    }
}