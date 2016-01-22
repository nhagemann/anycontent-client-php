<?php

namespace AnyContent\Connection;

use AnyContent\Client\Record;
use AnyContent\Connection\Configuration\RecordsFileConfiguration;
use AnyContent\Connection\RecordsFileReadWriteConnection;
use KVMLogger\KVMLoggerFactory;
use KVMLogger\KVMLogger;
use Symfony\Component\Filesystem\Filesystem;

class RecordsFileConfigTest extends \PHPUnit_Framework_TestCase
{

    /** @var  RecordsFileReadWriteConnection */
    public $connection;


    public static function setUpBeforeClass()
    {
        $target = __DIR__ . '/../../../tmp/RecordsFileExample';
        $source = __DIR__ . '/../../resources/RecordsFileExample';

        $fs = new Filesystem();

        if (file_exists($target))
        {
            $fs->remove($target);
        }

        $fs->mirror($source, $target);

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');

    }


    public function setUp()
    {
        $configuration = new RecordsFileConfiguration();

        $configuration->addContentType('profiles', __DIR__ . '/../../../tmp/RecordsFileExample/profiles.cmdl', __DIR__ . '/../../../tmp/RecordsFileExample/profiles.json');
        $configuration->addContentType('test', __DIR__ . '/../../../tmp/RecordsFileExample/test.cmdl', __DIR__ . '/../../../tmp/RecordsFileExample/test.json');
        $configuration->addConfigType('config1', __DIR__ . '/../../../tmp/RecordsFileExample/config1.cmdl', __DIR__ . '/../../../tmp/RecordsFileExample/config1.json');

        $connection = $configuration->createReadWriteConnection();

        $this->connection = $connection;
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