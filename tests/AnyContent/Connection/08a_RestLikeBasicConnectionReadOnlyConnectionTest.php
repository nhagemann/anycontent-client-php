<?php

namespace AnyContent\Connection;

use AnyContent\Client\Repository;
use AnyContent\Connection\Configuration\MySQLSchemalessConfiguration;
use AnyContent\Connection\Configuration\RecordsFileConfiguration;
use AnyContent\Connection\Configuration\RestLikeConfiguration;
use AnyContent\Connection\RecordsFileReadOnlyConnection;
use KVMLogger\KVMLoggerFactory;
use KVMLogger\KVMLogger;

class RestLikeBasicConnectionReadOnlyTest extends \PHPUnit_Framework_TestCase
{

    /** @var  RestLikeBasicReadOnlyConnection */
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
        $configuration->setRepositoryName('phpunit');

        $configuration->importCMDL(__DIR__ . '/../../resources/RestLikeBasicConnectionTests');

        $configuration->addContentTypes();

        $connection = $configuration->createReadWriteConnection();

        $repository = new Repository('phpunit', $connection);
        $repository->selectContentType('content1');

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


    public function setUp()
    {

        $configuration = new RestLikeConfiguration();

        $configuration->setUri(getenv('PHPUNIT_RESTLIKE_URI'));
        $connection = $configuration->createReadOnlyConnection();

        $configuration->addContentTypes();

        $this->connection = $connection;

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');

    }


    public function testContentTypeNotSelected()
    {
        KVMLogger::instance()->debug(__METHOD__);

        $connection = $this->connection;

        $this->setExpectedException('AnyContent\AnyContentClientException');
        $this->assertEquals(12, $connection->countRecords());
    }


    public function testContentTypeNames()
    {
        KVMLogger::instance()->debug(__METHOD__);

        $connection = $this->connection;

        $contentTypeNames = $connection->getContentTypeNames();

        $this->assertContains('content1', $contentTypeNames);
    }


    public function testContentTypeDefinitions()
    {
        KVMLogger::instance()->debug(__METHOD__);

        $connection = $this->connection;

        $contentTypes = $connection->getContentTypeDefinitions();

        $this->assertArrayHasKey('content1', $contentTypes);

        $contentType = $contentTypes['content1'];
        $this->assertInstanceOf('CMDL\ContentTypeDefinition', $contentType);
    }


    public function testCountRecords()
    {
        KVMLogger::instance()->debug(__METHOD__);

        $connection = $this->connection;

        $connection->selectContentType('content1');

        $this->assertEquals(3, $connection->countRecords());

    }


    public function testGetRecord()
    {
        KVMLogger::instance()->debug(__METHOD__);

        $connection = $this->connection;

        $connection->selectContentType('content1');

        $record = $connection->getRecord(5);

        $this->assertInstanceOf('AnyContent\Client\Record', $record);

        $this->assertEquals('Agency 5', $record->getProperty('name'));

        $record = $connection->getRecord(99);

        $this->assertFalse($record);

    }


    public function testGetRecords()
    {
        KVMLogger::instance()->debug(__METHOD__);

        $connection = $this->connection;

        $connection->selectContentType('content1');

        $records = $connection->getAllRecords();

        $this->assertCount(3, $records);

        foreach ($records as $record) {
            $id          = $record->getId();
            $fetchRecord = $connection->getRecord($id);
            $this->assertEquals($id, $fetchRecord->getId());
        }
    }


    public function testLastModified()
    {
        $connection = $this->connection;

        $this->assertInternalType('string', $this->connection->getLastModifiedDate());
    }


    public function testGetFilteredRecords()
    {
        KVMLogger::instance()->debug(__METHOD__);

        $connection = $this->connection;

        $repository = new Repository('phpunit', $connection);
        $connection->selectContentType('content1');

        $records = $repository->getRecords();

        $this->assertCount(3, $records);

        $records = $repository->getRecords('name *= Agency');

        $this->assertCount(3, $records);

        $records = $repository->getRecords('name = Bgency');

        $this->assertCount(0, $records);
    }
}