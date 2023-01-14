<?php

declare(strict_types=1);

namespace AnyContent\Connection\Configuration;

use AnyContent\AnyContentClientException;
use AnyContent\Connection\AbstractConnection;

class AbstractConfiguration
{
    protected array $contentTypes = [ ];

    protected array $configTypes = [ ];

    protected ?AbstractConnection $connection = null;

    public function hasContentType($contentTypeName): bool
    {
        return array_key_exists($contentTypeName, $this->contentTypes);
    }

    public function getContentTypeNames(): array
    {
        return array_keys($this->contentTypes);
    }

    public function hasConfigType($configTypeName): bool
    {
        return array_key_exists($configTypeName, $this->configTypes);
    }

    public function getConfigTypeNames(): array
    {
        return array_keys($this->configTypes);
    }

    public function apply(AbstractConnection $connection): void
    {
        $this->connection = $connection;
    }

    protected function getConnection(): AbstractConnection
    {
        if (!$this->connection) {
            throw new AnyContentClientException('You need to create a connection first.');
        }

        return $this->connection;
    }
}
