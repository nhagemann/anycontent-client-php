<?php

namespace Tests\AnyContent\Connection\ContentArchive;

use AnyContent\Connection\Configuration\ContentArchiveConfiguration;
use AnyContent\Connection\ContentArchiveReadWriteConnection;
use KVMLogger\KVMLoggerFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class ContentArchiveAdminTest extends TestCase
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

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../../../tmp');
    }

    public function testNrOfContentTypes()
    {
        $definitions = $this->connection->getContentTypeDefinitions();

        $this->assertCount(2, $definitions);

        $this->connection->saveContentTypeCMDL('add', 'name');

        $definitions = $this->connection->getContentTypeDefinitions();

        $this->assertCount(3, $definitions);
    }

    public function testChangedCMDLOfContentType()
    {
        $cmdl = $this->connection->getCMDLForContentType('add');

        $this->assertEquals('name', $cmdl);

        $this->connection->saveContentTypeCMDL('add', 'name = textfield');

        $cmdl = $this->connection->getCMDLForContentType('add');

        $this->assertEquals('name = textfield', $cmdl);
    }

    public function testDeleteContentTypes()
    {
        $definitions = $this->connection->getContentTypeDefinitions();

        $this->assertCount(3, $definitions);

        $this->connection->deleteContentTypeCMDL('add');

        $definitions = $this->connection->getContentTypeDefinitions();

        $this->assertCount(2, $definitions);
    }

    public function testNrOfConfigTypes()
    {
        $definitions = $this->connection->getConfigTypeDefinitions();

        $this->assertCount(1, $definitions);

        $this->connection->saveConfigTypeCMDL('add', 'name');

        $definitions = $this->connection->getConfigTypeDefinitions();

        $this->assertCount(2, $definitions);
    }

    public function testChangedCMDLOfConfigType()
    {
        $cmdl = $this->connection->getCMDLForConfigType('add');

        $this->assertEquals('name', $cmdl);

        $this->connection->saveConfigTypeCMDL('add', 'name = textfield');

        $cmdl = $this->connection->getCMDLForConfigType('add');

        $this->assertEquals('name = textfield', $cmdl);
    }

    public function testDeleteConfigTypes()
    {
        $definitions = $this->connection->getConfigTypeDefinitions();

        $this->assertCount(2, $definitions);

        $this->connection->deleteConfigTypeCMDL('add');

        $definitions = $this->connection->getConfigTypeDefinitions();

        $this->assertCount(1, $definitions);
    }
}
