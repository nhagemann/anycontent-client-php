<?php

namespace AnyContent\Connection;

use AnyContent\Connection\Configuration\RestLikeConfiguration;

use KVMLogger\KVMLoggerFactory;
use KVMLogger\KVMLogger;

class RestLikeBasicConnectionConfigTest extends \PHPUnit_Framework_TestCase
{

    /** @var  RestLikeBasicReadWriteConnection */
    public $connection;

    static $randomString1;
    static $randomString2;


    public static function setUpBeforeClass()
    {
        self::$randomString1 = md5(time());
        self::$randomString2 = md5(time());
    }


    public function setUp()
    {
        if (defined('PHPUNIT_CREDENTIALS_RESTLIKE_URL2'))
        {
            $configuration = new RestLikeConfiguration();

            $configuration->setUri(PHPUNIT_CREDENTIALS_RESTLIKE_URL2);
            $connection = $configuration->createReadWriteConnection();

            $configuration->addContentTypes();
            $configuration->addConfigTypes();

            $this->connection = $connection;

            KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
        }

    }


    public function testConfigSameConnection()
    {
        $connection = $this->connection;

        if (!$connection)
        {
            $this->markTestSkipped('RestLike Basic Connection credentials missing.');
        }

        $config = $connection->getConfig('config1');

        $this->assertInstanceOf('AnyContent\Client\Config', $config);

        $this->assertTrue($config->hasProperty('city'));

        $config->setProperty('city', self::$randomString1);

        $connection->saveConfig($config);

        $config = $connection->getConfig('config1');

        $this->assertInstanceOf('AnyContent\Client\Config', $config);

        $this->assertEquals(self::$randomString1, $config->getProperty('city'));
    }


    public function testConfigNewConnection()
    {
        $connection = $this->connection;

        if (!$connection)
        {
            $this->markTestSkipped('RestLike Basic Connection credentials missing.');
        }

        $config = $connection->getConfig('config1');

        $this->assertInstanceOf('AnyContent\Client\Config', $config);

        $this->assertEquals(self::$randomString1, $config->getProperty('city'));
    }


    public function testViewsConfigSameConnection()
    {
        $connection = $this->connection;

        if (!$connection)
        {
            $this->markTestSkipped('RestLike Basic Connection credentials missing.');
        }

        $connection->selectView('test');

        $config = $connection->getConfig('config1');

        $this->assertInstanceOf('AnyContent\Client\Config', $config);

        $this->assertTrue($config->hasProperty('comment'));

        $config->setProperty('comment', self::$randomString2);

        $connection->saveConfig($config);

        $config = $connection->getConfig('config1');

        $this->assertInstanceOf('AnyContent\Client\Config', $config);

        $this->assertEquals(self::$randomString2, $config->getProperty('comment'));

        $connection->selectView('default');

        $config = $connection->getConfig('config1');

        $this->assertInstanceOf('AnyContent\Client\Config', $config);

        $this->assertEquals(self::$randomString1, $config->getProperty('city'));

    }

}