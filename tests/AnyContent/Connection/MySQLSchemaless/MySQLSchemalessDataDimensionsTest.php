<?php

declare(strict_types=1);

namespace Tests\AnyContent\Connection\MySQLSchemaless;

use AnyContent\Client\Record;
use AnyContent\Client\Repository;
use AnyContent\Connection\Configuration\MySQLSchemalessConfiguration;
use AnyContent\Connection\MySQLSchemalessReadWriteConnection;
use PHPUnit\Framework\TestCase;

class MySQLSchemalessDataDimensionsTest extends TestCase
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

        $connection = $configuration->createReadWriteConnection();

        $repository = new Repository('phpunit', $connection);
        $repository->selectContentType('profiles');

        $record = $repository->createRecord('dmc digital media center', 5);
        $repository->saveRecord($record);

        $record = $repository->createRecord('Agency 16', 16);
        $repository->saveRecord($record);

        $repository->selectWorkspace('live');

        $record = $repository->createRecord('dmc digital media center', 5);
        $repository->saveRecord($record);

        $repository->selectLanguage('de');

        $record = $repository->createRecord('dmc digital media center', 5);
        $repository->saveRecord($record);
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
    }

    public function testSaveRecordSameConnection()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $record = $connection->getRecord(5);

        $this->assertEquals('dmc digital media center', $record->getProperty('name'));

        $record->setProperty('name', 'dmc');

        $connection->saveRecord($record);

        $record = $connection->getRecord(5);

        $this->assertEquals('dmc', $record->getProperty('name'));
    }

    public function testWorkSpaceLive()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $connection->selectWorkspace('live');

        $record = $connection->getRecord(5);

        $this->assertEquals('live', $record->getWorkspace());

        $record->setProperty('name', 'dmc');

        $connection->saveRecord($record);

        $record = $connection->getRecord(5);

        $this->assertEquals('dmc', $record->getProperty('name'));

        $record = $connection->getRecord(99);

        $this->assertFalse($record);
    }

    public function testSaveRecordNewConnection()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');
        $connection->selectWorkspace('live');

        $record = $connection->getRecord(5);

        $this->assertEquals('live', $record->getWorkspace());
        $this->assertEquals('dmc', $record->getProperty('name'));
    }

    public function testAddRecord()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles')->selectWorkspace('live');

        $record = new Record($connection->getCurrentContentTypeDefinition(), 'test');

        $id = $connection->saveRecord($record);

        $this->assertEquals(17, $record->getID());
        $this->assertEquals(17, $id);
    }

    public function testSaveRecordsSameConnection()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles')->selectWorkspace('live');

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

        $this->assertEquals(2, $connection->countRecords());

        $connection->selectWorkspace('live');

        $this->assertEquals(7, $connection->countRecords());
    }

    public function testDeleteRecord()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles')->selectWorkspace('live');

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

        $connection->selectContentType('profiles')->selectWorkspace('live');

        $this->assertEquals(6, $connection->countRecords());
    }

    public function testDeleteRecords()
    {
        $connection = $this->connection;

         $connection->selectContentType('profiles')->selectWorkspace('live');

        $result = $connection->deleteRecords([17, 999]);

        $this->assertCount(1, $result);
        $this->assertEquals(5, $connection->countRecords());
    }

    public function testDeleteRecordsNewConnection()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles')->selectWorkspace('live');

        $this->assertEquals(5, $connection->countRecords());
    }

    public function testDeleteAllRecords()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles')->selectWorkspace('live');

        $result = $connection->deleteAllRecords();

        $this->assertCount(5, $result);
        $this->assertEquals(0, $connection->countRecords());
    }

    public function testDeleteAllRecordsNewConnection()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles')->selectWorkspace('live');

        $this->assertEquals(0, $connection->countRecords());
    }

    public function testSwitchLanguage()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles')->selectWorkspace('live')->selectLanguage('de');

        $this->assertEquals(1, $connection->countRecords());
    }
}
