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

class SortingTest extends \PHPUnit_Framework_TestCase
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


    public function testGetSortedRecords()
    {
        $this->repository->selectContentType('example01');

        for ($i = 1; $i <= 10; $i++)
        {
            $record = $this->repository->createRecord('New Record');
            $record->setPosition(11-$i);
            $id = $this->repository->saveRecord($record);
            $this->assertEquals($i, $id);
        }

        $records = $this->repository->getSortedRecords(0);
        $this->assertEquals([ 10, 9, 8, 7, 6, 5, 4, 3, 2, 1 ], array_keys($records));

    }



    public function testSortRecords()
    {
        $this->repository->selectContentType('example01');

        $this->repository->sortRecords([10=>0,9=>10,8=>10]);

        $records = $this->repository->getRecords();

        $this->assertEquals(10,$records[9]->getParent());
        $this->assertEquals(10,$records[8]->getParent());
        $this->assertEquals(0,$records[10]->getParent());
        $this->assertNull($records[1]->getParent());
        $this->assertNotNull($records[10]->getParent());

        $this->assertEquals(1,$records[9]->getPosition());
        $this->assertEquals(2,$records[8]->getPosition());


        $records = $this->repository->getSortedRecords(0);
        $this->assertEquals([ 10,9,8], array_keys($records));

        $this->assertEquals(2,$records[9]->getLevel());
        $this->assertEquals(2,$records[8]->getLevel());
        $this->assertEquals(1,$records[10]->getLevel());

        $records = $this->repository->getSortedRecords(10);
        $this->assertEquals([ 9,8], array_keys($records));
    }




}