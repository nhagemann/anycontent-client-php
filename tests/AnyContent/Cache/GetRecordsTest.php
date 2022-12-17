<?php

namespace AnyContent\Cache;

use AnyContent\Connection\Configuration\RecordsFileConfiguration;
use Doctrine\Common\Cache\PhpFileCache;
use KVMLogger\KVMLoggerFactory;
use KVMLogger\KVMLogger;
use Symfony\Component\Filesystem\Filesystem;

class GetRecordsTest extends \PHPUnit_Framework_TestCase
{
    /** @var  CachingRepository */
    protected $repository;



    public function setUp()
    {

        $configuration = new RecordsFileConfiguration();

        $configuration->addContentType('profiles', __DIR__ . '/../../resources/RecordsFileExample/profiles.cmdl', __DIR__ . '/../../resources/RecordsFileExample/profiles.json');

        $connection = $configuration->createReadOnlyConnection();

        $repository = new CachingRepository('phpunit', $connection);

        $repository->selectLastModifiedCacheStrategy();
        $fs = new Filesystem();

        $fs->remove(__DIR__ . '/../../../tmp/phpfilecache');
        $fs->mkdir(__DIR__ . '/../../../tmp/phpfilecache');

        $cache = new PhpFileCache(__DIR__ . '/../../../tmp/phpfilecache');

        $repository->setCacheProvider($cache);
        $this->repository = $repository;

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
    }


    public function testGetRecords()
    {
        $repository = $this->repository;
        $repository->enableAllContentRecordsCaching(60);


        $repository->selectContentType('profiles');

        $records = $repository->getRecords();

        $this->assertCount(608, $records);

        $records = $repository->getRecords();

        $this->assertCount(608, $records);

        $this->assertEquals(3, $repository->getCacheProvider()->getMissCounter());
        $this->assertEquals(1, $repository->getCacheProvider()->getHitCounter());
    }


    public function testGetRecord()
    {
        $repository = $this->repository;
        $repository->enableSingleContentRecordCaching(60);

        $repository->selectContentType('profiles');

        $repository->getRecord(1);
        $repository->getRecord(1);

        $this->assertEquals(2, $repository->getCacheProvider()->getMissCounter());
        $this->assertEquals(1, $repository->getCacheProvider()->getHitCounter());
    }

    public function testQueryRecords()
    {
        $repository = $this->repository;
        $repository->enableContentQueryRecordsCaching(60);

        $repository->selectContentType('profiles');

        $records = $repository->getRecords('', ['.id'], 1, 10);

        $this->assertCount(10, $records);

        $records = $repository->getRecords('', ['.id'], 1, 10);

        $this->assertCount(10, $records);

        $this->assertEquals(2, $repository->getCacheProvider()->getMissCounter());
        $this->assertEquals(1, $repository->getCacheProvider()->getHitCounter());
    }

    public function testGetRecordWithIdNull()
    {
        $repository = $this->repository;
        $repository->selectContentType('profiles');

        $record = $repository->getRecord(null);
        $this->assertFalse($record);
        $record = $repository->getRecord(null);
        $this->assertFalse($record);

        $repository->enableSingleContentRecordCaching(60);

        $record = $repository->getRecord(null);
        $this->assertFalse($record);

        $record = $repository->getRecord(null);
        $this->assertFalse($record);
    }
}
