<?php

namespace AnyContent\Connection;

use AnyContent\Client\Record;
use AnyContent\Connection\Configuration\RecordsFileConfiguration;
use AnyContent\Connection\RecordsFileReadWriteConnection;
use KVMLogger\KVMLoggerFactory;
use KVMLogger\KVMLogger;
use Symfony\Component\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

class RecordsFileConfigTest extends TestCase
{
    /** @var  RecordsFileReadWriteConnection */
    public $connection;


    public static function setUpBeforeClass(): void    {
        $target = __DIR__ . '/../../../tmp/RecordsFileExample';
        $source = __DIR__ . '/../../resources/RecordsFileExample';

        $fs = new Filesystem();

        if (file_exists($target)) {
            $fs->remove($target);
        }

        $fs->mirror($source, $target);

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
    }


    public function setUp(): void    {
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

    public function testProtectedProperties()
    {
        KVMLogger::instance()->debug(__METHOD__);

        $connection = $this->connection;

        $config = $connection->getConfig('config1');

        $config->setProperty('ranking', 1);

        $this->assertEquals(1, $config->getProperty('ranking'));

        $connection->saveConfig($config);

        $config = $connection->getConfig('config1');

        $this->assertEquals('', $config->getProperty('ranking'));
    }
}
