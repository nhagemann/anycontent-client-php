<?php

declare(strict_types=1);

namespace Tests\AnyContent\Cache;

use AnyContent\Cache\CachingRepository;
use AnyContent\Connection\Configuration\RecordsFileConfiguration;
use AnyContent\Connection\RecordsFileReadWriteConnection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class SaveRecordsLastModifiedStategyTest extends TestCase
{
    /** @var  CachingRepository */
    protected $repository;

    /** @var  RecordsFileReadWriteConnection */
    protected $connection;

    public static function setUpBeforeClass(): void
    {
        $target = __DIR__ . '/../../../tmp/RecordsFileExample';
        $source = __DIR__ . '/../../resources/RecordsFileExample';

        $fs = new Filesystem();

        if (file_exists($target)) {
            $fs->remove($target);
        }

        $fs->mirror($source, $target);
    }

    public function setUp(): void
    {
        $target = __DIR__ . '/../../../tmp/RecordsFileExample';

        $configuration = new RecordsFileConfiguration();

        $configuration->addContentType('profiles', $target . '/profiles.cmdl', $target . '/profiles.json');

        $connection = $configuration->createReadWriteConnection();

        $this->connection = $connection;

        $repository = new CachingRepository('phpunit', $connection);
        $repository->selectLastModifiedCacheStrategy();
        $fs = new Filesystem();

        $fs->remove(__DIR__ . '/../../../tmp/phpfilecache');
        $fs->mkdir(__DIR__ . '/../../../tmp/phpfilecache');

        //$cache = DoctrineProvider::wrap(new FilesystemAdapter('', 0, __DIR__ . '/../../../tmp/phpfilecache'));
        //$repository->setCacheProvider($cache);
        $this->repository = $repository;
    }

    public function testChangeRecord()
    {
        $repository = $this->repository;
        $repository->enableSingleContentRecordCaching(60);
        $repository->enableAllContentRecordsCaching(60);
        $repository->selectContentType('profiles');

        $record = $repository->getRecord(1);
        $this->assertEquals('UDG United Digital Group', $record->getName());
        $record->setName('UDG');

        $repository->saveRecord($record);

        $record = $repository->getRecord(1);
        $this->assertEquals('UDG', $record->getName());
    }

    public function testChangedRecord()
    {
        $repository = $this->repository;
        $repository->enableSingleContentRecordCaching(60);
        $repository->enableAllContentRecordsCaching(60);

        $this->assertFalse($repository->hasExpirationCacheStrategy());
        $this->assertTrue($repository->hasLastModifiedCacheStrategy());

        $repository->selectContentType('profiles');

        $record = $repository->getRecord(1);
        $this->assertEquals('UDG', $record->getName());
    }
}
