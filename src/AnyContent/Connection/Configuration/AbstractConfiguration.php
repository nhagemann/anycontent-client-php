<?php

namespace AnyContent\Connection\Configuration;

use AnyContent\AnyContentClientException;
use AnyContent\Connection\AbstractConnection;

class AbstractConfiguration
{
    protected $contentTypes = [ ];

    protected $configTypes = [ ];

    /** @var  AbstractConnection */
    protected $connection;

    public function hasContentType($contentTypeName)
    {
        return array_key_exists($contentTypeName, $this->contentTypes);
    }

    public function getContentTypeNames()
    {
        return array_keys($this->contentTypes);
    }

    public function hasConfigType($configTypeName)
    {
        return array_key_exists($configTypeName, $this->configTypes);
    }

    public function getConfigTypeNames()
    {
        return array_keys($this->configTypes);
    }

    public function apply(AbstractConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return AbstractConnection
     */
    protected function getConnection()
    {
        if (!$this->connection) {
            throw new AnyContentClientException('You need to create a connection first.');
        }

        return $this->connection;
    }
}
