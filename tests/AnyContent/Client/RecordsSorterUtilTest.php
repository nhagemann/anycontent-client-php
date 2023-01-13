<?php

namespace AnyContent\Client;

use AnyContent\Client\Util\RecordsSorter;
use AnyContent\Connection\Configuration\ContentArchiveConfiguration;
use AnyContent\Connection\ContentArchiveReadWriteConnection;
use KVMLogger\KVMLoggerFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class RecordsSorterUtilTest extends TestCase
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

    public function testOrderRecords()
    {
        $records = [ ];

        $this->repository->selectContentType('example01');

        for ($i = 1; $i <= 5; $i++) {
            $record = $this->repository->createRecord('New Record');
            $record->setId($i);
            $record->setProperty('source', $i);
            $record->setProperty('article', 'A');
            $records[$i] = $record;
        }
        for ($i = 6; $i <= 10; $i++) {
            $record = $this->repository->createRecord('New Record');
            $record->setId($i);
            $record->setProperty('source', $i);
            $record->setProperty('article', 'B');
            $records[$i] = $record;
        }

        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9, 10], array_keys($records));
        $records = RecordsSorter::orderRecords($records, 'source+');
        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9, 10], array_keys($records));
        $records = RecordsSorter::orderRecords($records, 'source-');
        $this->assertEquals([10, 9, 8, 7, 6, 5, 4, 3, 2, 1], array_keys($records));

        $records = RecordsSorter::orderRecords($records, 'article+');
        foreach (array_slice($records, 0, 5) as $record) {
            $this->assertEquals($record->getProperty('article'), 'A');
        }
        foreach (array_slice($records, 5, 5) as $record) {
            $this->assertEquals($record->getProperty('article'), 'B');
        }

        $records = RecordsSorter::orderRecords($records, 'article-');
        foreach (array_slice($records, 0, 5) as $record) {
            $this->assertEquals($record->getProperty('article'), 'B');
        }
        foreach (array_slice($records, 5, 5) as $record) {
            $this->assertEquals($record->getProperty('article'), 'A');
        }

        $records = RecordsSorter::orderRecords($records, ['article-', 'source+']);

        $this->assertEquals([6, 7, 8, 9, 10, 1, 2, 3, 4, 5], array_keys($records));

        $records = RecordsSorter::orderRecords($records, ['.id']);
        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9, 10], array_keys($records));

        $records = RecordsSorter::orderRecords($records, ['.id-']);
        $this->assertEquals([10, 9, 8, 7, 6, 5, 4, 3, 2, 1], array_keys($records));
    }

    public function testSortByUserInfo()
    {
        $this->repository->selectContentType('example01');

        $userInfo1 = new UserInfo('a', 'a', 'a', 1);
        $userInfo2 = new UserInfo('b', 'b', 'b', 2);

        $record1 = $this->repository->createRecord('New Record')->setId(1);
        $record2 = $this->repository->createRecord('New Record')->setId(2);

        $records = [1 => $record1, 2 => $record2];

        $record1->setCreationUserInfo($userInfo1);
        $record2->setCreationUserInfo($userInfo2);

        $records = RecordsSorter::orderRecords($records, ['.info.creation.username']);
        $this->assertEquals([1, 2], array_keys($records));

        $records = RecordsSorter::orderRecords($records, ['.info.creation.firstname']);
        $this->assertEquals([1, 2], array_keys($records));

        $records = RecordsSorter::orderRecords($records, ['.info.creation.lastname']);
        $this->assertEquals([1, 2], array_keys($records));

        $records = RecordsSorter::orderRecords($records, ['.info.creation.timestamp']);
        $this->assertEquals([1, 2], array_keys($records));

        $records = RecordsSorter::orderRecords($records, ['.info.creation.username-']);
        $this->assertEquals([2, 1], array_keys($records));

        $records = RecordsSorter::orderRecords($records, ['.info.creation.firstname-']);
        $this->assertEquals([2, 1], array_keys($records));

        $records = RecordsSorter::orderRecords($records, ['.info.creation.lastname-']);
        $this->assertEquals([2, 1], array_keys($records));

        $records = RecordsSorter::orderRecords($records, ['.info.creation.timestamp-']);
        $this->assertEquals([2, 1], array_keys($records));

        $record1->setLastChangeUserInfo($userInfo1);
        $record2->setLastChangeUserInfo($userInfo2);

        $records = RecordsSorter::orderRecords($records, ['.info.lastchange.username']);
        $this->assertEquals([1, 2], array_keys($records));

        $records = RecordsSorter::orderRecords($records, ['.info.lastchange.firstname']);
        $this->assertEquals([1, 2], array_keys($records));

        $records = RecordsSorter::orderRecords($records, ['.info.lastchange.lastname']);
        $this->assertEquals([1, 2], array_keys($records));

        $records = RecordsSorter::orderRecords($records, ['.info.lastchange.timestamp']);
        $this->assertEquals([1, 2], array_keys($records));

        $records = RecordsSorter::orderRecords($records, ['.info.lastchange.username-']);
        $this->assertEquals([2, 1], array_keys($records));

        $records = RecordsSorter::orderRecords($records, ['.info.lastchange.firstname-']);
        $this->assertEquals([2, 1], array_keys($records));

        $records = RecordsSorter::orderRecords($records, ['.info.lastchange.lastname-']);
        $this->assertEquals([2, 1], array_keys($records));

        $records = RecordsSorter::orderRecords($records, ['.info.lastchange.timestamp-']);
        $this->assertEquals([2, 1], array_keys($records));
    }

    public function testOrderPositionProperty()
    {
        $cmdl = 'name
        @sortable';

        $records    = [ ];
        $records[1] = RecordFactory::instance()->createRecordFromCMDL($cmdl, ['name' => 'F', 'position' => 1])
                                   ->setId(1);
        $records[2] = RecordFactory::instance()->createRecordFromCMDL($cmdl, ['name' => 'E', 'position' => 2])
                                   ->setId(2);
        $records[3] = RecordFactory::instance()->createRecordFromCMDL($cmdl, ['name' => 'D', 'position' => 3])
                                   ->setId(3);
        $records[4] = RecordFactory::instance()->createRecordFromCMDL($cmdl, ['name' => 'C', 'position' => 4])
                                   ->setId(4);
        $records[5] = RecordFactory::instance()->createRecordFromCMDL($cmdl, ['name' => 'B', 'position' => 5])
                                   ->setId(5);
        $records[6] = RecordFactory::instance()->createRecordFromCMDL($cmdl, ['name' => 'A'])->setId(6);

        $records = RecordsSorter::orderRecords($records, ['name']);
        $this->assertEquals([6, 5, 4, 3, 2, 1], array_keys($records));

        $records = RecordsSorter::orderRecords($records, ['name-']);
        $this->assertEquals([1, 2, 3, 4, 5, 6], array_keys($records));

        $records = RecordsSorter::orderRecords($records, ['position']);
        $this->assertEquals([6, 1, 2, 3, 4, 5], array_keys($records));
    }

    public function testSortingList()
    {
        $cmdl = 'name
        @sortable';

        $records    = [ ];
        $records[1] = RecordFactory::instance()
                                   ->createRecordFromCMDL($cmdl, ['name' => 'F', 'position' => 1, 'parent' => 0])
                                   ->setId(1);
        $records[2] = RecordFactory::instance()
                                   ->createRecordFromCMDL($cmdl, ['name' => 'E', 'position' => 2, 'parent' => 0])
                                   ->setId(2);
        $records[3] = RecordFactory::instance()
                                   ->createRecordFromCMDL($cmdl, ['name' => 'D', 'position' => 3, 'parent' => 0])
                                   ->setId(3);
        $records[4] = RecordFactory::instance()
                                   ->createRecordFromCMDL($cmdl, ['name' => 'C', 'position' => 4, 'parent' => 0])
                                   ->setId(4);
        $records[5] = RecordFactory::instance()
                                   ->createRecordFromCMDL($cmdl, ['name' => 'B', 'position' => 5, 'parent' => 0])
                                   ->setId(5);
        $records[6] = RecordFactory::instance()->createRecordFromCMDL($cmdl, ['name' => 'A'])->setId(6);

        $records = RecordsSorter::orderRecords($records, ['name']);
        $this->assertEquals([6, 5, 4, 3, 2, 1], array_keys($records));

        $records = RecordsSorter::orderRecords($records, ['name-']);
        $this->assertEquals([1, 2, 3, 4, 5, 6], array_keys($records));

        $records = RecordsSorter::orderRecords($records, ['position']);
        $this->assertEquals([6, 1, 2, 3, 4, 5], array_keys($records));

        $records = RecordsSorter::sortRecords($records);
        $this->assertEquals([1, 2, 3, 4, 5], array_keys($records));

        $records[1]->setPosition(6);

        $records = RecordsSorter::sortRecords($records);
        $this->assertEquals([2, 3, 4, 5, 1], array_keys($records));
    }

    public function testSortingTree()
    {
        //        A
        //     B    C
        //   D  E     F
        //          G   H
        //
        $cmdl = 'name
        @sortable';

        $records    = [ ];
        $records[1] = RecordFactory::instance()
                                   ->createRecordFromCMDL($cmdl, ['name' => 'A', 'position' => 1, 'parent' => 0])
                                   ->setId(1);
        $records[2] = RecordFactory::instance()
                                   ->createRecordFromCMDL($cmdl, ['name' => 'B', 'position' => 1, 'parent' => 1])
                                   ->setId(2);
        $records[3] = RecordFactory::instance()
                                   ->createRecordFromCMDL($cmdl, ['name' => 'C', 'position' => 1, 'parent' => 1])
                                   ->setId(3);
        $records[4] = RecordFactory::instance()
                                   ->createRecordFromCMDL($cmdl, ['name' => 'D', 'position' => 1, 'parent' => 2])
                                   ->setId(4);
        $records[5] = RecordFactory::instance()
                                   ->createRecordFromCMDL($cmdl, ['name' => 'E', 'position' => 2, 'parent' => 2])
                                   ->setId(5);
        $records[6] = RecordFactory::instance()
                                   ->createRecordFromCMDL($cmdl, ['name' => 'F', 'position' => 1, 'parent' => 3])
                                   ->setId(6);
        $records[7] = RecordFactory::instance()
                                   ->createRecordFromCMDL($cmdl, ['name' => 'G', 'position' => 1, 'parent' => 6])
                                   ->setId(7);
        $records[8] = RecordFactory::instance()
                                   ->createRecordFromCMDL($cmdl, ['name' => 'H', 'position' => 2, 'parent' => 6])
                                   ->setId(8);

        $subset = RecordsSorter::sortRecords($records);
        $this->assertEquals([1, 2, 4, 5, 3, 6, 7, 8], array_keys($subset));

        $subset = RecordsSorter::sortRecords($records, 0, false, 1);
        $this->assertEquals([1], array_keys($subset));

        $subset = RecordsSorter::sortRecords($records, 0, false, 2);
        $this->assertEquals([1, 2, 3], array_keys($subset));

        $subset = RecordsSorter::sortRecords($records, 2, false, 1); // B
        $this->assertEquals([4, 5], array_keys($subset));

        $subset = RecordsSorter::sortRecords($records, 3, false, 1); // C
        $this->assertEquals([6], array_keys($subset));

        $subset = RecordsSorter::sortRecords($records, 3, false, 2); // C
        $this->assertEquals([6, 7, 8], array_keys($subset));

        $subset = RecordsSorter::sortRecords($records, 0, true, 1);
        $this->assertEquals([1], array_keys($subset));

        $subset = RecordsSorter::sortRecords($records, 2, true, 1); // B
        $this->assertEquals([2, 4, 5], array_keys($subset));
    }
}
