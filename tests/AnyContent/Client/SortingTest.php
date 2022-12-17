<?php

namespace AnyContent\Client;

use AnyContent\Client\Util\MenuBuilder;
use AnyContent\Connection\Configuration\ContentArchiveConfiguration;
use AnyContent\Connection\ContentArchiveReadWriteConnection;
use AnyContent\Filter\ANDFilter;
use AnyContent\Filter\ORFilter;
use AnyContent\Filter\PropertyFilter;
use KVMLogger\KVMLoggerFactory;
use KVMLogger\KVMLogger;
use Symfony\Component\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

class SortingTest extends TestCase
{
    /** @var  ContentArchiveReadWriteConnection */
    public $connection;

    /** @var  Repository */
    public $repository;


    public static function setUpBeforeClass(): void
    {
        $target = __DIR__ . '/../../../tmp/ExampleContentArchive';
        $source = __DIR__ . '/../../resources/ContentArchiveExample2';

        $fs = new Filesystem();

        if (file_exists($target)) {
            $fs->remove($target);
        }

        $fs->mirror($source, $target);

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
    }


    public function setUp(): void
    {
        $target = __DIR__ . '/../../../tmp/ExampleContentArchive';

        $configuration = new ContentArchiveConfiguration();

        $configuration->setContentArchiveFolder($target);

        $connection = $configuration->createReadWriteConnection();

        $this->connection = $connection;

        $this->repository = new Repository('phpunit', $this->connection);
    }


    public function testGetSortedRecords()
    {
        $this->repository->selectContentType('example01');

        for ($i = 1; $i <= 10; $i++) {
            $record = $this->repository->createRecord('New Record');
            $record->setPosition(11 - $i);
            $id = $this->repository->saveRecord($record);
            $this->assertEquals($i, $id);
        }

        $records = $this->repository->getSortedRecords(0);
        $this->assertEquals([ 10, 9, 8, 7, 6, 5, 4, 3, 2, 1 ], array_keys($records));
    }


    public function testSortRecords()
    {
        $this->repository->selectContentType('example01');

        $this->repository->sortRecords([ 10 => 0, 9 => 10, 8 => 10 ]);

        $records = $this->repository->getRecords();

        $this->assertEquals(10, $records[9]->getParent());
        $this->assertEquals(10, $records[8]->getParent());
        $this->assertEquals(0, $records[10]->getParent());
        $this->assertNull($records[1]->getParent());
        $this->assertNotNull($records[10]->getParent());

        $this->assertEquals(1, $records[9]->getPosition());
        $this->assertEquals(2, $records[8]->getPosition());

        $records = $this->repository->getSortedRecords(0);
        $this->assertEquals([ 10, 9, 8 ], array_keys($records));

        $this->assertEquals(2, $records[9]->getLevel());
        $this->assertEquals(2, $records[8]->getLevel());
        $this->assertEquals(1, $records[10]->getLevel());

        $records = $this->repository->getSortedRecords(10);
        $this->assertEquals([ 9, 8 ], array_keys($records));
    }


    public function testReverseSortRecords()
    {
        $this->repository->selectContentType('example01');

        // 1
        // 2
        //  -- 4
        //     -- 8
        //     -- 5
        //  -- 6
        // 3
        //  -- 9
        // 7
        $this->repository->sortRecords([ 1 => 0, 2 => 0, 4 => 2, 8 => 4, 5 => 4, 6 => 2, 3 => 0, 9 => 3, 7 => 0 ]);

        $records = $this->repository->getRecords();

        $this->assertEquals(2, $records[6]->getParent());

        $records = $this->repository->getSortedRecords(4);
        $this->assertEquals([ 8, 5 ], array_keys($records));

        $records = $this->repository->getSortedRecords(4, true);
        $this->assertEquals([ 4, 8, 5 ], array_keys($records));

        $records = $this->repository->getSortedRecords(4, true, 0, 1);
        $this->assertEquals([ 2, 4 ], array_keys($records));

        $records = $this->repository->getSortedRecords(4, false, 0, 1);
        $this->assertEquals([ 2 ], array_keys($records));

        $records = $this->repository->getSortedRecords(5, true, 0, 1);
        $this->assertEquals([ 2, 4, 5 ], array_keys($records));

        $records = $this->repository->getSortedRecords(9, true, 0, 1);
        $this->assertEquals([ 3, 9 ], array_keys($records));

        $records = $this->repository->getSortedRecords(4, true, 1, 1);
        $this->assertEquals([ 2, 4, 8, 5 ], array_keys($records));

        $records = $this->repository->getSortedRecords(2, false, 1);
        $this->assertEquals([ 4, 6 ], array_keys($records));

        $records = MenuBuilder::getBreadcrumb($this->repository, 'example01', 8);
        $this->assertEquals([ 2, 4, 8 ], array_keys($records));

        $records = MenuBuilder::getExpandedMenu($this->repository, 'example01', 8);
        $this->assertEquals([ 1, 2, 4, 8, 5, 6, 3, 7 ], array_keys($records));

        $records = MenuBuilder::getExpandedMenu($this->repository, 'example01', 6);
        $this->assertEquals([ 1, 2, 4, 6, 3, 7 ], array_keys($records));

        $records = MenuBuilder::getExpandedMenu($this->repository, 'example01', 4);
        $this->assertEquals([ 1, 2, 4, 8, 5, 6, 3, 7 ], array_keys($records));
    }
}
