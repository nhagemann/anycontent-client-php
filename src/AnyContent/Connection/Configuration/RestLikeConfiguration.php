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
        $uri       = rtrim($uri, '/');
        $uri       = rtrim($uri, '/info');
        $uri       = $uri . '/';
        $this->uri = $uri;

    }


    public function addContentTypes()
    {
        /** @var RestLikeBasicReadOnlyConnection $connection */
        $connection = $this->getConnection();
        $info       = $connection->getRepositoryInfo();

        $contentTypes = array_keys($info['content']);

        $this->contentTypes = array_fill_keys($contentTypes, [ ]);

        return $this;
    }


    public function addConfigTypes()
    {

        /** @var RestLikeBasicReadOnlyConnection $connection */
        $connection = $this->getConnection();
        $info       = $connection->getRepositoryInfo();

        $configTypes = array_keys($info['config']);

        $this->configTypes = array_fill_keys($configTypes, [ ]);

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