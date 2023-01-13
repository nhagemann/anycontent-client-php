<?php

namespace AnyContent\Client;

use AnyContent\Connection\Configuration\RecordsFileConfiguration;
use KVMLogger\KVMLoggerFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class RecordsFileRepositoryTest extends TestCase
{
    /** @var  RecordFilesReadWriteConnection */
    public $connection;

    public static function setUpBeforeClass(): void
    {
        $fs = new Filesystem();
        $fs->copy(__DIR__ . '/../../resources/RecordsFileExample/profiles.json', __DIR__ . '/../../resources/RecordsFileExample/temp.json', true);
        $fs->copy(__DIR__ . '/../../resources/RecordsFileExample/profiles.cmdl', __DIR__ . '/../../resources/RecordsFileExample/temp.cmdl', true);
    }

    public function setUp(): void
    {
        $configuration = new RecordsFileConfiguration();

        $configuration->addContentType('temp', __DIR__ . '/../../resources/RecordsFileExample/temp.cmdl', __DIR__ . '/../../resources/RecordsFileExample/temp.json');

        $connection = $configuration->createReadWriteConnection();

        $this->connection = $connection;
        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
    }

    public function testContentTypes()
    {
        $repository = new Repository('phpunit', $this->connection);

        $contentTypeNames = $repository->getContentTypeNames();

        $this->assertCount(1, $contentTypeNames);

        $this->assertTrue($repository->hasContentType('temp'));
    }

    public function testGetRecord()
    {
        $repository = new Repository('phpunit', $this->connection);

        $repository->selectContentType('temp');

        $record = $repository->getRecord(1);

        $this->assertEquals(1, $record->getID());
    }

    public function testGetRecords()
    {
        $repository = new Repository('phpunit', $this->connection);

        $repository->selectContentType('temp');

        $records = $repository->getRecords();

        $this->assertCount(608, $records);
    }
}
