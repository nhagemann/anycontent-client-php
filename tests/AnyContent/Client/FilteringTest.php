<?php

namespace AnyContent\Client;

use AnyContent\Connection\Configuration\ContentArchiveConfiguration;
use AnyContent\Filter\ANDFilter;
use AnyContent\Filter\ORFilter;
use AnyContent\Filter\PropertyFilter;
use KVMLogger\KVMLoggerFactory;
use Symfony\Component\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

class FilteringTest extends TestCase
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


    public static function tearDownAfterClass(): void
    {
        $target = __DIR__ . '/../../../tmp/ExampleContentArchive';

        $fs = new Filesystem();
        $fs->remove($target);
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


    public function testSaveRecords()
    {
        $this->repository->selectContentType('example01');

        $record = $this->repository->createRecord('New Record');
        $record->setProperty('source', 'a');
        $id = $this->repository->saveRecord($record);
        $this->assertEquals(1, $id);


        $record = $this->repository->createRecord('New Record');
        $record->setProperty('source', 'b');
        $id = $this->repository->saveRecord($record);
        $this->assertEquals(2, $id);


        $record = $this->repository->createRecord('Differing Name');
        $record->setProperty('source', 'c');
        $id = $this->repository->saveRecord($record);
        $this->assertEquals(3, $id);


        $records = $this->repository->getRecords('name = New Record');
        $this->assertCount(2, $records);

        $filter1 = new PropertyFilter('name = New Record');
        $filter2 = new PropertyFilter('name = Differing Name');
        $orFilter = new ORFilter([$filter1, $filter2]);

        $records = $this->repository->getRecords($orFilter);
        $this->assertCount(3, $records);


        $records = $this->repository->getRecords('source > b');
        $this->assertCount(1, $records);


        $filter1 = new PropertyFilter('source > a');
        $filter2 = new PropertyFilter('name = Differing Name');
        $andFilter = new ANDFilter([$filter1, $filter2]);
        $records = $this->repository->getRecords($andFilter);
        $this->assertCount(1, $records);
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
