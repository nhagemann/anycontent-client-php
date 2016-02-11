<?php

namespace AnyContent\Admin;



use AnyContent\Client\Repository;
use AnyContent\Connection\Configuration\MySQLSchemalessConfiguration;
use AnyContent\Connection\MySQLSchemalessReadWriteConnection;
use KVMLogger\KVMLoggerFactory;
use Symfony\Component\Filesystem\Filesystem;

class AdminTestMySQLSchemaless1Test extends \PHPUnit_Framework_TestCase
{
    /** @var  MySQLSchemalessReadWriteConnection */
    public $connection;

    /** @var  Repository */
    public $repository;

    public static function setUpBeforeClass()
    {
        if (defined('PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_HOST'))
        {
            $source = __DIR__ . '/../../resources/ContentArchiveExample1/cmdl';
            $target = __DIR__ . '/../../../tmp/MySqlSchemaLessCMDL';

            $fs = new Filesystem();

            if (file_exists($target))
            {
                $fs->remove($target);
            }

            $fs->mirror($source, $target);

            $configuration = new MySQLSchemalessConfiguration();

            $configuration->initDatabase(PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_HOST, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_DBNAME, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_USERNAME, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_PASSWORD);
            $configuration->setCMDLFolder($target);
            $configuration->setRepositoryName('phpunit');
            $configuration->addContentTypes();

            $database = $configuration->getDatabase();

            $database->execute('DROP TABLE IF EXISTS _cmdl_');
            $database->execute('DROP TABLE IF EXISTS _counter_');
            $database->execute('DROP TABLE IF EXISTS phpunit$profiles');

            $connection = $configuration->createReadWriteConnection();

            $repository = new Repository('phpunit',$connection);
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
        if (defined('PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_HOST'))
        {
            $target = __DIR__ . '/../../../tmp/MySqlSchemaLessCMDL';

            $configuration = new MySQLSchemalessConfiguration();

            $configuration->initDatabase(PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_HOST, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_DBNAME, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_USERNAME, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_PASSWORD);
            $configuration->setCMDLFolder($target);
            $configuration->setRepositoryName('phpunit');
            $configuration->addContentTypes();
            $configuration->addConfigTypes();

            $connection = $configuration->createReadWriteConnection();

            $this->connection = $connection;
            $this->repository       = new Repository('phpunit',$connection);

            KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
        }
    }


    public function testSetupAsExpected()
    {
        $connection = $this->connection;

        if (!$connection)
        {
            $this->markTestSkipped('MySQL credentials missing.');
        }

        $repository = $this->repository;

        $this->assertCount(2,$repository->getContentTypeNames());
        $this->assertCount(1,$connection->getConfigTypeNames());

        $this->assertTrue($repository->isWritable());
        $this->assertTrue($repository->isAdministrable());
    }

    public function testAddContentType()
    {
        $connection = $this->connection;

        if (!$connection)
        {
            $this->markTestSkipped('MySQL credentials missing.');
        }

        $repository = $this->repository;

        $cmdl = 'Name';

        $connection->saveContentTypeCMDL('neu',$cmdl);

        $this->assertCount(3,$connection->getContentTypeNames());

        $this->assertEquals($cmdl,$connection->getCMDLForContentType('neu'));
    }

    public function testDeleteContentType()
    {
        $connection = $this->connection;

        if (!$connection)
        {
            $this->markTestSkipped('MySQL credentials missing.');
        }

        $repository = $this->repository;

        $connection->deleteContentTypeCMDL('neu');

        $this->assertCount(2,$connection->getContentTypeNames());

        $this->setExpectedException('AnyContent\AnyContentClientException');
        $connection->getCMDLForContentType('neu');
    }

    public function testAddConfigType()
    {
        $connection = $this->connection;

        if (!$connection)
        {
            $this->markTestSkipped('MySQL credentials missing.');
        }

        $repository = $this->repository;

        $cmdl = 'Name';

        $this->assertCount(1,$connection->getConfigTypeNames());

        $connection->saveConfigTypeCMDL('neu',$cmdl);

        $this->assertCount(2,$connection->getConfigTypeNames());

        $this->assertEquals($cmdl,$connection->getCMDLForConfigType('neu'));
    }

    public function testDeleteConfigType()
    {
        $connection = $this->connection;

        if (!$connection)
        {
            $this->markTestSkipped('MySQL credentials missing.');
        }

        $repository = $this->repository;

        $connection->deleteConfigTypeCMDL('neu');

        $this->assertCount(1,$connection->getConfigTypeNames());

        $this->setExpectedException('AnyContent\AnyContentClientException');
        $connection->getCMDLForConfigType('neu');
    }
}