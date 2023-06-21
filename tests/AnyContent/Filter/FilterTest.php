<?php

declare(strict_types=1);

namespace Tests\AnyContent\Filter;

use AnyContent\Client\Repository;
use AnyContent\Connection\Configuration\ContentArchiveConfiguration;
use AnyContent\Connection\ContentArchiveReadWriteConnection;
use AnyContent\Filter\ANDFilter;
use AnyContent\Filter\ORFilter;
use AnyContent\Filter\PropertyFilter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class FilterTest extends TestCase
{
    public ContentArchiveReadWriteConnection $connection;

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
    }

    public function setUp(): void
    {
        $target = __DIR__ . '/../../../tmp/ExampleContentArchive';

        $configuration = new ContentArchiveConfiguration();

        $configuration->setContentArchiveFolder($target);

        $connection = $configuration->createReadWriteConnection();

        $this->connection = $connection;
        $this->repository = new Repository('phpunit', $connection);
    }

    public function testSimpleFilter()
    {
        $this->repository->selectContentType('example01');

        $record1 = $this->repository->createRecord('New Record');

        $record2 = $this->repository->createRecord('Another Record');

        $filter = new PropertyFilter('name = New Record');
        $this->assertTrue($filter->match($record1));

        $filter = new PropertyFilter('name = "New Record"');
        $this->assertTrue($filter->match($record1));

        $filter = new PropertyFilter("name = 'New Record'");
        $this->assertTrue($filter->match($record1));

        $filter = new PropertyFilter('name = New Record');
        $this->assertFalse($filter->match($record2));
    }

    public function testANDFilter()
    {
        $this->repository->selectContentType('example01');

        $record1 = $this->repository->createRecord('New Record');
        $record1->setProperty('source', 'a');

        $record2 = $this->repository->createRecord('Another Record');
        $record2->setProperty('source', 'b');

        $filter1 = new PropertyFilter('name = New Record');
        $filter2 = new PropertyFilter('source = a');
        $filter3 = new PropertyFilter('source = b');

        $andFilter = new ANDFilter([$filter1, $filter2]);

        $this->assertTrue($andFilter->match($record1));
        $this->assertFalse($andFilter->match($record2));

        $andFilter = new ANDFilter([$filter1, $filter3]);

        $this->assertFalse($andFilter->match($record1));
        $this->assertFalse($andFilter->match($record2));
    }

    public function testORFilter()
    {
        $this->repository->selectContentType('example01');

        $record1 = $this->repository->createRecord('New Record');
        $record1->setProperty('source', 'a');

        $record2 = $this->repository->createRecord('Another Record');
        $record2->setProperty('source', 'b');

        $filter1 = new PropertyFilter('name = New Record');
        $filter2 = new PropertyFilter('source = a');
        $filter3 = new PropertyFilter('source = b');

        $orFilter = new ORFilter([$filter1, $filter2]);

        $this->assertTrue($orFilter->match($record1));
        $this->assertFalse($orFilter->match($record2));

        $orFilter = new ORFilter([$filter1, $filter3]);

        $this->assertTrue($orFilter->match($record1));
        $this->assertTrue($orFilter->match($record2));
    }

    public function testCombinedFilter()
    {
        $this->repository->selectContentType('example01');

        $record1 = $this->repository->createRecord('New Record');
        $record1->setProperty('source', 'a');

        $record2 = $this->repository->createRecord('Another Record');
        $record2->setProperty('source', 'b');

        $filter1 = new PropertyFilter('name = New Record');
        $filter2 = new PropertyFilter('source = a');
        $filter3 = new PropertyFilter('source = b');

        $andFilter = new ANDFilter([$filter1, $filter2]);

        $orFilter = new ORFilter([$andFilter, $filter3]);

        $this->assertTrue($orFilter->match($record1));
        $this->assertTrue($orFilter->match($record2));
    }

    public function testDifferentFilters()
    {
        $this->repository->selectContentType('example01');

        $record1 = $this->repository->createRecord('New Record');
        $record1->setProperty('source', 'a');

        $record2 = $this->repository->createRecord('Another Record');
        $record2->setProperty('source', 'b');

        $filter = new PropertyFilter('source > a');
        $this->assertFalse($filter->match($record1));
        $this->assertTrue($filter->match($record2));

        $filter = new PropertyFilter('source < b');
        $this->assertTrue($filter->match($record1));
        $this->assertFalse($filter->match($record2));

        $filter = new PropertyFilter('source >= a');
        $this->assertTrue($filter->match($record1));
        $this->assertTrue($filter->match($record2));

        $filter = new PropertyFilter('source <= b');
        $this->assertTrue($filter->match($record1));
        $this->assertTrue($filter->match($record2));

        $filter = new PropertyFilter('source != b');
        $this->assertTrue($filter->match($record1));
        $this->assertFalse($filter->match($record2));
    }

    public function testLikeFilter()
    {
        $this->repository->selectContentType('example01');

        $record1 = $this->repository->createRecord('New Record');
        $record1->setProperty('source', 'hans dieter');

        $record2 = $this->repository->createRecord('Another Record');
        $record2->setProperty('source', 'peter');

        $record3 = $this->repository->createRecord('Another Record');
        $record3->setProperty('source', 'Schmalhans');

        $filter = new PropertyFilter('source *= hans');
        $this->assertTrue($filter->match($record1));
        $this->assertFalse($filter->match($record2));
        $this->assertTrue($filter->match($record3));
    }

    public function testNumericalComparison()
    {
        $this->repository->selectContentType('example01');

        $record1 = $this->repository->createRecord('New Record');
        $record1->setProperty('source', '110');

        $record2 = $this->repository->createRecord('Another Record');
        $record2->setProperty('source', '10');

        $record3 = $this->repository->createRecord('Another Record');
        $record3->setProperty('source', '12');

        $filter = new PropertyFilter('source < 111');

        $this->assertTrue($filter->match($record1));
        $this->assertTrue($filter->match($record2));
        $this->assertTrue($filter->match($record3));
    }

    public function testFilterAsString()
    {
        $filter = new PropertyFilter('source < 111');
        $this->assertEquals('source < 111', (string)$filter);

        $filter1 = new PropertyFilter('name = New Record');
        $filter2 = new PropertyFilter('source = a');
        $filter3 = new PropertyFilter('source = b');

        $andFilter = new ANDFilter([$filter1, $filter2]);

        $orFilter = new ORFilter([$andFilter, $filter3]);

        $this->assertEquals('(name = New Record AND source = a)', (string)$andFilter);
        $this->assertEquals('((name = New Record AND source = a) OR source = b)', (string)$orFilter);
    }

//
//    public function testSimpleFilter()
//    {
//
//        $repository = $this->client->getRepository();
//        $repository->selectContentType('example01');
//
//        $filter  = 'name = New Record';
//        $records = $repository->getRecords('default', 'default', 'default', 'id', array(), null, 1, $filter);
//        $this->assertCount(2, $records);
//
//        $filter  = 'name = New Record, name = Differing Name';
//        $records = $repository->getRecords('default', 'default', 'default', 'id', array(), null, 1, $filter);
//        $this->assertCount(3, $records);
//
//        $filter  = 'source > b';
//        $records = $repository->getRecords('default', 'default', 'default', 'id', array(), null, 1, $filter);
//        $this->assertCount(1, $records);
//
//        $filter  = 'source > a + name = Differing Name';
//        $records = $repository->getRecords('default', 'default', 'default', 'id', array(), null, 1, $filter);
//        $this->assertCount(1, $records);
//    }
//
//
//    public function testFilterToStringConversion()
//    {
//        $cmdl = $this->client->getCMDL('example01');
//
//        $contentTypeDefinition = Parser::parseCMDLString($cmdl);
//
//        $filter = new ContentFilter($contentTypeDefinition);
//        $filter->addCondition('name', '=', 'New Record');
//
//        $this->assertEquals('name = New Record', $filter->getSimpleQuery());
//
//        $filter = new ContentFilter($contentTypeDefinition);
//        $filter->addCondition('name', '=', 'New Record');
//        $filter->addCondition('name', '=', 'Differing Name');
//
//        $this->assertEquals('name = New Record , name = Differing Name', $filter->getSimpleQuery());
//
//        $filter = new ContentFilter($contentTypeDefinition);
//        $filter->addCondition('source', '>', 'b');
//
//        $this->assertEquals('source > b', $filter->getSimpleQuery());
//
//        $filter = new ContentFilter($contentTypeDefinition);
//        $filter->addCondition('source', '>', 'a');
//        $filter->nextConditionsBlock();
//        $filter->addCondition('name', '=', 'Differing Name');
//
//        $this->assertEquals('source > a + name = Differing Name', $filter->getSimpleQuery());
//    }
}
