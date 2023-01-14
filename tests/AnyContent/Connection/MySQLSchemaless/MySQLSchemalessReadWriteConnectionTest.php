<?php

namespace Tests\AnyContent\Connection\MySQLSchemaless;

use AnyContent\Client\Record;
use AnyContent\Client\Repository;
use AnyContent\Connection\Configuration\MySQLSchemalessConfiguration;
use AnyContent\Connection\MySQLSchemalessReadWriteConnection;
use KVMLogger\KVMLogger;
use KVMLogger\KVMLoggerFactory;
use PHPUnit\Framework\TestCase;

class MySQLSchemalessReadWriteConnectionTest extends TestCase
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

        $connection = $configuration->createReadWriteConnection();

        $this->connection = $connection;
        $repository       = new Repository('phpunit', $connection);
        $this->assertEquals($repository, $this->connection->getRepository());

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../../../tmp');
    }

    public function testSaveRecordSameConnection()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $record = new Record($connection->getCurrentContentTypeDefinition(), 'Agency 5');
        $record->setId(5);

        $this->assertEquals('Agency 5', $record->getProperty('name'));

        $record->setProperty('name', 'Agency 51');

        $connection->saveRecord($record);

        $record = $connection->getRecord(5);

        $this->assertEquals('Agency 51', $record->getProperty('name'));
    }

    public function testSaveRecordNewConnection()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $record = $connection->getRecord(5);

        $this->assertEquals('Agency 51', $record->getProperty('name'));
    }

    public function testAddRecord()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $record = new Record($connection->getCurrentContentTypeDefinition(), 'test');

        $id = $connection->saveRecord($record);

        $this->assertEquals(6, $record->getID());
        $this->assertEquals(6, $id);
    }

    public function testSaveRecordsSameConnection()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $this->assertEquals(2, $connection->countRecords());

        $records = [];

        for ($i = 1; $i <= 5; $i++) {
            $record    = new Record($connection->getCurrentContentTypeDefinition(), 'Test ' . $i);
            $records[] = $record;
        }

        $connection->saveRecords($records);

        $this->assertEquals(7, $connection->countRecords());
    }

    public function testSaveRecordsNewConnection()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $this->assertEquals(7, $connection->countRecords());
    }

    public function testDeleteRecord()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $result = $connection->deleteRecord(5);

        $this->assertEquals(5, $result);
        $this->assertEquals(6, $connection->countRecords());

        $result = $connection->deleteRecord(999);

        $this->assertEquals(false, $result);
        $this->assertEquals(6, $connection->countRecords());
    }

    public function testDeleteRecordNewConnection()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $this->assertEquals(6, $connection->countRecords());
    }

    public function testDeleteRecords()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $result = $connection->deleteRecords([6, 999]);

        $this->assertCount(1, $result);
        $this->assertEquals(5, $connection->countRecords());
    }

    public function testDeleteRecordsNewConnection()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $this->assertEquals(5, $connection->countRecords());
    }

    public function testDeleteAllRecords()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $result = $connection->deleteAllRecords();

        $this->assertCount(5, $result);
        $this->assertEquals(0, $connection->countRecords());
    }

    public function testDeleteAllRecordsNewConnection()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $this->assertEquals(0, $connection->countRecords());
    }

    public function testProtectedProperties()
    {
        KVMLogger::instance()->debug(__METHOD__);

        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $record = new Record($connection->getCurrentContentTypeDefinition(), 'test');

        $record->setProperty('ranking', 1);

        $this->assertEquals(1, $record->getProperty('ranking'));

        $id = $connection->saveRecord($record);

        $record = $connection->getRecord($id);

        $this->assertEquals('', $record->getProperty('ranking'));
    }

    public function testOmmittedProperties()
    {
        KVMLogger::instance()->debug(__METHOD__);

        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $record = new Record($connection->getCurrentContentTypeDefinition(), 'test');

        $record->setProperty('claim', 'A');
        $id = $connection->saveRecord($record);

        $record = $connection->getRecord($id);

        $this->assertEquals('A', $record->getProperty('claim'));

        $record = new Record($connection->getCurrentContentTypeDefinition(), 'test');

        $record->setId($id);
        $id = $connection->saveRecord($record);

        $record = $connection->getRecord($id);

        $this->assertEquals('A', $record->getProperty('claim'));
    }

    public function testRevisionCounting()
    {
        KVMLogger::instance()->debug(__METHOD__);

        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $record = new Record($connection->getCurrentContentTypeDefinition(), 'test');

        $record->setProperty('claim', 'A');
        $id = $connection->saveRecord($record);

        $record = $connection->getRecord($id);
        $this->assertEquals(1, $record->getRevision());

        $connection->deleteRecord($id);

        $record = new Record($connection->getCurrentContentTypeDefinition(), 'test');

        $record->setProperty('claim', 'A');
        $record->setId($id);
        $connection->saveRecord($record);

        $record = $connection->getRecord($id);
        $this->assertEquals(3, $record->getRevision());
    }

    public function testPartialUpdateRecord()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $record = new Record($connection->getCurrentContentTypeDefinition(), 'test');

        $record->setProperty('twitter', 'https://www.twitter.com');
        $id = $connection->saveRecord($record);
        $this->assertEquals('https://www.twitter.com', $record->getProperty('twitter'));

        $record = new Record($connection->getCurrentContentTypeDefinition(), 'test');
        $record->setId($id);
        $properties = $record->getProperties();
        $this->assertCount(1, $properties);

        $record->setProperty('facebook', 'https://www.facebook.com');
        $this->assertEquals('https://www.facebook.com', $record->getProperty('facebook'));

        $properties = $record->getProperties();
        $this->assertCount(2, $properties);

        $connection->saveRecord($record);

        $record = $connection->getRecord($id);
        $properties = $record->getProperties();
        $this->assertCount(47, $properties);

        $this->assertEquals('https://www.facebook.com', $record->getProperty('facebook'));
        $record->setProperty('twitter', 'https://www.twitter.com');
    }
}
