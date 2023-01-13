<?php

namespace AnyContent\Connection;

use AnyContent\AnyContentClientException;
use AnyContent\Client\Config;
use AnyContent\Client\Record;
use AnyContent\Client\Repository;
use AnyContent\Connection\Configuration\MySQLSchemalessConfiguration;
use KVMLogger\KVMLoggerFactory;
use PDO;
use PHPUnit\Framework\TestCase;

class MySQLSchemalessReadOnlyRevisionConnectionTest extends TestCase
{
    /** @var  MySQLSchemalessReadWriteConnection */
    public $connection;

    /**
     * @throws \AnyContent\AnyContentClientException
     */
    public static function setUpBeforeClass(): void
    {
        // drop & create database
        $pdo = new PDO('mysql:host=anycontent-client-phpunit-mysql;port=3306;charset=utf8', 'root', 'root');

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

        $record = $repository->createRecord('Agency 2', 2);
        $repository->saveRecord($record);

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
    }

    /**
     * @throws AnyContentClientException
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
        $this->assertEquals($repository, $this->connection->getRepository());

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
    }

    public function testCheckSetupRevision()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $revisions = $connection->getRevisionsOfRecord(1);

        $this->assertEquals(1, count($revisions));

        $record = array_shift($revisions);

        $this->assertInstanceOf('AnyContent\Client\Record', $record);
    }

    public function testCreateAndFetchRecordRevisions()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        for ($i = 1; $i <= 4; $i++) {
            $record = $connection->getRecord(1);

            $this->assertEquals($i, $record->getRevision());

            $connection->saveRecord($record);

            $this->assertEquals($i + 1, $record->getRevision());

            $revisions = $connection->getRevisionsOfRecord(1);

            $this->assertCount($i + 1, $revisions);
        }

        $i = 5;
        /** @var Record $revision */
        foreach ($revisions as $revision) {
            $this->assertEquals($i--, $revision->getRevision());
        }
    }

    public function testTimeShiftIntoRecordRevision()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $revisions = $connection->getRevisionsOfRecord(1);

        foreach ($revisions as $timeshift => $revision) {
            /** @var Record $revision */
            $connection->setTimeShift($timeshift);
            $record = $connection->getRecord(1);
            $this->assertEquals($revision->getRevision(), $record->getRevision());
        }
    }

    public function testCheckSetupConfigRevision()
    {
        $connection = $this->connection;

        $config = $connection->getConfig('config1');

        $this->assertInstanceOf('AnyContent\Client\Config', $config);

        $this->assertEquals(0, $config->getRevision());
    }

    public function testCreateAndFetchConfigRevisions()
    {
        $connection = $this->connection;

        for ($i = 0; $i <= 4; $i++) {
            $config = $connection->getConfig('config1');

            $this->assertEquals($i, $config->getRevision());

            $connection->saveConfig($config);

            $this->assertEquals($i + 1, $config->getRevision());

            $revisions = $connection->getRevisionsOfConfig('config1');

            $this->assertCount($i + 1, $revisions);
        }

        $i = 5;
        /** @var Config $revision */
        foreach ($revisions as $revision) {
            $this->assertEquals($i--, $revision->getRevision());
        }
    }

    public function testTimeShiftIntoConfigRevision()
    {
        $connection = $this->connection;

        $revisions = $connection->getRevisionsOfConfig('config1');

        foreach ($revisions as $timeshift => $revision) {
            /** @var Record $revision */
            $connection->setTimeShift($timeshift);
            $config = $connection->getConfig('config1');
            $this->assertEquals($revision->getRevision(), $config->getRevision());
        }
    }
}
