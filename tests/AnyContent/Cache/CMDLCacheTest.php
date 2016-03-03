<?php

namespace AnyContent\Cache;

use AnyContent\Connection\Configuration\RecordsFileConfiguration;

use Doctrine\Common\Cache\PhpFileCache;

use KVMLogger\KVMLoggerFactory;
use KVMLogger\KVMLogger;
use Symfony\Component\Filesystem\Filesystem;

class CMDLCacheTest extends \PHPUnit_Framework_TestCase
{

    /** @var  CachingRepository */
    protected $repository;



    public function setUp()
    {

        $configuration = new RecordsFileConfiguration();

        $configuration->addContentType('profiles', __DIR__ . '/../../resources/RecordsFileExample/profiles.cmdl', __DIR__ . '/../../resources/RecordsFileExample/profiles.json');

        $connection = $configuration->createReadOnlyConnection();

        $repository = new CachingRepository('phpunit', $connection);



        $fs = new Filesystem();

        $fs->remove(__DIR__ . '/../../../tmp/phpfilecache');
        $fs->mkdir(__DIR__ . '/../../../tmp/phpfilecache');

        $cache = new PhpFileCache(__DIR__ . '/../../../tmp/phpfilecache');

        $repository->setCacheProvider($cache);
        $repository->selectLastModifiedCacheStrategy();
        $this->repository = $repository;

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
    }



    public function testGetRecordWithoutCMDLCache()
    {
        $repository = $this->repository;
        $repository->enableSingleContentRecordCaching(60);

        $repository->selectContentType('profiles');

        $repository->getRecord(1);
        $repository->getRecord(1);

        $this->assertEquals(2,$repository->getCacheProvider()->getMissCounter());
        $this->assertEquals(1,$repository->getCacheProvider()->getHitCounter());

    }

    public function testGetRecordWithCMDLCache()
    {
        $repository = $this->repository;

        $repository->getCacheProvider()->clearHitMissCounter();
        $repository->enableSingleContentRecordCaching(60);
        $repository->enableCmdlCaching(60);

        $repository->selectContentType('profiles');

        $repository->getRecord(1);
        $repository->getRecord(1);

        $this->assertEquals(3,$repository->getCacheProvider()->getMissCounter());
        $this->assertEquals(5,$repository->getCacheProvider()->getHitCounter());

        $definition = $repository->getContentTypeDefinition('profiles');

        $this->assertEquals(3,$repository->getCacheProvider()->getMissCounter());
        $this->assertEquals(7,$repository->getCacheProvider()->getHitCounter());
    }



}