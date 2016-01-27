<?php
namespace AnyContent\Connection\Configuration;

use AnyContent\AnyContentClientException;
use AnyContent\Connection\RestLikeBasicReadOnlyConnection;
use AnyContent\Connection\RestLikeBasicReadWriteConnection;

class RestLikeConfiguration extends AbstractConfiguration
{

    protected $timeout = 30;

    protected $uri;


    /**
     * @return mixed
     */
    public function getUri()
    {
        if (!$this->uri)
        {
            throw new AnyContentClientException('Basi uri not set.');
        }

        return $this->uri;
    }


    /**
     * @param mixed $uri
     */
    public function setUri($uri)
    {
        $uri = rtrim($uri, '/');

        if (substr($uri, -5) == '/info')
        {
            $uri = substr($uri, 0, -5);
        }
        $uri       = $uri . '/';
        $this->uri = $uri;
    }


    public function addContentTypes($contentTypeNames = false)
    {

        if ($contentTypeNames===false)
        {
            /** @var RestLikeBasicReadOnlyConnection $connection */
            $connection = $this->getConnection();
            $info       = $connection->getRepositoryInfo();

            $contentTypeNames = array_keys($info['content']);
        }

        $this->contentTypes = array_fill_keys($contentTypeNames, [ ]);

        return $this;
    }


    public function addConfigTypes($configTypeNames = false)
    {
        if ($configTypeNames===false)
        {
            /** @var RestLikeBasicReadOnlyConnection $connection */
            $connection = $this->getConnection();
            $info       = $connection->getRepositoryInfo();

            $configTypeNames = array_keys($info['config']);

        }
        $this->configTypes = array_fill_keys($configTypeNames, [ ]);

        return $this;
    }


    public function createReadOnlyConnection()
    {
        return new RestLikeBasicReadOnlyConnection($this);
    }


    public function createReadWriteConnection()
    {
        return new  RestLikeBasicReadWriteConnection($this);
    }


    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }


    /**
     * @param int $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;

        return $this;
    }

}