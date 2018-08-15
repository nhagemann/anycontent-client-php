<?php

namespace AnyContent\Connection;

use AnyContent\Connection\Configuration\MySQLSchemalessConfiguration;
use AnyContent\Connection\Configuration\RestLikeConfiguration;

use KVMLogger\KVMLoggerFactory;
use KVMLogger\KVMLogger;

class RestLikeBasicConnectionConfigTest extends \PHPUnit_Framework_TestCase
{

    /** @var  RestLikeBasicReadWriteConnection */
    public $connection;

    static $randomString1;
    static $randomString2;


    /**
     * @throws \AnyContent\AnyContentClientException
     */
    public static function setUpBeforeClass()
    {
        self::$randomString1 = md5(time() + 'string1');
        self::$randomString2 = md5(time() + 'string2');

        // drop & create database
        $pdo = new \PDO('mysql:host=anycontent-client-phpunit-mysql;port=3306;charset=utf8', 'root', 'root');

        $pdo->exec('DROP DATABASE IF EXISTS phpunit');
        $pdo->exec('CREATE DATABASE phpunit');

        $configuration = new MySQLSchemalessConfiguration();

        $configuration->initDatabase('anycontent-client-phpunit-mysql', 'phpunit', 'root', 'root');
        $configuration->setRepositoryName('phpunit');

        $configuration->importCMDL(__DIR__ . '/../../resources/RestLikeBasicConnectionTests');

        $configuration->addContentTypes();

        $connection = $configuration->createReadWriteConnection();

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
    }


    public function setUp()
    {

        $configuration = new RestLikeConfiguration();

        $configuration->setUri(getenv('PHPUNIT_RESTLIKE_URI'));
        $connection = $configuration->createReadWriteConnection();

        $configuration->addContentTypes();
        $configuration->addConfigTypes();

        $this->connection = $connection;

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');

    }


    public function testConfigSameConnection()
    {
        $connection = $this->connection;

        $config = $connection->getConfig('config4');

        $this->assertInstanceOf('AnyContent\Client\Config', $config);

        $this->assertTrue($config->hasProperty('name'));

        $config->setProperty('name', self::$randomString1);

        $connection->saveConfig($config);

        $config = $connection->getConfig('config4');

        $this->assertInstanceOf('AnyContent\Client\Config', $config);

        $this->assertEquals(self::$randomString1, $config->getProperty('name'));
    }


    public function testConfigNewConnection()
    {
        $connection = $this->connection;

        $config = $connection->getConfig('config4');

        $this->assertInstanceOf('AnyContent\Client\Config', $config);

        $this->assertEquals(self::$randomString1, $config->getProperty('name'));
    }


    public function testViewsConfigSameConnection()
    {
        $connection = $this->connection;

        $connection->selectView('test');

        $config = $connection->getConfig('config4');

        $this->assertInstanceOf('AnyContent\Client\Config', $config);

        $this->assertTrue($config->hasProperty('a'));

        $config->setProperty('a', self::$randomString2);

        $connection->saveConfig($config);

        $config = $connection->getConfig('config4');

        $this->assertInstanceOf('AnyContent\Client\Config', $config);

        $this->assertEquals(self::$randomString2, $config->getProperty('a'));

        $connection->selectView('default');

        $config = $connection->getConfig('config4');

        $this->assertInstanceOf('AnyContent\Client\Config', $config);

        $this->assertEquals(self::$randomString1, $config->getProperty('name'));

    }


    public function testProtectedProperties()
    {
        echo 'ToDo';
        return;
        KVMLogger::instance()->debug(__METHOD__);

        $connection = $this->connection;

        $config = $connection->getConfig('config4');

        $config->setProperty('b', 1);

        $this->assertEquals(1, $config->getProperty('b'));

        $connection->saveConfig($config);

        $config = $connection->getConfig('config4');

        $this->assertEquals('', $config->getProperty('b'));
    }

}