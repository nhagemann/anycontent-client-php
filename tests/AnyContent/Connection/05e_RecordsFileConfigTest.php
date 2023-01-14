<?php

namespace Tests\AnyContent\Connection;

use AnyContent\Connection\Configuration\RecordFilesConfiguration;
use AnyContent\Connection\RecordFilesReadWriteConnection;
use KVMLogger\KVMLogger;
use KVMLogger\KVMLoggerFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class RecordFilesConfigTest extends TestCase
{
    /** @var  RecordFilesReadWriteConnection */
    public $connection;

    public static function setUpBeforeClass(): void
    {
        $source = __DIR__ . '/../..//resources/RecordFilesExample';
        $target = __DIR__ . '/../../../tmp/RecordFilesReadWriteConnection';

        $fs = new Filesystem();

        if (file_exists($target)) {
            $fs->remove($target);
        }

        $fs->mirror($source, $target);
    }

    public function setUp(): void
    {
        $target = __DIR__ . '/../../../tmp/RecordFilesReadWriteConnection';

        $configuration = new RecordFilesConfiguration();

        $configuration->addContentType('profiles', $target . '/profiles.cmdl', $target . '/records/profiles');
        $configuration->addContentType('test', $target . '/test.cmdl', $target . '/records/test');
        $configuration->addConfigType('config1', $target . '/config1.cmdl', $target . '/records/profiles/config1.json');

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

        $config->setProperty('ranking', 1);

        $this->assertEquals(1, $config->getProperty('ranking'));

        $connection->saveConfig($config);

        $config = $connection->getConfig('config1');

        $this->assertEquals('', $config->getProperty('ranking'));
    }
}
