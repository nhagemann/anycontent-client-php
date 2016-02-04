<?php

namespace AnyContent\Client;

use AnyContent\Connection\Configuration\ContentArchiveConfiguration;

use AnyContent\Connection\ContentArchiveReadWriteConnection;

use AnyContent\Filter\ANDFilter;
use AnyContent\Filter\ORFilter;
use AnyContent\Filter\PropertyFilter;
use KVMLogger\KVMLoggerFactory;
use KVMLogger\KVMLogger;
use Symfony\Component\Filesystem\Filesystem;

class PagingTest extends \PHPUnit_Framework_TestCase
{

    /** @var  ContentArchiveReadWriteConnection */
    public $connection;

    /** @var  Repository */
    public $repository;


    public static function setUpBeforeClass()
    {
        $target = __DIR__ . '/../../../tmp/ExampleContentArchive';
        $source = __DIR__ . '/../../resources/ContentArchiveExample2';

        $fs = new Filesystem();

        if (file_exists($target))
        {
            $fs->remove($target);
        }

        $fs->mirror($source, $target);

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
    }


    public function setUp()
    {
        $target = __DIR__ . '/../../../tmp/ExampleContentArchive';

        $configuration = new ContentArchiveConfiguration();

        $configuration->setContentArchiveFolder($target);

        $connection = $configuration->createReadWriteConnection();

        $this->connection = $connection;

        $this->repository = new Repository('phpunit',$this->connection);

    }


    public function testSliceRecords()
    {
        $this->repository->selectContentType('example01');

        for ($i = 1; $i <= 10; $i++)
        {
            $record = $this->repository->createRecord('New Record');
            $record->setProperty('source', $i);
            $id = $this->repository->saveRecord($record);
            $this->assertEquals($i, $id);
        }

        $records = $this->repository->getRecords('',['.id'], 1, 5);
        $this->assertCount(5, $records);
        $records = $this->repository->getRecords('',['.id'], 2, 5);
        $this->assertCount(5, $records);
        $records = $this->repository->getRecords('',['.id'], 3, 5);
        $this->assertCount(0, $records);
        $records = $this->repository->getRecords('',['.id'], 99, 99);
        $this->assertCount(0, $records);

        $records = $this->repository->getRecords('',['.id'], 1, 6);
        $this->assertCount(6, $records);
        $records = $this->repository->getRecords('',['.id'], 2, 6);
        $this->assertCount(4, $records);

    }


    public function testSliceFilteredRecords()
    {
        $this->repository->selectContentType('example01');

        $records = $this->repository->getRecords('source > 3', 'source',1, 5);
        $this->assertCount(5, $records);

        $this->assertEquals([ 4, 5, 6, 7, 8 ], array_keys($records));

        $records = $this->repository->getRecords('source > 3','source', 2, 5);
        $this->assertCount(2, $records);

        $this->assertEquals([ 9, 10 ], array_keys($records));
    }

}