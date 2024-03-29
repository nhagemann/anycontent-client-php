<?php

declare(strict_types=1);

namespace Tests\AnyContent\Client;

use AnyContent\Client\Record;
use AnyContent\Client\Repository;
use AnyContent\Connection\Configuration\ContentArchiveConfiguration;
use AnyContent\Connection\ContentArchiveReadWriteConnection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class RepositoryTest extends TestCase
{
    /** @var  ContentArchiveReadWriteConnection */
    public $connection;

    public static function setUpBeforeClass(): void
    {
        $target = __DIR__ . '/../../../tmp/ExampleContentArchive';
        $source = __DIR__ . '/../../resources/ContentArchiveExample2';

        $fs = new Filesystem();

        if (file_exists($target)) {
            $fs->remove($target);
        }

        $fs->mirror($source, $target);
    }

    public function setUp(): void
    {
        $target = __DIR__ . '/../../../tmp/ExampleContentArchive';

        $configuration = new ContentArchiveConfiguration();

        $configuration->setContentArchiveFolder($target);

        $connection = $configuration->createReadWriteConnection();

        $this->connection = $connection;
    }

    public function testContentTypes()
    {
        $repository = new Repository('phpunit', $this->connection);

        $contentTypeNames = $repository->getContentTypeNames();

        $this->assertCount(6, $contentTypeNames);

        $this->assertTrue($repository->hasContentType('example01'));
    }

    public function testConfigTypes()
    {
        $repository = new Repository('phpunit', $this->connection);

        $configTypeNames = $repository->getConfigTypeNames();

        $this->assertCount(3, $configTypeNames);

        $this->assertTrue($repository->hasConfigType('config1'));
    }

    public function testLastModified()
    {
        $repository = new Repository('phpunit', $this->connection);
        $this->assertIsFloat($repository->getLastModifiedDate());
    }

    public function testRecordCanAccessRepository()
    {
        $repository = new Repository('phpunit', $this->connection);

        $repository->selectContentType('example01');

        $definition = $repository->getContentTypeDefinition();

        $records = [];

        for ($i = 1; $i <= 5; $i++) {
            $record = new Record($definition, 'Test ' . $i);
            $records[] = $record;
        }

        $repository->saveRecords($records);

        $record = $repository->getRecord(1);

        $this->assertInstanceOf('AnyContent\Client\Repository', $record->getRepository());

        $records = $repository->getRecords();
        $record = array_shift($records);

        $this->assertInstanceOf('AnyContent\Client\Repository', $record->getRepository());
    }
}
