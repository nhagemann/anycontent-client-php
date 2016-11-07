<?php

namespace AnyContent\Connection;

use AnyContent\Client\Record;

use AnyContent\Connection\Configuration\RestLikeConfiguration;
use KVMLogger\KVMLoggerFactory;
use KVMLogger\KVMLogger;

class RestLikeBasicConnectionReadWriteConnectionTest extends \PHPUnit_Framework_TestCase
{

    /** @var  RestLikeBasicReadWriteConnection */
    public $connection;


    public function setUp()
    {
        if (defined('PHPUNIT_CREDENTIALS_RESTLIKE_URL2'))
        {
            $configuration = new RestLikeConfiguration();

            $configuration->setUri(PHPUNIT_CREDENTIALS_RESTLIKE_URL2);
            $connection = $configuration->createReadWriteConnection();

            $configuration->addContentTypes();
            $configuration->addConfigTypes();

            $this->connection = $connection;

            KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
        }

    }


    public function testSaveRecordSameConnection()
    {
        $connection = $this->connection;

        if (!$connection)
        {
            $this->markTestSkipped('RestLike Basic Connection credentials missing.');
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
            $this->markTestSkipped('RestLike Basic Connection credentials missing.');
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
            $this->markTestSkipped('RestLike Basic Connection credentials missing.');
        }
        $connection->selectContentType('profiles');

        $record = new Record($connection->getCurrentContentTypeDefinition(), 'test');

        $id = $connection->saveRecord($record);

        $this->assertTrue($id > 5);
        $this->assertEquals($id, $record->getID());

    }


    public function testSaveRecordsSameConnection()
    {
        $connection = $this->connection;
        if (!$connection)
        {
            $this->markTestSkipped('RestLike Basic Connection credentials missing.');
        }
        $connection->selectContentType('profiles');

        $c = $connection->countRecords();

        $records = [ ];

        for ($i = 1; $i <= 5; $i++)
        {
            $record    = new Record($connection->getCurrentContentTypeDefinition(), 'Test ' . $i);
            $records[] = $record;
        }

        $connection->saveRecords($records);

        $this->assertEquals($c + 5, $connection->countRecords());

    }


    public function testDeleteRecord()
    {

        $connection = $this->connection;
        if (!$connection)
        {
            $this->markTestSkipped('RestLike Basic Connection credentials missing.');
        }
        $connection->selectContentType('profiles');

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
        if (!$connection)
        {
            $this->markTestSkipped('RestLike Basic Connection credentials missing.');
        }
        $connection->selectContentType('profiles');

        $result = $connection->deleteRecords([ 6, 999 ]);

        // No expectations, since the test does not yet have the necessary setup
        // $this->assertCount(1, $result);
        // $this->assertEquals(5, $connection->countRecords());

    }


    public function testDeleteAllRecords()
    {
        $connection = $this->connection;
        if (!$connection)
        {
            $this->markTestSkipped('RestLike Basic Connection credentials missing.');
        }
        $connection->selectContentType('profiles');

        $result = $connection->deleteAllRecords();

        //$this->assertCount(5, $result);
        $this->assertEquals(0, $connection->countRecords());

    }


    public function testDeleteAllRecordsNewConnection()
    {
        $connection = $this->connection;
        if (!$connection)
        {
            $this->markTestSkipped('RestLike Basic Connection credentials missing.');
        }
        $connection->selectContentType('profiles');

        $this->assertEquals(0, $connection->countRecords());
    }

    public function testProtectedProperties()
    {
        KVMLogger::instance()->debug(__METHOD__);

        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $record = new Record($connection->getCurrentContentTypeDefinition(), 'test');

        $record->setProperty('ranking',1);

        $this->assertEquals(1,$record->getProperty('ranking'));

        $id = $connection->saveRecord($record);

        $record = $connection->getRecord($id);

        $this->assertEquals('',$record->getProperty('ranking'));
    }
}