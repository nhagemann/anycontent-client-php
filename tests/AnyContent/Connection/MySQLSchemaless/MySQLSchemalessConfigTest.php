<?php

declare(strict_types=1);

namespace Tests\AnyContent\Connection\MySQLSchemaless;

use AnyContent\Client\Repository;
use AnyContent\Connection\Configuration\MySQLSchemalessConfiguration;
use AnyContent\Connection\MySQLSchemalessReadWriteConnection;
use PHPUnit\Framework\TestCase;

class MySQLSchemalessConfigTest extends TestCase
{
    /** @var  MySQLSchemalessReadWriteConnection */
    public $connection;

    /**
     * @throws \AnyContent\AnyContentClientException
     */
    public static function setUpBeforeClass(): void
    {
        // drop & create database
        $pdo = new \PDO('mysql:host=anycontent-client-phpunit-mysql;port=3306;charset=utf8', 'root', 'root');

        $pdo->exec('DROP DATABASE IF EXISTS phpunit');
        $pdo->exec('CREATE DATABASE phpunit');
    }

    /**
     * @throws \AnyContent\AnyContentClientException
     */
    public function setUp(): void
    {
        $configuration = new MySQLSchemalessConfiguration();

        $configuration->initDatabase('anycontent-client-phpunit-mysql', 'phpunit', 'root', 'root');

        $configuration->setCMDLFolder(__DIR__ . '/../../../resources/ContentArchiveExample1/cmdl');
        $configuration->setRepositoryName('phpunit');
        $configuration->addContentTypes();
        $configuration->addConfigTypes();

        $connection = $configuration->createReadWriteConnection();

        $this->connection = $connection;
        $repository       = new Repository('phpunit', $connection);
        $this->assertEquals($repository, $this->connection->getRepository());
    }

    public function testConfigSameConnection()
    {
        $connection = $this->connection;

        $config = $connection->getConfig('config1');

        $this->assertInstanceOf('AnyContent\Client\Config', $config);

        $this->assertEquals('', $config->getProperty('city'));

        $config->setProperty('city', 'Frankfurt');

        $connection->saveConfig($config);

        $config = $connection->getConfig('config1');

        $this->assertInstanceOf('AnyContent\Client\Config', $config);

        $this->assertEquals('Frankfurt', $config->getProperty('city'));
    }

    public function testConfigNewConnection()
    {
        $connection = $this->connection;

        $config = $connection->getConfig('config1');

        $this->assertInstanceOf('AnyContent\Client\Config', $config);

        $this->assertEquals('Frankfurt', $config->getProperty('city'));
    }

    public function testViewsConfigSameConnection()
    {
        $connection = $this->connection;

        $connection->selectView('test');

        $config = $connection->getConfig('config1');

        $this->assertInstanceOf('AnyContent\Client\Config', $config);

        $this->assertEquals('', $config->getProperty('comment'));

        $config->setProperty('comment', 'Test');

        $connection->saveConfig($config);

        $config = $connection->getConfig('config1');

        $this->assertInstanceOf('AnyContent\Client\Config', $config);

        $this->assertEquals('Test', $config->getProperty('comment'));

        $connection->selectView('default');

        $config = $connection->getConfig('config1');

        $this->assertInstanceOf('AnyContent\Client\Config', $config);

        $this->assertEquals('Frankfurt', $config->getProperty('city'));
    }

    public function testProtectedProperties()
    {
        $connection = $this->connection;

        $config = $connection->getConfig('config1');

        $config->setProperty('ranking', 1);

        $this->assertEquals(1, $config->getProperty('ranking'));

        $connection->saveConfig($config);

        $config = $connection->getConfig('config1');

        $this->assertEquals('', $config->getProperty('ranking'));
    }
}
