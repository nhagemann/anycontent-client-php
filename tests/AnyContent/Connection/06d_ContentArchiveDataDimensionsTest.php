<?php

namespace AnyContent\Connection;

use AnyContent\Client\Record;
use AnyContent\Connection\Configuration\ContentArchiveConfiguration;
use KVMLogger\KVMLoggerFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class ContentArchiveDataDimensionsTest extends TestCase
{
    /** @var  ContentArchiveReadWriteConnection */
    public $connection;

    public static function setUpBeforeClass(): void
    {
        $target = __DIR__ . '/../../../tmp/TestContentArchive';
        $source = __DIR__ . '/../../resources/ContentArchiveExample1';

        $fs = new Filesystem();

        if (file_exists($target)) {
            $fs->remove($target);
        }

        $fs->mirror($source, $target);
    }

    public static function tearDownAfterClass(): void
    {
        $target = __DIR__ . '/../../../tmp/TestContentArchive';

        $fs = new Filesystem();
        $fs->remove($target);

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
    }

    public function setUp(): void
    {
        $target = __DIR__ . '/../../../tmp/TestContentArchive';

        $configuration = new ContentArchiveConfiguration();

        $configuration->setContentArchiveFolder($target);

        $connection = $configuration->createReadWriteConnection();

        $this->connection = $connection;
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

        $this->assertEquals(3, $connection->countRecords());

        $records = [ ];

        for ($i = 1; $i <= 5; $i++) {
            $record    = new Record($connection->getCurrentContentTypeDefinition(), 'Test ' . $i);
            $records[] = $record;
        }

        $connection->saveRecords($records);

        $this->assertEquals(8, $connection->countRecords());
    }

    public function testSaveRecordsNewConnection()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $this->assertEquals(3, $connection->countRecords());

        $connection->selectWorkspace('live');

        $this->assertEquals(8, $connection->countRecords());
    }

    public function testDeleteRecord()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles')->selectWorkspace('live');

        $result = $connection->deleteRecord(5);

        $this->assertEquals(5, $result);
        $this->assertEquals(7, $connection->countRecords());

        $result = $connection->deleteRecord(999);

        $this->assertEquals(false, $result);
        $this->assertEquals(7, $connection->countRecords());
    }

    public function testDeleteRecordNewConnection()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles')->selectWorkspace('live');

        $this->assertEquals(7, $connection->countRecords());
    }

    public function testDeleteRecords()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles')->selectWorkspace('live');

        $result = $connection->deleteRecords([6, 999]);

        $this->assertCount(1, $result);
        $this->assertEquals(6, $connection->countRecords());
    }

    public function testDeleteRecordsNewConnection()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles')->selectWorkspace('live');

        $this->assertEquals(6, $connection->countRecords());
    }

    public function testDeleteAllRecords()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles')->selectWorkspace('live');

        $result = $connection->deleteAllRecords();

        $this->assertCount(6, $result);
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
