<?php

namespace AnyContent\Cache;

use AnyContent\Client\Repository;
use AnyContent\Connection\Configuration\RecordsFileConfiguration;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use KVMLogger\KVMLoggerFactory;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

class SaveRecordsExpirationStrategyTest extends TestCase
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

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
    }


    public function setUp(): void
    {

        $target = __DIR__ . '/../../../tmp/RecordsFileExample';

        $configuration = new RecordsFileConfiguration();

        $configuration->addContentType('profiles', $target . '/profiles.cmdl', $target . '/profiles.json');

        $connection = $configuration->createReadWriteConnection();

        $this->connection = $connection;

        $repository = new CachingRepository('phpunit', $connection);

        $fs = new Filesystem();

        $fs->remove(__DIR__ . '/../../../tmp/phpfilecache');
        $fs->mkdir(__DIR__ . '/../../../tmp/phpfilecache');

        $cache = DoctrineProvider::wrap(new FilesystemAdapter('', 0, __DIR__ . '/../../../tmp/phpfilecache'));

        $repository->setCacheProvider($cache);
        $this->repository = $repository;

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
    }


    public function testChangeRecord()
    {
        $repository = $this->repository;
        $repository->enableSingleContentRecordCaching(60);
        $repository->enableAllContentRecordsCaching(60);
        $repository->selectContentType('profiles');

        $this->assertTrue($repository->hasExpirationCacheStrategy());
        $this->assertFalse($repository->hasLastModifiedCacheStrategy());

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
        $repository->selectContentType('profiles');

        $record = $repository->getRecord(1);
        $this->assertEquals('UDG', $record->getName());
    }


    /**
     * Test showing the flaws of the full flash cache strategy
     *
     * @throws \AnyContent\AnyContentClientException
     */
    public function testCacheStrategyFailure()
    {
        $repository = $this->repository;
        $repository->enableSingleContentRecordCaching(60);
        $repository->enableAllContentRecordsCaching(60);
        $repository->selectContentType('profiles');

        $record = $repository->getRecord(1);
        $this->assertEquals('UDG', $record->getName());

        $nonCachingRepository = new Repository('phpunit', $this->connection);
        $nonCachingRepository->selectContentType('profiles');

        $record = $nonCachingRepository->getRecord(1);
        $this->assertEquals('UDG', $record->getName());
        $record->setName('');
        $nonCachingRepository->saveRecord($record);
        $record = $nonCachingRepository->getRecord(1);
        $this->assertEquals('', $record->getName());

        $record = $repository->getRecord(1);
        $this->assertEquals('UDG', $record->getName());
    }
}
