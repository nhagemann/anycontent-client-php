<?php

declare(strict_types=1);

namespace Tests\AnyContent\Connection\MySQLSchemaless;

use AnyContent\Client\Repository;
use AnyContent\Connection\Configuration\MySQLSchemalessConfiguration;
use AnyContent\Connection\MySQLSchemalessReadOnlyConnection;
use KVMLogger\KVMLoggerFactory;
use PHPUnit\Framework\TestCase;

class MySQLSchemalessConnectionTest extends TestCase
{
    /** @var  MySQLSchemalessReadOnlyConnection */
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

        $connection = $configuration->createReadWriteConnection();

        $repository = new Repository('phpunit', $connection);
        $repository->selectContentType('profiles');

        $record = $repository->createRecord('Agency 1', 1);
        $repository->saveRecord($record);

        $record = $repository->createRecord('Agency 2', 2);
        $repository->saveRecord($record);

        $record = $repository->createRecord('Agency 5', 5);
        $repository->saveRecord($record);

        $repository->selectWorkspace('live');

        $record = $repository->createRecord('Agency 1', 1);
        $repository->saveRecord($record);

        $record = $repository->createRecord('Agency 2', 2);
        $repository->saveRecord($record);

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../../../tmp');
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

        $connection = $configuration->createReadOnlyConnection();

        $this->connection = $connection;
        $repository = new Repository('phpunit', $connection);
        $this->assertEquals($repository, $this->connection->getRepository());

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../../../tmp');
    }

    public function testContentTypeNotSelected()
    {
        $connection = $this->connection;

        $this->expectException('AnyContent\AnyContentClientException');
        $this->assertEquals(12, $connection->countRecords());
    }

    public function testContentTypeNames()
    {
        $connection = $this->connection;

        $contentTypeNames = $connection->getContentTypeNames();

        $this->assertContains('profiles', $contentTypeNames);
    }

    public function testContentTypeDefinitions()
    {
        $connection = $this->connection;

        $contentTypes = $connection->getContentTypeDefinitions();

        $this->assertArrayHasKey('profiles', $contentTypes);

        $contentType = $contentTypes['profiles'];
        $this->assertInstanceOf('CMDL\ContentTypeDefinition', $contentType);
    }

    public function testCountRecords()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $this->assertEquals(3, $connection->countRecords());
    }

    public function testGetRecord()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $record = $connection->getRecord(5);

        $this->assertInstanceOf('AnyContent\Client\Record', $record);

        $this->assertEquals('Agency 5', $record->getProperty('name'));
    }

    public function testGetRecords()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $records = $connection->getAllRecords();

        $this->assertCount(3, $records);

        foreach ($records as $record) {
            $id          = $record->getID();
            $fetchRecord = $connection->getRecord($id);
            $this->assertEquals($id, $fetchRecord->getID());
        }
    }

    public function testWorkspaces()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $connection->selectWorkspace('live');

        $records = $connection->getAllRecords();

        $this->assertCount(2, $records);
    }

    public function testLastModified()
    {
        $this->assertNotEquals(0, $this->connection->getLastModifiedDate());
    }
}
