<?php

namespace AnyContent\Client;

use AnyContent\Connection\Configuration\ContentArchiveConfiguration;
use AnyContent\Connection\ContentArchiveReadWriteConnection;
use KVMLogger\KVMLoggerFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class GetFirstRecordTest extends TestCase
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

    public static function tearDownAfterClass(): void
    {
        $target = __DIR__ . '/../../../tmp/ExampleContentArchive';

        $fs = new Filesystem();
        $fs->remove($target);
    }

    public function setUp(): void
    {
        $target = __DIR__ . '/../../../tmp/ExampleContentArchive';

        $configuration = new ContentArchiveConfiguration();

        $configuration->setContentArchiveFolder($target);

        $connection = $configuration->createReadWriteConnection();

        $this->connection = $connection;

        $this->repository = new Repository('phpunit', $this->connection);

        $this->repository->selectContentType('example01');

        $record = $this->repository->createRecord('New Record 1');
        $record->setProperty('source', 'a');
        $this->repository->saveRecord($record);

        $record = $this->repository->createRecord('New Record 2');
        $record->setProperty('source', 'a');
        $this->repository->saveRecord($record);

        $record = $this->repository->createRecord('New Record 3');
        $record->setProperty('source', 'b');
        $this->repository->saveRecord($record);
    }

    public function testRetrieve2Records()
    {
        $this->repository->selectContentType('example01');

        $records = $this->repository->getRecords('source = a');
        $this->assertCount(2, $records);

        $records = $this->repository->getRecords('source = b');
        $this->assertCount(1, $records);
    }

    public function testGetFirstRecord()
    {
        $this->repository->selectContentType('example01');

        $record = $this->repository->getFirstRecord('source = a');
        $this->assertInstanceOf('AnyContent\Client\Record', $record);

        $record = $this->repository->getFirstRecord('source = b');
        $this->assertInstanceOf('AnyContent\Client\Record', $record);

        $record = $this->repository->getFirstRecord('source = c');
        $this->assertFalse($record);
    }
}
