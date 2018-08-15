<?php

namespace AnyContent\Connection;

use AnyContent\Client\Record;

use AnyContent\Client\Repository;
use AnyContent\Connection\Configuration\MySQLSchemalessConfiguration;
use AnyContent\Connection\Configuration\RestLikeConfiguration;
use KVMLogger\KVMLoggerFactory;
use KVMLogger\KVMLogger;

class RestLikeBasicConnectionReadWriteConnectionTest extends \PHPUnit_Framework_TestCase
{

    /** @var  RestLikeBasicReadWriteConnection */
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

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
    }


    public function setUp()
    {

        $configuration = new RestLikeConfiguration();

        $configuration->setUri(getenv('PHPUNIT_RESTLIKE_URI'));
        $connection = $configuration->createReadWriteConnection();

        $configuration->addContentTypes();
        $configuration->addConfigTypes();

        $this->connection = $connection;

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');

    }


    public function testSaveRecordSameConnection()
    {
        $connection = $this->connection;

        $connection->selectContentType('content1');
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

        $connection->selectContentType('content1');

        $record = $connection->getRecord(5);

        $this->assertEquals('Agency 51', $record->getProperty('name'));

    }


    public function testAddRecord()
    {
        $connection = $this->connection;

        $connection->selectContentType('content1');

        $record = new Record($connection->getCurrentContentTypeDefinition(), 'test');

        $id = $connection->saveRecord($record);

        $this->assertTrue($id > 5);
        $this->assertEquals($id, $record->getID());

    }


    public function testSaveRecordsSameConnection()
    {
        $connection = $this->connection;

        $connection->selectContentType('content1');

        $c = $connection->countRecords();

        $records = [];

        for ($i = 1; $i <= 5; $i++) {
            $record    = new Record($connection->getCurrentContentTypeDefinition(), 'Test ' . $i);
            $records[] = $record;
        }

        $connection->saveRecords($records);

        $this->assertEquals($c + 5, $connection->countRecords());

    }


    public function testDeleteRecord()
    {

        $connection = $this->connection;

        $connection->selectContentType('content1');

        $c = $connection->countRecords();

        $result = $connection->deleteRecord(5);

        $this->assertEquals(5, $result);
        $this->assertEquals($c - 1, $connection->countRecords());

        $result = $connection->deleteRecord(999);

        $this->assertEquals(false, $result);
        $this->assertEquals($c - 1, $connection->countRecords());

    }


    public function testDeleteRecords()
    {
        $connection = $this->connection;

        $connection->selectContentType('content1');

        $result = $connection->deleteRecords([6, 999]);

        // No expectations, since the test does not yet have the necessary setup
        // $this->assertCount(1, $result);
        // $this->assertEquals(5, $connection->countRecords());

    }


    public function testDeleteAllRecords()
    {
        $connection = $this->connection;

        $connection->selectContentType('content1');

        $result = $connection->deleteAllRecords();

        //$this->assertCount(5, $result);
        $this->assertEquals(0, $connection->countRecords());

    }


    public function testDeleteAllRecordsNewConnection()
    {
        $connection = $this->connection;

        $connection->selectContentType('content1');

        $this->assertEquals(0, $connection->countRecords());
    }


    public function testProtectedProperties()
    {
        echo 'ToDO';

        return;
        KVMLogger::instance()->debug(__METHOD__);

        $connection = $this->connection;

        $connection->selectContentType('content5');

        $record = new Record($connection->getCurrentContentTypeDefinition(), 'test');

        $record->setProperty('a', 1);

        $this->assertEquals(1, $record->getProperty('a'));

        $id = $connection->saveRecord($record);

        $record = $connection->getRecord($id);

        $this->assertEquals('', $record->getProperty('a'));
    }
}