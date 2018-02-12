<?php

namespace AnyContent\Client;

use AnyContent\Connection\Configuration\ContentArchiveConfiguration;

use AnyContent\Connection\Configuration\MySQLSchemalessConfiguration;
use AnyContent\Connection\ContentArchiveReadWriteConnection;

use AnyContent\Connection\MySQLSchemalessReadOnlyConnection;
use AnyContent\Connection\MySQLSchemalessReadWriteConnection;
use KVMLogger\KVMLoggerFactory;
use KVMLogger\KVMLogger;
use Symfony\Component\Filesystem\Filesystem;

class RepositoryRecordsAndRevisionsMySQLSchemalessConnectionTest extends \PHPUnit_Framework_TestCase
{

    /** @var  MySQLSchemalessReadWriteConnection */
    public $connection;

    /** @var  Repository */
    public $repository;

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

            $this->repository = new Repository('phpunit', $this->connection);
        }
    }

    public function testSupportsRevisions()
    {
        $connection = $this->connection;

        if (!$connection) {
            $this->markTestSkipped('MySQL credentials missing.');
        }

        $this->assertTrue($this->repository->supportsRevisions());
    }

    public function testCreatingRecordRevisions()
    {
        $connection = $this->connection;

        if (!$connection) {
            $this->markTestSkipped('MySQL credentials missing.');
        }

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
        $connection = $this->connection;

        if (!$connection) {
            $this->markTestSkipped('MySQL credentials missing.');
        }

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
        $connection = $this->connection;

        if (!$connection) {
            $this->markTestSkipped('MySQL credentials missing.');
        }

        $repository = $this->repository;

        $repository->selectContentType('profiles');

        /** @var $record Record * */
        $records = $repository->getRecords();

        $this->assertCount(3, $records);

        $t1 = $repository->getLastModifiedDate('profiles');

        $this->assertFalse($repository->deleteRecord(99));
        $this->assertTrue((boolean)$repository->deleteRecord(5));

        $t2 = $repository->getLastModifiedDate('profiles');

        $this->assertNotEquals($t1, $t2);
    }
}