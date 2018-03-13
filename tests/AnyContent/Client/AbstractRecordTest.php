<?php

namespace AnyContent\Client;

use AnyContent\Connection\Configuration\ContentArchiveConfiguration;
use AnyContent\Connection\ContentArchiveReadWriteConnection;
use KVMLogger\KVMLoggerFactory;
use Symfony\Component\Filesystem\Filesystem;

class AbstractRecordTest extends \PHPUnit_Framework_TestCase
{

    /** @var  ContentArchiveReadWriteConnection */
    public $connection;

    /** @var  Repository */
    public $repository;

    public static function setUpBeforeClass()
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

    public function setUp()
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

            $this->assertEquals('DEFAULT', $record->getProperty('article', 'DEFAULT'));

            $record->setProperty('article', '');
            $id = $this->repository->saveRecord($record);
            $this->assertEquals($i, $id);
        }

        $record = $this->repository->getRecord(1);
        $this->assertEquals('DEFAULT', $record->getProperty('article', 'DEFAULT'));

        $record->setProperty('article', 0);
        $id = $this->repository->saveRecord($record);

        $record = $this->repository->getRecord(1);
        $this->assertEquals('0', $record->getProperty('article', 'DEFAULT'));

        $record->setProperty('article', null);
        $id = $this->repository->saveRecord($record);

        $record = $this->repository->getRecord(1);
        $this->assertEquals('DEFAULT', $record->getProperty('article', 'DEFAULT'));
    }

    public function testHash()
    {
        $this->repository->selectContentType('example01');

        $record = $this->repository->createRecord('New Record');

        $hash = '';
        for ($i = 1; $i <= 5; $i++) {
            $record->setProperty('article', 'Text ' . $i);

            $this->assertNotEquals($hash, $record->getHash());
            $hash = $record->getHash();
        }

        $record->setProperty('source', 0);
        $record->setProperty('article', 1);
        $hash1 = $record->getHash();
        $record->setProperty('source', 1);
        $record->setProperty('article', 0);
        $hash2 = $record->getHash();

        $this->assertFalse($hash1 === $hash2);
    }
}