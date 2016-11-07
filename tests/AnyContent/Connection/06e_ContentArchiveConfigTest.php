<?php

namespace AnyContent\Connection;

use AnyContent\Client\Record;
use AnyContent\Connection\Configuration\ContentArchiveConfiguration;
use KVMLogger\KVMLoggerFactory;
use KVMLogger\KVMLogger;
use Symfony\Component\Filesystem\Filesystem;

class ContentArchiveConfigTest extends \PHPUnit_Framework_TestCase
{

    /** @var  ContentArchiveReadWriteConnection */
    public $connection;


    public static function setUpBeforeClass()
    {
        $target = __DIR__ . '/../../../tmp/ExampleContentArchive';
        $source = __DIR__ . '/../../resources/ContentArchiveExample1';

        $fs = new Filesystem();

        if (file_exists($target))
        {
            $fs->remove($target);
        }

        $fs->mirror($source, $target);

    }


    public function setUp()
    {
        $target = __DIR__ . '/../../../tmp/ExampleContentArchive';

        $configuration = new ContentArchiveConfiguration();

        $configuration->setContentArchiveFolder($target);

        $connection = $configuration->createReadWriteConnection();

        $this->connection = $connection;

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');

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

    public function testProtectedProperties()
    {
        KVMLogger::instance()->debug(__METHOD__);

        $connection = $this->connection;

        $config = $connection->getConfig('config1');

        $config->setProperty('ranking',1);

        $this->assertEquals(1,$config->getProperty('ranking'));

        $connection->saveConfig($config);

        $config = $connection->getConfig('config1');

        $this->assertEquals('',$config->getProperty('ranking'));
    }

}