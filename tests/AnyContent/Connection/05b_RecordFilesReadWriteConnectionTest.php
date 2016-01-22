<?php

namespace AnyContent\Connection;

use AnyContent\Client\Record;
use AnyContent\Connection\Configuration\RecordFilesConfiguration;
use KVMLogger\KVMLoggerFactory;
use KVMLogger\KVMLogger;
use Symfony\Component\Filesystem\Filesystem;

class RecordFilesReadWriteConnectionTest extends \PHPUnit_Framework_TestCase
{

    /** @var  RecordFilesReadWriteConnection */
    public $connection;

    public static function setUpBeforeClass()
    {
        $source = __DIR__ . '/../..//resources/RecordFilesExample';
        $target = __DIR__ . '/../../../tmp/RecordFilesReadWriteConnection';

        $fs = new Filesystem();

        if (file_exists($target))
        {
            $fs->remove($target);
        }

        $fs->mirror($source, $target);

    }


    public function setUp()
    {

        $target = __DIR__ . '/../../../tmp/RecordFilesReadWriteConnection';


        $configuration = new RecordFilesConfiguration();

        $configuration->addContentType('profiles', $target . '/profiles.cmdl', $target . '/records/profiles');
        $configuration->addContentType('test', $target . '/test.cmdl', $target . '/records/test');

        $connection = $configuration->createReadWriteConnection();

        $this->connection = $connection;


        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');

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


    public function testSaveRecordNewConnection()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $record = $connection->getRecord(5);

        $this->assertEquals('dmc', $record->getProperty('name'));

    }


    public function testAddRecord()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $record = new Record($connection->getCurrentContentTypeDefinition(), 'test');

        $id = $connection->saveRecord($record);

        $this->assertEquals(17, $record->getID());
        $this->assertEquals(17, $id);

    }


    public function testSaveRecordsSameConnection()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $this->assertEquals(4, $connection->countRecords());

        $records = [ ];

        for ($i = 1; $i <= 5; $i++)
        {
            $record    = new Record($connection->getCurrentContentTypeDefinition(), 'Test ' . $i);
            $records[] = $record;
        }

        $connection->saveRecords($records);

        $this->assertEquals(9, $connection->countRecords());

    }


    public function testSaveRecordsNewConnection()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $this->assertEquals(9, $connection->countRecords());
    }


    public function testDeleteRecord()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $result = $connection->deleteRecord(5);

        $this->assertEquals(5,$result);
        $this->assertEquals(8, $connection->countRecords());

        $result = $connection->deleteRecord(999);

        $this->assertEquals(false, $result);
        $this->assertEquals(8, $connection->countRecords());

    }


    public function testDeleteRecordNewConnection()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $this->assertEquals(8, $connection->countRecords());
    }


    public function testDeleteRecords()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $result = $connection->deleteRecords([ 6, 999 ]);

        $this->assertCount(1, $result);
        $this->assertEquals(7, $connection->countRecords());

    }


    public function testDeleteRecordsNewConnection()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $this->assertEquals(7, $connection->countRecords());
    }


    public function testDeleteAllRecords()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $result = $connection->deleteAllRecords();

        $this->assertCount(7, $result);
        $this->assertEquals(0, $connection->countRecords());

    }


    public function testDeleteAllRecordsNewConnection()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $this->assertEquals(0, $connection->countRecords());
    }
}