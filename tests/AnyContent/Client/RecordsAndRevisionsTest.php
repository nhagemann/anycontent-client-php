<?php

declare(strict_types=1);

namespace Tests\AnyContent\Client;

use AnyContent\Client\Repository;
use AnyContent\Connection\Configuration\ContentArchiveConfiguration;
use AnyContent\Connection\ContentArchiveReadWriteConnection;
use KVMLogger\KVMLoggerFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class RepositoryRecordsAndRevisionsTest extends TestCase
{
    /** @var  ContentArchiveReadWriteConnection */
    public $connection;

    /** @var  Repository */
    public $repository;

    public static function setUpBeforeClass(): void
    {
        $target = __DIR__ . '/../../../tmp/ExampleContentArchive';
        $source = __DIR__ . '/../../resources/ContentArchiveExample2';

        $fs = new Filesystem();

        if (file_exists($target)) {
            $fs->remove($target);
        }

        $fs->mirror($source, $target);

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
    }

    public function setUp(): void
    {
        $target = __DIR__ . '/../../../tmp/ExampleContentArchive';

        $configuration = new ContentArchiveConfiguration();

        $configuration->setContentArchiveFolder($target);

        $connection = $configuration->createReadWriteConnection();

        $this->connection = $connection;

        $this->repository = new Repository('phpunit', $this->connection);
    }

    public function testSaveRecords()
    {
        $this->repository->selectContentType('example01');

        for ($i = 1; $i <= 5; $i++) {
            $record = $this->repository->createRecord('New Record ' . $i);
            $record->setProperty('article', 'Test ' . $i);
            $id = $this->repository->saveRecord($record);
            $this->assertEquals($i, $id);
        }

        for ($i = 2; $i <= 5; $i++) {
            $record = $this->repository->createRecord('New Record 1 - Revision ' . $i);
            $record->setId(1);
            $id = $this->repository->saveRecord($record);
            $this->assertEquals(1, $id);
            $this->assertEquals($i, $record->getRevision());
        }

        $record = $this->repository->getRecord(1);
        $this->assertEquals(5, $record->getRevision());

        $records = $this->repository->getRecords();
        $this->assertCount(5, $records);
        $this->assertEquals(5, $this->repository->countRecords());

        $record = $this->repository->getRecord(99);
        $this->assertFalse($record);
    }

    public function testNewConnection()
    {
        $this->repository->selectContentType('example01');

        $record = $this->repository->getRecord(1);
        $this->assertEquals(5, $record->getRevision());

        $this->assertEquals('example01', $record->getContentTypeName());
        $this->assertEquals(1, $record->getID());
        $this->assertEquals('New Record 1 - Revision 5', $record->getName());
        $this->assertEquals('Test 1', $record->getProperty('article'));

        $records = $this->repository->getRecords();
        $this->assertCount(5, $records);
        $this->assertEquals(5, $this->repository->countRecords());
    }

    public function testDeleteRecords()
    {
//        $cmdl = $this->client->getCMDL('example01');
//
//        $contentTypeDefinition = Parser::parseCMDLString($cmdl);
//        $contentTypeDefinition->setName('example01');
//
//        /** @var $record Record * */
//        $records = $this->client->getRecords($contentTypeDefinition);
//
//        $this->assertCount(5,$records);
//
//        $t1 = $this->client->getLastContentTypeChangeTimestamp($contentTypeDefinition->getName());
//
//        $this->assertFalse($this->client->deleteRecord($contentTypeDefinition,99));
//        $this->assertTrue($this->client->deleteRecord($contentTypeDefinition,5));
//
//        $t2 = $this->client->getLastContentTypeChangeTimestamp($contentTypeDefinition->getName());
//
//        $this->assertNotEquals($t1,$t2);
//
//        /** @var $record Record * */
//        $records = $this->client->getRecords($contentTypeDefinition);
//
//        $this->assertCount(4,$records);
//
//        $record = new Record($contentTypeDefinition, 'New Record 5');
//        $record->setProperty('article', 'Test 5 ');
//        $record->setId(5);
//        $id = $this->client->saveRecord($record);
//        $this->assertEquals(5, $id);
//
//        /** @var $record Record * */
//        $records = $this->client->getRecords($contentTypeDefinition);
//
//        $this->assertCount(5,$records);
    }
}
