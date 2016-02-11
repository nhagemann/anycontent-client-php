<?php

namespace AnyContent\Admin;

use AnyContent\Client\Repository;
use AnyContent\Connection\Configuration\MySQLSchemalessConfiguration;
use AnyContent\Connection\MySQLSchemalessReadWriteConnection;
use KVMLogger\KVMLoggerFactory;
use Symfony\Component\Filesystem\Filesystem;

class AdminTestMySQLSchemaless2Test extends \PHPUnit_Framework_TestCase
{

    /** @var  MySQLSchemalessReadWriteConnection */
    public $connection;

    /** @var  Repository */
    public $repository;


    public static function setUpBeforeClass()
    {
        if (defined('PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_HOST'))
        {
            $configuration = new MySQLSchemalessConfiguration();

            $configuration->initDatabase(PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_HOST, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_DBNAME, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_USERNAME, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_PASSWORD);
            $configuration->setRepositoryName('phpunit');

            $database = $configuration->getDatabase();

            $database->execute('DROP TABLE IF EXISTS _cmdl_');
            $database->execute('DROP TABLE IF EXISTS _counter_');
            $database->execute('DROP TABLE IF EXISTS phpunit$profiles');

            $connection = $configuration->createReadWriteConnection();

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
            $configuration->setRepositoryName('phpunit');
            $configuration->addContentTypes();
            $configuration->addConfigTypes();

            $connection = $configuration->createReadWriteConnection();

            $this->connection = $connection;
            $this->repository = new Repository('phpunit', $connection);

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

        $this->assertCount(0, $repository->getContentTypeNames());
        $this->assertCount(0, $connection->getConfigTypeNames());

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

        $connection->saveContentTypeCMDL('neu', $cmdl);

        $this->assertCount(1, $connection->getContentTypeNames());

        $this->assertEquals($cmdl, $connection->getCMDLForContentType('neu'));
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

        $this->assertCount(0, $connection->getContentTypeNames());

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

        $this->assertCount(0, $connection->getConfigTypeNames());

        $connection->saveConfigTypeCMDL('neu', $cmdl);

        $this->assertCount(1, $connection->getConfigTypeNames());

        $this->assertEquals($cmdl, $connection->getCMDLForConfigType('neu'));
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

        $this->assertCount(0, $connection->getConfigTypeNames());

        $this->setExpectedException('AnyContent\AnyContentClientException');
        $connection->getCMDLForConfigType('neu');
    }
}