<?php

declare(strict_types=1);

namespace Tests\AnyContent\Connection\MySQLSchemaless;

use AnyContent\Client\Config;
use AnyContent\Client\Record;
use AnyContent\Client\Repository;
use AnyContent\Connection\Configuration\MySQLSchemalessConfiguration;
use AnyContent\Connection\MySQLSchemalessReadWriteConnection;
use DateTime;
use PHPUnit\Framework\TestCase;

class MySQLSchemalessReadWriteRevisionConnectionTest extends TestCase
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

        $configuration = new MySQLSchemalessConfiguration();

        $configuration->initDatabase('anycontent-client-phpunit-mysql', 'phpunit', 'root', 'root');
        $configuration->setCMDLFolder(__DIR__ . '/../../../resources/ContentArchiveExample1/cmdl');
        $configuration->setRepositoryName('phpunit');
        $configuration->addContentTypes();
        $configuration->addConfigTypes();
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

    public function testDeleteContentTypeRevisions()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $records = $connection->getAllRecords();
        $this->assertCount(0, $records);

        $record = new Record($connection->getCurrentContentTypeDefinition(), 'Agency 5');
        $record->setId(5);

        $this->assertEquals('Agency 5', $record->getProperty('name'));

        $record->setProperty('name', 'Agency 51');

        $connection->saveRecord($record);

        $record = $connection->getRecord(5);

        $this->assertEquals('Agency 51', $record->getProperty('name'));

        $records = $connection->getAllRecords();
        $this->assertCount(1, $records);

        $revisions = $connection->getRevisionsOfRecord(5);
        $this->assertCount(1, $revisions);
        $connection->saveRecord($record);
        $revisions = $connection->getRevisionsOfRecord(5);

        $this->assertCount(2, $revisions);

        sleep(1); // Delete, what is currently no longer valid (at least a second)
        $connection->truncateContentTypeRevisions($connection->getCurrentContentTypeDefinition(), new DateTime());

        $revisions = $connection->getRevisionsOfRecord(5);

        $this->assertCount(1, $revisions);

        $record = $connection->getRecord(5);

        $this->assertEquals('Agency 51', $record->getProperty('name'));
    }

    public function testDeleteConfigTypeRevisions()
    {
        $connection = $this->connection;

        $revisions = $connection->getRevisionsOfConfig('config1');

        $this->assertEquals(false, $revisions);

        $config = new Config($connection->getConfigTypeDefinition('config1'));
        $connection->saveConfig($config);

        $revisions = $connection->getRevisionsOfConfig('config1');
        $this->assertCount(1, $revisions);

        $config->setProperty('companyname', 'MegaCorp');
        $connection->saveConfig($config);
        $revisions = $connection->getRevisionsOfConfig('config1');
        $this->assertCount(2, $revisions);

        $config = $this->connection->getConfig('config1');
        $this->assertEquals('MegaCorp', $config->getProperty('companyname'));

        sleep(1); // Delete, what is currently no longer valid (at least a second)
        $connection->truncateConfigTypeRevisions($connection->getConfigTypeDefinition('config1'), new DateTime());

        $revisions = $connection->getRevisionsOfConfig('config1');
        $this->assertCount(1, $revisions);
    }
}
