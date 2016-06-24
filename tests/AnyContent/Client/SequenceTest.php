<?php

namespace AnyContent\Client;

use AnyContent\Connection\Configuration\ContentArchiveConfiguration;

use AnyContent\Connection\ContentArchiveReadWriteConnection;

use KVMLogger\KVMLoggerFactory;

use Symfony\Component\Filesystem\Filesystem;

class SequenceTest extends \PHPUnit_Framework_TestCase
{

    /** @var  ContentArchiveReadWriteConnection */
    public $connection;

    /** @var  Repository */
    public $repository;


    public static function setUpBeforeClass()
    {
        $target = __DIR__ . '/../../../tmp/ExampleContentArchive';
        $source = __DIR__ . '/../../resources/ContentArchiveExample1';

        $fs = new Filesystem();

        if (file_exists($target))
        {
            $fs->remove($target);
        }

        $fs->mirror($source, $target);

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
    }


    public static function tearDownAfterClass()
    {
        $target = __DIR__ . '/../../../tmp/ExampleContentArchive';

        $fs = new Filesystem();
        $fs->remove($target);

    }


    public function setUp()
    {
        $target = __DIR__ . '/../../../tmp/ExampleContentArchive';

        $configuration = new ContentArchiveConfiguration();

        $configuration->setContentArchiveFolder($target);

        $connection = $configuration->createReadWriteConnection();

        $this->connection = $connection;

        $this->repository = new Repository('phpunit', $this->connection);

    }


    public function testAccessSequence()
    {
        $this->repository->selectContentType('profiles');

        $record = $this->repository->getRecord(5);

        $sequence = $record->getSequence('standorte');

        $this->assertInstanceOf('AnyContent\Client\Sequence', $sequence);

        $this->assertEquals(0, count($sequence));

    }


    public function testWrongProperty()
    {
        $this->repository->selectContentType('profiles');

        $record = $this->repository->getRecord(5);

        $this->setExpectedException('AnyContent\AnyContentClientException');

        $item = new SequenceItem($record->getDataTypeDefinition(), 'wrong', 'test');
    }


    public function testWrongItemType()
    {
        $this->repository->selectContentType('profiles');

        $record = $this->repository->getRecord(5);

        $this->setExpectedException('AnyContent\AnyContentClientException');

        $item = new SequenceItem($record->getDataTypeDefinition(), 'standorte', 'test');
    }


    public function testAddItemWrongProperty()
    {
        $this->repository->selectContentType('profiles');

        $record = $this->repository->getRecord(5);

        $this->setExpectedException('CMDL\CMDLParserException');

        $item = new SequenceItem($record->getDataTypeDefinition(), 'standorte', 'standort');

        $item->setProperty('xxxx', 'test');
    }


    public function testAddItem()
    {
        $this->repository->selectContentType('profiles');

        $record = $this->repository->getRecord(5);

        $sequence = $record->getSequence('standorte');

        $this->assertEquals(0, count($sequence));

        $item = new SequenceItem($record->getDataTypeDefinition(), 'standorte', 'standort');
        $item->setProperty('standort_name', 'Berlin');
        $this->assertEquals('standort', $item->getItemType());

        $sequence->addItem($item);
        $this->assertEquals(1, count($sequence));
    }


    public function testAddItems()
    {
        $this->repository->selectContentType('profiles');

        $record = $this->repository->getRecord(5);

        $sequence = $record->getSequence('standorte');

        for ($i = 1; $i <= 5; $i++)
        {
            $item = new SequenceItem($record->getDataTypeDefinition(), 'standorte', 'standort');
            $item->setProperty('standort_name', 'Location ' . $i);
            $sequence->addItem($item);
            $this->assertEquals($i, count($sequence));
        }

        $i = 0;
        foreach ($sequence as $item)
        {
            $i++;

            $this->assertEquals('Location ' . $i, $item->getProperty('standort_name'));
            $this->assertEquals('standort', $item->getItemType());
        }
    }


    public function testCompatibility()
    {
        $this->repository->selectContentType('profiles');

        $values = [ ];
        for ($i = 1; $i <= 5; $i++)
        {
            $values[] = [ 'standort' => [ 'standort_name' => 'Location ' . $i ] ];
        }

        $sequence = new Sequence($this->repository->getCurrentContentTypeDefinition(), 'standorte', $values);

        $i = 0;
        foreach ($sequence as $item)
        {
            $i++;

            $this->assertEquals('Location ' . $i, $item->getProperty('standort_name'));
            $this->assertEquals('standort', $item->getItemType());
        }

        $this->assertEquals(5, $i);
    }


    public function testRefactoringIterator()
    {
        $this->repository->selectContentType('profiles');

        $record = $this->repository->getRecord(5);

        $sequence = $record->getSequence('standorte');

        for ($i = 1; $i <= 5; $i++)
        {
            $item = new SequenceItem($record->getDataTypeDefinition(), 'standorte', 'standort');
            $item->setProperty('standort_name', 'Location ' . $i);
            $sequence->addItem($item);
            $this->assertEquals($i, count($sequence));
        }

        $i = 0;
        foreach ($sequence as $item)
        {
            $i++;

            $this->assertEquals('Location ' . $i, $item->getProperty('standort_name'));
            $this->assertEquals('standort', $item->getItemType());
            $this->assertInstanceOf('AnyContent\Client\SequenceItem', $item);
        }
    }


    public function testEmptySequence()
    {
        $this->repository->selectContentType('profiles');

        $record = $this->repository->getRecord(5);

        $sequence = $record->getSequence('standorte');

        $this->assertEquals(0, count($sequence));

        $i = 0;
        foreach ($sequence as $item)
        {
            $i++;
        }

        $this->assertEquals(false, $sequence->getProperties());
        $this->assertEquals(false, $sequence->getProperty('standort_name'));

        $this->assertEquals(0, $i);

    }


    public function testPropertiesOfSequenceVsSequenveItem()
    {
        $this->repository->selectContentType('profiles');

        $record = $this->repository->getRecord(5);

        $sequence = $record->getSequence('standorte');

        for ($i = 1; $i <= 5; $i++)
        {
            $item = new SequenceItem($record->getDataTypeDefinition(), 'standorte', 'standort');
            $item->setProperty('standort_name', 'Location ' . $i);
            $sequence->addItem($item);
            $this->assertEquals($i, count($sequence));
        }

        $i = 0;
        foreach ($sequence as $item)
        {
            $i++;
            $this->assertInstanceOf('AnyContent\Client\SequenceItem', $item);

            $this->assertEquals($i, $sequence->getPosition());

            $properties = $item->getProperties();
            $this->assertEquals('Location ' . $i, $properties['standort_name']);
            $properties = $sequence->getProperties();
            $this->assertEquals('Location ' . $i, $properties['standort_name']);
            $properties = $item->getProperties();
            $this->assertEquals('Location ' . $i, $properties['standort_name']);

        }
        $this->assertEquals(5, $i);

    }


    public function testSetSequenceProperty()
    {
        $this->repository->selectContentType('profiles');

        $record = $this->repository->getRecord(5);

        $sequence = $record->getSequence('standorte');

        for ($i = 1; $i <= 5; $i++)
        {
            $item = new SequenceItem($record->getDataTypeDefinition(), 'standorte', 'standort');
            $item->setProperty('standort_name', 'Location ' . $i);
            $sequence->addItem($item);
            $this->assertEquals($i, count($sequence));
        }

        $record->setProperty('standorte', $sequence);

        $sequence = $record->getSequence('standorte');

        $i = 0;
        foreach ($sequence as $item)
        {
            $i++;

            $this->assertEquals('Location ' . $i, $item->getProperty('standort_name'));
            $this->assertEquals('standort', $item->getItemType());
            $this->assertInstanceOf('AnyContent\Client\SequenceItem', $item);
        }
        $this->assertEquals(5, $i);
    }

}