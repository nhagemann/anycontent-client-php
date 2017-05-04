<?php

namespace AnyContent\Connection;

use AnyContent\Client\Record;
use AnyContent\Client\Repository;
use AnyContent\Connection\Configuration\MySQLSchemalessConfiguration;
use KVMLogger\KVMLoggerFactory;
use KVMLogger\KVMLogger;

class MySQLSchemalessReadWriteConnectionTest extends \PHPUnit_Framework_TestCase
{

    /** @var  MySQLSchemalessReadWriteConnection */
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

            $connection = $configuration->createReadWriteConnection();

            $this->connection = $connection;
            $repository       = new Repository('phpunit',$connection);

            KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
        }
    }


    public function testSaveRecordSameConnection()
    {
        $connection = $this->connection;

        if (!$connection)
        {
            $this->markTestSkipped('MySQL credentials missing.');
        }

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

        if (!$connection)
        {
            $this->markTestSkipped('MySQL credentials missing.');
        }

        $connection->selectContentType('profiles');

        $record = $connection->getRecord(5);

        $this->assertEquals('Agency 51', $record->getProperty('name'));

    }


    public function testAddRecord()
    {
        $connection = $this->connection;

        if (!$connection)
        {
            $this->markTestSkipped('MySQL credentials missing.');
        }

        $connection->selectContentType('profiles');

        $record = new Record($connection->getCurrentContentTypeDefinition(), 'test');

        $id = $connection->saveRecord($record);

        $this->assertEquals(6, $record->getID());
        $this->assertEquals(6, $id);

    }


    public function testSaveRecordsSameConnection()
    {
        $connection = $this->connection;

        if (!$connection)
        {
            $this->markTestSkipped('MySQL credentials missing.');
        }

        $connection->selectContentType('profiles');

        $this->assertEquals(2, $connection->countRecords());

        $records = [ ];

        for ($i = 1; $i <= 5; $i++)
        {
            $record    = new Record($connection->getCurrentContentTypeDefinition(), 'Test ' . $i);
            $records[] = $record;
        }

        $connection->saveRecords($records);

        $this->assertEquals(7, $connection->countRecords());

    }


    public function testSaveRecordsNewConnection()
    {
        $connection = $this->connection;

        if (!$connection)
        {
            $this->markTestSkipped('MySQL credentials missing.');
        }

        $connection->selectContentType('profiles');

        $this->assertEquals(7, $connection->countRecords());
    }


    public function testDeleteRecord()
    {
        $connection = $this->connection;

        if (!$connection)
        {
            $this->markTestSkipped('MySQL credentials missing.');
        }

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

        if (!$connection)
        {
            $this->markTestSkipped('MySQL credentials missing.');
        }
        $connection->selectContentType('profiles');

        $this->assertEquals(6, $connection->countRecords());
    }


    public function testDeleteRecords()
    {
        $connection = $this->connection;

        if (!$connection)
        {
            $this->markTestSkipped('MySQL credentials missing.');
        }

        $connection->selectContentType('profiles');

        $result = $connection->deleteRecords([ 6, 999 ]);

        $this->assertCount(1, $result);
        $this->assertEquals(5, $connection->countRecords());

    }


    public function testDeleteRecordsNewConnection()
    {
        $connection = $this->connection;

        if (!$connection)
        {
            $this->markTestSkipped('MySQL credentials missing.');
        }

        $connection->selectContentType('profiles');

        $this->assertEquals(5, $connection->countRecords());
    }


    public function testDeleteAllRecords()
    {
        $connection = $this->connection;

        if (!$connection)
        {
            $this->markTestSkipped('MySQL credentials missing.');
        }

        $connection->selectContentType('profiles');

        $result = $connection->deleteAllRecords();

        $this->assertCount(5, $result);
        $this->assertEquals(0, $connection->countRecords());

    }


    public function testDeleteAllRecordsNewConnection()
    {
        $connection = $this->connection;

        if (!$connection)
        {
            $this->markTestSkipped('MySQL credentials missing.');
        }

        $connection->selectContentType('profiles');

        $this->assertEquals(0, $connection->countRecords());
    }

    public function testProtectedProperties()
    {
        KVMLogger::instance()->debug(__METHOD__);

        $connection = $this->connection;

        if (!$connection)
        {
            $this->markTestSkipped('MySQL credentials missing.');
        }

        $connection->selectContentType('profiles');

        $record = new Record($connection->getCurrentContentTypeDefinition(), 'test');

        $record->setProperty('ranking',1);

        $this->assertEquals(1,$record->getProperty('ranking'));

        $id = $connection->saveRecord($record);

        $record = $connection->getRecord($id);

        $this->assertEquals('',$record->getProperty('ranking'));
    }

}