<?php

declare(strict_types=1);

namespace Tests\AnyContent\Connection\ContentArchive;

use AnyContent\Connection\Configuration\ContentArchiveConfiguration;
use AnyContent\Connection\ContentArchiveReadWriteConnection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class ContentArchiveConfigTest extends TestCase
{
    /** @var  ContentArchiveReadWriteConnection */
    public $connection;

    public static function setUpBeforeClass(): void
    {
        $target = __DIR__ . '/../../../../../tmp/ExampleContentArchive';
        $source = __DIR__ . '/../../../resources/ContentArchiveExample1';

        $fs = new Filesystem();

        if (file_exists($target)) {
            $fs->remove($target);
        }

        $fs->mirror($source, $target);
    }

    public function setUp(): void
    {
        $target = __DIR__ . '/../../../../../tmp/ExampleContentArchive';

        $configuration = new ContentArchiveConfiguration();

        $configuration->setContentArchiveFolder($target);

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
        $connection = $this->connection;

        $config = $connection->getConfig('config1');

        $config->setProperty('ranking', 1);

        $this->assertEquals(1, $config->getProperty('ranking'));

        $connection->saveConfig($config);

        $config = $connection->getConfig('config1');

        $this->assertEquals('', $config->getProperty('ranking'));
    }
}
