<?php

namespace AnyContent\Connection;

use AnyContent\Client\Record;
use AnyContent\Connection\Configuration\ContentArchiveConfiguration;
use KVMLogger\KVMLoggerFactory;
use KVMLogger\KVMLogger;
use Symfony\Component\Filesystem\Filesystem;

class ContentArchiveReadWriteConnectionTest extends \PHPUnit_Framework_TestCase
{

    /** @var  ContentArchiveReadWriteConnection */
    public $connection;


    public static function setUpBeforeClass()
    {
        $target = __DIR__ . '/../../../tmp/ExampleContentArchive';
        $source = __DIR__ . '/../../resources/ContentArchiveExample1';

        $fs = new Filesystem();

        if (file_exists($target))
        {
            $fs->remove($target);
        }

        $fs->mirror($source, $target);

    }


    public function setUp()
    {
        $target = __DIR__ . '/../../../tmp/ExampleContentArchive';

        $configuration = new ContentArchiveConfiguration();

        $configuration->setContentArchiveFolder($target);

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

        $this->assertEquals(5, $result);
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


    public function testPartialUpdateRecord()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $record = new Record($connection->getCurrentContentTypeDefinition(), 'test');

        $record->setProperty('twitter','https://www.twitter.com');
        $id = $connection->saveRecord($record);
        $this->assertEquals('https://www.twitter.com',$record->getProperty('twitter'));

        $record = new Record($connection->getCurrentContentTypeDefinition(), 'test');
        $record->setId($id);
        $properties = $record->getProperties();
        $this->assertCount(1,$properties);

        $record->setProperty('facebook','https://www.facebook.com');
        $this->assertEquals('https://www.facebook.com',$record->getProperty('facebook'));

        $properties = $record->getProperties();
        $this->assertCount(2,$properties);

        $connection->saveRecord($record);

        $record = $connection->getRecord($id);
        $properties = $record->getProperties();
        $this->assertCount(3,$properties);

        $this->assertEquals('https://www.facebook.com',$record->getProperty('facebook'));
        $record->setProperty('twitter','https://www.twitter.com');
    }

}