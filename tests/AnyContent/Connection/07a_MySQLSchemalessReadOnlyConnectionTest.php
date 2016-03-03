<?php

namespace AnyContent\Connection;

use AnyContent\Client\Repository;
use AnyContent\Connection\Configuration\ContentArchiveConfiguration;
use AnyContent\Connection\Configuration\MySQLSchemalessConfiguration;
use KVMLogger\KVMLoggerFactory;
use KVMLogger\KVMLogger;

class MySQLSchemalessConnectionTest extends \PHPUnit_Framework_TestCase
{

    /** @var  MySQLSchemalessReadOnlyConnection */
    public $connection;


    public static function setUpBeforeClass()
    {
        if (defined('PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_HOST'))
        {
            $configuration = new MySQLSchemalessConfiguration();

            $configuration->initDatabase(PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_HOST, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_DBNAME, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_USERNAME, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_PASSWORD);
            $configuration->setCMDLFolder(__DIR__ . '/../../resources/ContentArchiveExample1/cmdl');
            $configuration->setRepositoryName('phpunit');
            $configuration->addContentTypes();

            $database = $configuration->getDatabase();

            $database->execute('DROP TABLE IF EXISTS _cmdl_');
            $database->execute('DROP TABLE IF EXISTS _counter_');
            $database->execute('DROP TABLE IF EXISTS phpunit$profiles');

            $connection = $configuration->createReadWriteConnection();

            $repository = new Repository('phpunit',$connection);
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
    }


    public function setUp()
    {
        if (defined('PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_HOST'))
        {
            $configuration = new MySQLSchemalessConfiguration();

            $configuration->initDatabase(PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_HOST, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_DBNAME, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_USERNAME, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_PASSWORD);
            $configuration->setCMDLFolder(__DIR__ . '/../../resources/ContentArchiveExample1/cmdl');
            $configuration->setRepositoryName('phpunit');
            $configuration->addContentTypes();

            $connection = $configuration->createReadOnlyConnection();

            $this->connection = $connection;
            $repository       = new Repository('phpunit',$connection);

            KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
        }
    }


    public function testContentTypeNotSelected()
    {
        $connection = $this->connection;

        if (!$connection)
        {
            $this->markTestSkipped('MySQL credentials missing.');
        }

        $this->setExpectedException('AnyContent\AnyContentClientException');
        $this->assertEquals(12, $connection->countRecords());
    }


    public function testContentTypeNames()
    {
        $connection = $this->connection;

        if (!$connection)
        {
            $this->markTestSkipped('MySQL credentials missing.');
        }

        $contentTypeNames = $connection->getContentTypeNames();

        $this->assertContains('profiles', $contentTypeNames);
    }


    public function testContentTypeDefinitions()
    {
        $connection = $this->connection;

        if (!$connection)
        {
            $this->markTestSkipped('MySQL credentials missing.');
        }

        $contentTypes = $connection->getContentTypeDefinitions();

        $this->assertArrayHasKey('profiles', $contentTypes);

        $contentType = $contentTypes['profiles'];
        $this->assertInstanceOf('CMDL\ContentTypeDefinition', $contentType);
    }


    public function testCountRecords()
    {
        $connection = $this->connection;

        if (!$connection)
        {
            $this->markTestSkipped('MySQL credentials missing.');
        }

        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $this->assertEquals(3, $connection->countRecords());

    }


    public function testGetRecord()
    {
        $connection = $this->connection;

        if (!$connection)
        {
            $this->markTestSkipped('MySQL credentials missing.');
        }

        $connection->selectContentType('profiles');

        $record = $connection->getRecord(5);

        $this->assertInstanceOf('AnyContent\Client\Record', $record);

        $this->assertEquals('Agency 5', $record->getProperty('name'));

    }


    public function testGetRecords()
    {
        $connection = $this->connection;

        if (!$connection)
        {
            $this->markTestSkipped('MySQL credentials missing.');
        }

        $connection->selectContentType('profiles');

        $records = $connection->getAllRecords();

        $this->assertCount(3, $records);

        foreach ($records as $record)
        {
            $id          = $record->getID();
            $fetchRecord = $connection->getRecord($id);
            $this->assertEquals($id, $fetchRecord->getID());
        }
    }


    public function testWorkspaces()
    {
        $connection = $this->connection;

        if (!$connection)
        {
            $this->markTestSkipped('MySQL credentials missing.');
        }

        $connection->selectContentType('profiles');

        $connection->selectWorkspace('live');

        $records = $connection->getAllRecords();

        $this->assertCount(2, $records);
    }

    public function testLastModified()
    {
        $this->assertNotEquals(0,$this->connection->getLastModifiedDate());
    }
}