<?php

namespace AnyContent\Connection;

use AnyContent\Client\Repository;
use AnyContent\Connection\Configuration\MySQLSchemalessConfiguration;
use KVMLogger\KVMLoggerFactory;

class MySQLSchemalessConnectionTest extends \PHPUnit_Framework_TestCase
{

    /** @var  MySQLSchemalessReadOnlyConnection */
    public $connection;


    /**
     * @throws \AnyContent\AnyContentClientException
     */
    public static function setUpBeforeClass()
    {

        // drop & create database
        $pdo = new \PDO('mysql:host=anycontent-client-phpunit-mysql;port=3306;charset=utf8', 'root', 'root');

        $pdo->exec('DROP DATABASE IF EXISTS phpunit');
        $pdo->exec('CREATE DATABASE phpunit');

        $configuration = new MySQLSchemalessConfiguration();

        $configuration->initDatabase('anycontent-client-phpunit-mysql', 'phpunit', 'root', 'root');
        $configuration->setCMDLFolder(__DIR__ . '/../../resources/ContentArchiveExample1/cmdl');
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

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
    }


    /**
     * @throws \AnyContent\AnyContentClientException
     */
    public function setUp()
    {

        $configuration = new MySQLSchemalessConfiguration();

        $configuration->initDatabase('anycontent-client-phpunit-mysql', 'phpunit', 'root', 'root');

        $configuration->setCMDLFolder(__DIR__ . '/../../resources/ContentArchiveExample1/cmdl');
        $configuration->setRepositoryName('phpunit');
        $configuration->addContentTypes();

        $connection = $configuration->createReadOnlyConnection();

        $this->connection = $connection;
        $repository       = new Repository('phpunit', $connection);

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');

    }


    public function testContentTypeNotSelected()
    {
        $connection = $this->connection;

        $this->setExpectedException('AnyContent\AnyContentClientException');
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
        $connection = $this->connection;

        $this->assertNotEquals(0, $this->connection->getLastModifiedDate());
    }
}