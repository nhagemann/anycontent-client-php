<?php

namespace AnyContent\Connection;

use AnyContent\Client\Record;
use AnyContent\Connection\Configuration\RecordsFileConfiguration;
use KVMLogger\KVMLoggerFactory;
use KVMLogger\KVMLogger;
use Symfony\Component\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

class RecordsFileReadWriteConnectionTest extends TestCase
{
    /** @var  RecordsFileReadWriteConnection */
    public $connection;


    public static function setUpBeforeClass(): void
    {
        $target = __DIR__ . '/../../../tmp/RecordsFileExample';
        $source = __DIR__ . '/../../resources/RecordsFileExample';

        $fs = new Filesystem();

        if (file_exists($target)) {
            $fs->remove($target);
        }

        $fs->mirror($source, $target);

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
    }


    public function setUp(): void
    {
        $configuration = new RecordsFileConfiguration();

        $configuration->addContentType('profiles', __DIR__ . '/../../../tmp/RecordsFileExample/profiles.cmdl', __DIR__ . '/../../../tmp/RecordsFileExample/profiles.json');
        $configuration->addContentType('test', __DIR__ . '/../../../tmp/RecordsFileExample/test.cmdl', __DIR__ . '/../../../tmp/RecordsFileExample/test.json');

        $connection = $configuration->createReadWriteConnection();

        $this->connection = $connection;
    }


    public function testSaveRecordSameConnection()
    {
        KVMLogger::instance()->debug(__METHOD__);

        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $record = $connection->getRecord(1);

        $this->assertEquals('UDG United Digital Group', $record->getProperty('name'));

        $record->setProperty('name', 'UDG');

        $connection->saveRecord($record);

        $record = $connection->getRecord(1);

        $this->assertEquals('UDG', $record->getProperty('name'));
    }


    public function testSaveRecordNewConnection()
    {
        KVMLogger::instance()->debug(__METHOD__);

        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $record = $connection->getRecord(1);

        $this->assertEquals('UDG', $record->getProperty('name'));
    }


    public function testAddRecord()
    {
        KVMLogger::instance()->debug(__METHOD__);

        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $record = new Record($connection->getCurrentContentTypeDefinition(), 'test');

        $id = $connection->saveRecord($record);

        $this->assertEquals(629, $record->getID());
        $this->assertEquals(629, $id);
    }


    public function testSaveRecordsSameConnection()
    {
        KVMLogger::instance()->debug(__METHOD__);

        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $this->assertEquals(609, $connection->countRecords());

        $records = [ ];

        for ($i = 1; $i <= 5; $i++) {
            $record    = new Record($connection->getCurrentContentTypeDefinition(), 'Test ' . $i);
            $records[] = $record;
        }

        $connection->saveRecords($records);

        $this->assertEquals(614, $connection->countRecords());
    }


    public function testSaveRecordsNewConnection()
    {
        KVMLogger::instance()->debug(__METHOD__);

        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $this->assertEquals(614, $connection->countRecords());
    }


    public function testDeleteRecord()
    {
        KVMLogger::instance()->debug(__METHOD__);

        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $result = $connection->deleteRecord(1);

        $this->assertEquals(1, $result);
        $this->assertEquals(613, $connection->countRecords());

        $result = $connection->deleteRecord(999);

        $this->assertEquals(false, $result);
        $this->assertEquals(613, $connection->countRecords());
    }


    public function testDeleteRecordNewConnection()
    {
        KVMLogger::instance()->debug(__METHOD__);

        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $this->assertEquals(613, $connection->countRecords());
    }


    public function testDeleteRecords()
    {
        KVMLogger::instance()->debug(__METHOD__);

        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $result = $connection->deleteRecords([2, 5, 999]);

        $this->assertCount(2, $result);
        $this->assertEquals(611, $connection->countRecords());
    }


    public function testDeleteRecordsNewConnection()
    {
        KVMLogger::instance()->debug(__METHOD__);

        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $this->assertEquals(611, $connection->countRecords());
    }


    public function testDeleteAllRecords()
    {
        KVMLogger::instance()->debug(__METHOD__);

        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $result = $connection->deleteAllRecords();

        $this->assertCount(611, $result);
        $this->assertEquals(0, $connection->countRecords());
    }


    public function testDeleteAllRecordsNewConnection()
    {
        KVMLogger::instance()->debug(__METHOD__);

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
        $this->assertCount(3, $properties);

        $this->assertEquals('https://www.facebook.com', $record->getProperty('facebook'));
        $record->setProperty('twitter', 'https://www.twitter.com');
    }
}
