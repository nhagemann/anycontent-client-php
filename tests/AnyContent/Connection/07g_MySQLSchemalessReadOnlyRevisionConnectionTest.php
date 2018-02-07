<?php

namespace AnyContent\Connection;

use AnyContent\Client\Config;
use AnyContent\Client\Record;
use AnyContent\Client\Repository;
use AnyContent\Connection\Configuration\MySQLSchemalessConfiguration;
use KVMLogger\KVMLoggerFactory;

class MySQLSchemalessReadOnlyRevisionConnectionTest extends \PHPUnit_Framework_TestCase
{

    /** @var  MySQLSchemalessReadWriteConnection */
    public $connection;

    public static function setUpBeforeClass()
    {
        if (defined('PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_HOST')) {
            $configuration = new MySQLSchemalessConfiguration();

            $configuration->initDatabase(PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_HOST, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_DBNAME, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_USERNAME, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_PASSWORD);
            $configuration->setCMDLFolder(__DIR__ . '/../../resources/ContentArchiveExample1/cmdl');
            $configuration->setRepositoryName('phpunit');
            $configuration->addContentTypes();
            $configuration->addConfigTypes();

            $database = $configuration->getDatabase();

            $database->execute('DROP TABLE IF EXISTS _cmdl_');
            $database->execute('DROP TABLE IF EXISTS _counter_');
            $database->execute('DROP TABLE IF EXISTS _update_');
            $database->execute('DROP TABLE IF EXISTS phpunit$profiles');
            $database->execute('DROP TABLE IF EXISTS phpunit$$config');

            // init again, since we just removed some vital tables, but had to init the database before, to get the database object
            $configuration->initDatabase(PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_HOST, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_DBNAME, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_USERNAME, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_PASSWORD);

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
    }

    public function setUp()
    {
        if (defined('PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_HOST')) {
            $configuration = new MySQLSchemalessConfiguration();

            $configuration->initDatabase(PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_HOST, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_DBNAME, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_USERNAME, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_PASSWORD);
            $configuration->setCMDLFolder(__DIR__ . '/../../resources/ContentArchiveExample1/cmdl');
            $configuration->setRepositoryName('phpunit');
            $configuration->addContentTypes();
            $configuration->addConfigTypes();

            $connection = $configuration->createReadWriteConnection();

            $this->connection = $connection;
            $repository       = new Repository('phpunit', $connection);

            KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
        }
    }

    public function testCheckSetupRevision()
    {
        $connection = $this->connection;

        if (!$connection) {
            $this->markTestSkipped('MySQL credentials missing.');
        }

        $connection->selectContentType('profiles');

        $revisions = $connection->getRevisionsOfRecord(1);

        $this->assertEquals(1, count($revisions));

        $record = array_shift($revisions);

        $this->assertInstanceOf('AnyContent\Client\Record', $record);
    }

    public function testCreateAndFetchRecordRevisions()
    {
        $connection = $this->connection;

        if (!$connection) {
            $this->markTestSkipped('MySQL credentials missing.');
        }

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

        if (!$connection) {
            $this->markTestSkipped('MySQL credentials missing.');
        }

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

        if (!$connection) {
            $this->markTestSkipped('MySQL credentials missing.');
        }

        $config = $connection->getConfig('config1');

        $this->assertInstanceOf('AnyContent\Client\Config', $config);

        $this->assertEquals(0, $config->getRevision());
    }

    public function testCreateAndFetchConfigRevisions()
    {
        $connection = $this->connection;

        if (!$connection) {
            $this->markTestSkipped('MySQL credentials missing.');
        }

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

        if (!$connection) {
            $this->markTestSkipped('MySQL credentials missing.');
        }

        $revisions = $connection->getRevisionsOfConfig('config1');

        foreach ($revisions as $timeshift => $revision) {
            /** @var Record $revision */
            $connection->setTimeShift($timeshift);
            $config = $connection->getConfig('config1');
            $this->assertEquals($revision->getRevision(), $config->getRevision());
        }
    }
}