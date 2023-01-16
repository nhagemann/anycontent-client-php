<?php

declare(strict_types=1);

namespace Tests\AnyContent\Client;

use AnyContent\Client\Record;
use AnyContent\Client\Repository;
use AnyContent\Connection\Configuration\ContentArchiveConfiguration;
use AnyContent\Connection\Configuration\MySQLSchemalessConfiguration;
use AnyContent\Connection\ContentArchiveReadWriteConnection;
use KVMLogger\KVMLoggerFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class RecordsAndRevisionsTest extends TestCase
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

    public function testRestartRevisionCountingInContentArchiveConnection()
    {
        $this->repository->selectContentType('example01');
        $this->repository->deleteAllRecords();

        for ($i = 1; $i <= 5; $i++) {
            $record = $this->repository->createRecord('New Record ' . $i);
            $record->setProperty('article', 'Test ' . $i);
            $record->setId(1);
            $id = $this->repository->saveRecord($record);
            $this->assertEquals(1, $id);
            $this->assertEquals($i, $record->getRevision());
        }

        $records = $this->repository->getRecords();

        $this->assertCount(1, $records);

        $record = $this->repository->getRecord(1);
        $this->assertInstanceOf(Record::class, $record);
        $this->assertEquals(5, $record->getRevision());

        $this->assertEquals(1, $this->repository->deleteRecord(1));

        $record = $this->repository->createRecord('New Record 1');
        $record->setId(1);
        $id = $this->repository->saveRecord($record);
        $this->assertEquals(1, $id);
        $this->assertEquals(1, $record->getRevision());
    }

    public function testContinueRevisionCountingInMySQLSchemaLessConnection()
    {
        // drop & create database
        $pdo = new \PDO('mysql:host=anycontent-client-phpunit-mysql;port=3306;charset=utf8', 'root', 'root');

        $pdo->exec('DROP DATABASE IF EXISTS phpunit');
        $pdo->exec('CREATE DATABASE phpunit');

        $configuration = new MySQLSchemalessConfiguration();

        $configuration->initDatabase('anycontent-client-phpunit-mysql', 'phpunit', 'root', 'root');

        $configuration->setCMDLFolder(__DIR__ . '/../../resources/ContentArchiveExample2/cmdl');
        $configuration->setRepositoryName('phpunit');
        $configuration->addContentTypes();
        $configuration->addConfigTypes();

        $connection = $configuration->createReadWriteConnection();

        $repository       = new Repository('phpunit', $connection);
        $this->assertEquals($repository, $connection->getRepository());

        $repository->selectContentType('example01');
        $repository->deleteAllRecords();

        for ($i = 1; $i <= 5; $i++) {
            $record = $repository->createRecord('New Record ' . $i);
            $record->setProperty('article', 'Test ' . $i);
            $record->setId(1);
            $id = $repository->saveRecord($record);
            $this->assertEquals(1, $id);
            $this->assertEquals($i, $record->getRevision());
        }

        $records = $repository->getRecords();

        $this->assertCount(1, $records);

        $record = $repository->getRecord(1);
        $this->assertInstanceOf(Record::class, $record);
        $this->assertEquals(5, $record->getRevision());

        $this->assertEquals(1, $repository->deleteRecord(1));
        $record = $repository->createRecord('New Record 1');
        $record->setId(1);
        $id = $repository->saveRecord($record);
        $this->assertEquals(1, $id);
        $this->assertEquals(7, $record->getRevision());

//        $this->assertEquals(1,$repository->deleteRecord(1));
//        $record = $repository->createRecord('New Record 1');
//        $record->setId(1);
//        $id = $repository->saveRecord($record);
//        $this->assertEquals(1, $id);
//        $this->assertEquals(9,$record->getRevision());
    }
}
