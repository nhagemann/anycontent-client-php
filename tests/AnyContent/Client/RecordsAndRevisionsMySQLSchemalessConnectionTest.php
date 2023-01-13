<?php

namespace AnyContent\Client;

use AnyContent\Connection\Configuration\MySQLSchemalessConfiguration;
use AnyContent\Connection\MySQLSchemalessReadWriteConnection;
use KVMLogger\KVMLoggerFactory;
use PHPUnit\Framework\TestCase;

class RepositoryRecordsAndRevisionsMySQLSchemalessConnectionTest extends TestCase
{
    /** @var  MySQLSchemalessReadWriteConnection */
    public $connection;

    /** @var  Repository */
    public $repository;

    /**
     * @throws \AnyContent\AnyContentClientException
     */
    public static function setUpBeforeClass(): void
    {
        // drop & create database
        $pdo = new \PDO('mysql:host=anycontent-client-phpunit-mysql;port=3306;charset=utf8', 'root', 'root');

        $pdo->exec('DROP DATABASE IF EXISTS phpunit');
        $pdo->exec('CREATE DATABASE phpunit');

        $configuration = new MySQLSchemalessConfiguration();

        $configuration->initDatabase('anycontent-client-phpunit-mysql', 'phpunit', 'root', 'root');
        $configuration->setCMDLFolder(__DIR__ . '/../../resources/ContentArchiveExample1/cmdl');
        $configuration->setRepositoryName('phpunit');
        $configuration->addContentTypes();

        $connection = $configuration->createReadWriteConnection();

        $repository = new Repository('phpunit', $connection);
        $repository->selectContentType('profiles');

        $record = $repository->createRecord('Agency 1', 1);
        $repository->saveRecord($record);

        $record = $repository->createRecord('Agency 2', 2);
        $repository->saveRecord($record);

        $record = $repository->createRecord('Agency 5', 5);
        $repository->saveRecord($record);

        $repository->selectWorkspace('live');

        $record = $repository->createRecord('Agency 1', 1);
        $repository->saveRecord($record);

        //$record = $repository->createRecord('Agency 2', 2);

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
    }

    /**
     * @throws \AnyContent\AnyContentClientException
     */
    public function setUp(): void
    {
        $configuration = new MySQLSchemalessConfiguration();

        $configuration->initDatabase('anycontent-client-phpunit-mysql', 'phpunit', 'root', 'root');

        $configuration->setCMDLFolder(__DIR__ . '/../../resources/ContentArchiveExample1/cmdl');
        $configuration->setRepositoryName('phpunit');
        $configuration->addContentTypes();
        $configuration->addConfigTypes();

        $connection = $configuration->createReadWriteConnection();

        $this->connection = $connection;
        $repository       = new Repository('phpunit', $connection);

        $this->repository = $repository;

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
    }

    public function testSupportsRevisions()
    {
        $this->assertTrue($this->repository->supportsRevisions());
    }

    public function testCreatingRecordRevisions()
    {
        $repository = $this->repository;

        $repository->selectContentType('profiles');

        for ($i = 1; $i <= 4; $i++) {
            $record = $repository->getRecord(1);

            $this->assertEquals($i, $record->getRevision());

            $repository->saveRecord($record);

            $this->assertEquals($i + 1, $record->getRevision());

            $revisions = $repository->getRevisionsOfRecord(1);

            $this->assertCount($i + 1, $revisions);
        }

        $i = 5;
        /** @var Record $revision */
        foreach ($revisions as $revision) {
            $this->assertEquals($i--, $revision->getRevision());
        }
    }

    public function testCreatingConfigRevisions()
    {
        $repository = $this->repository;

        for ($i = 0; $i <= 4; $i++) {
            $config = $repository->getConfig('config1');

            $this->assertEquals($i, $config->getRevision());

            $repository->saveConfig($config);

            $this->assertEquals($i + 1, $config->getRevision());

            $revisions = $repository->getRevisionsOfConfig('config1');

            $this->assertCount($i + 1, $revisions);
        }

        $i = 5;
        /** @var Config $revision */
        foreach ($revisions as $revision) {
            $this->assertEquals($i--, $revision->getRevision());
        }
    }

    public function testDeleteRecords()
    {
        $repository = $this->repository;

        $repository->selectContentType('profiles');

        /** @var $record Record * */
        $records = $repository->getRecords();

        $this->assertCount(3, $records);

        $t1 = $repository->getLastModifiedDate('profiles');

        $this->assertFalse($repository->deleteRecord(99));
        $this->assertTrue((bool)$repository->deleteRecord(5));

        $t2 = $repository->getLastModifiedDate('profiles');

        $this->assertNotEquals($t1, $t2);
    }
}
