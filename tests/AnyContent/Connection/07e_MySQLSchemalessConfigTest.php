<?php

namespace AnyContent\Connection;

use AnyContent\Client\Record;
use AnyContent\Client\Repository;
use AnyContent\Connection\Configuration\MySQLSchemalessConfiguration;
use KVMLogger\KVMLoggerFactory;
use KVMLogger\KVMLogger;

class MySQLSchemalessConfigTest extends \PHPUnit_Framework_TestCase
{

    /** @var  MySQLSchemalessReadWriteConnection */
    public $connection;


    public static function setUpBeforeClass()
    {
        if (defined('PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_HOST'))
        {
            $configuration = new MySQLSchemalessConfiguration();

            $configuration->initDatabase(PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_HOST, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_DBNAME, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_USERNAME, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_PASSWORD);
            $configuration->setCMDLFolder(__DIR__ . '/../../resources/ContentArchiveExample1/cmdl');
            $configuration->setRepositoryName('phpunit');
            $configuration->addContentTypes();
            $configuration->addConfigTypes();

            $database = $configuration->getDatabase();

            $database->execute('DROP TABLE IF EXISTS _cmdl_');
            $database->execute('DROP TABLE IF EXISTS _counter_');
            $database->execute('DROP TABLE IF EXISTS phpunit$$config');

            KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
        }
    }


    public function setUp()
    {
        if (defined('PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_HOST'))
        {
            $configuration = new MySQLSchemalessConfiguration();

            $configuration->initDatabase(PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_HOST, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_DBNAME, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_USERNAME, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_PASSWORD);
            $configuration->setCMDLFolder(__DIR__ . '/../../resources/ContentArchiveExample1/cmdl');
            $configuration->setRepositoryName('phpunit');
            $configuration->addContentTypes();
            $configuration->addConfigTypes();

            $connection = $configuration->createReadWriteConnection();

            $this->connection = $connection;
            $repository       = new Repository('phpunit',$connection);

            KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
        }
    }


    public function testConfigSameConnection()
    {
        $connection = $this->connection;

        $config = $connection->getConfig('config1');

        $this->assertInstanceOf('AnyContent\Client\Config', $config);

        $this->assertEquals('', $config->getProperty('city'));

        $config->setProperty('city', 'Frankfurt');

        $connection->saveConfig($config);

        $config = $connection->getConfig('config1');

        $this->assertInstanceOf('AnyContent\Client\Config', $config);

        $this->assertEquals('Frankfurt', $config->getProperty('city'));
    }


    public function testConfigNewConnection()
    {
        $connection = $this->connection;

        $config = $connection->getConfig('config1');

        $this->assertInstanceOf('AnyContent\Client\Config', $config);

        $this->assertEquals('Frankfurt', $config->getProperty('city'));
    }


    public function testViewsConfigSameConnection()
    {
        $connection = $this->connection;

        $connection->selectView('test');

        $config = $connection->getConfig('config1');

        $this->assertInstanceOf('AnyContent\Client\Config', $config);

        $this->assertEquals('', $config->getProperty('comment'));

        $config->setProperty('comment', 'Test');

        $connection->saveConfig($config);

        $config = $connection->getConfig('config1');

        $this->assertInstanceOf('AnyContent\Client\Config', $config);

        $this->assertEquals('Test', $config->getProperty('comment'));

        $connection->selectView('default');

        $config = $connection->getConfig('config1');

        $this->assertInstanceOf('AnyContent\Client\Config', $config);

        $this->assertEquals('Frankfurt', $config->getProperty('city'));

    }

}