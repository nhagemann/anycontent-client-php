<?php
namespace AnyContent\Connection\Configuration;

use AnyContent\AnyContentClientException;
use AnyContent\Connection\RecordsFileFirebaseReadOnlyConnection;

class RecordsFileFirebaseConfiguration extends AbstractConfiguration
{

    protected $baseUri;

    protected $token;

    protected $defaultPath;

    protected $maxNumberOfSingleRecordFetches = 5;


    public function setFirebase($baseUri, $token, $defaultPath)
    {
        $this->baseUri     = $baseUri;
        $this->token       = $token;
        $this->setDefaultPath($defaultPath);

    }


    /**
     * @return mixed
     */
    public function getBaseUri()
    {
        return $this->baseUri;
    }


    /**
     * @param mixed $baseUri
     */
    public function setBaseUri($baseUri)
    {
        $this->baseUri = $baseUri;
    }


    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }


    /**
     * @param mixed $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }


    /**
     * @return mixed
     */
    public function getDefaultPath()
    {
        return $this->defaultPath;
    }


    /**
     * @param mixed $defaultPath
     */
    public function setDefaultPath($defaultPath)
    {
        $path = trim($defaultPath, '/');
        if ($path == '')
        {
            $this->defaultPath = '/';
        }
        else
        {
            $this->defaultPath = '/' . $path . '/';
        }
    }


    /**
     * @return int
     */
    public function getMaxNumberOfSingleRecordFetches()
    {
        return $this->maxNumberOfSingleRecordFetches;
    }


    /**
     * @param int $maxNumberOfSingleRecordFetches
     */
    public function setMaxNumberOfSingleRecordFetches($maxNumberOfSingleRecordFetches)
    {
        $this->maxNumberOfSingleRecordFetches = $maxNumberOfSingleRecordFetches;
    }


    public function addContentType($contentTypeName, $keyCMDL, $keyRecords)
    {

        $this->contentTypes[$contentTypeName] = [ 'keyCMDL' => $keyCMDL, 'keyRecords' => $keyRecords ];

        return $this;
    }



    public function getUriCMDLForContentType($contentTypeName)
    {
        if ($this->hasContentType($contentTypeName))
        {
            return $this->contentTypes[$contentTypeName]['keyCMDL'];
        }

        throw new AnyContentClientException ('Unknown content type ' . $contentTypeName);
    }


    public function getUriRecords($contentTypeName)
    {
        if ($this->hasContentType($contentTypeName))
        {
            return $this->contentTypes[$contentTypeName]['keyRecords'];
        }

        throw new AnyContentClientException ('Unknown content type ' . $contentTypeName);
    }


    public function createReadOnlyConnection()
    {
        return new RecordsFileFirebaseReadOnlyConnection($this);
    }

}