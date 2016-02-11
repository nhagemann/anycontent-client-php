<?php

namespace AnyContent\Connection;

use AnyContent\Client\Record;
use AnyContent\Client\Repository;
use AnyContent\Connection\Configuration\MySQLSchemalessConfiguration;
use KVMLogger\KVMLoggerFactory;
use KVMLogger\KVMLogger;

class MySQLSchemalessViewsTest extends \PHPUnit_Framework_TestCase
{

    /** @var  MySQLSchemalessReadWriteConnection */
    public $connection;


    public static function setUpBeforeClass()
    {
        if (defined('PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_HOST'))
        {
            $configuration = new MySQLSchemalessConfiguration();

            $configuration->initDatabase(PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_HOST, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_DBNAME, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_USERNAME, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_PASSWORD);
            $configuration->setCMDLFolder(__DIR__ . '/../../resources/ContentArchiveExample1/cmdl');
            $configuration->setRepositoryName('phpunit');
            $configuration->addContentTypes();

            $database = $configuration->getDatabase();

            $database->execute('DROP TABLE IF EXISTS _cmdl_');
            $database->execute('DROP TABLE IF EXISTS _counter_');
            $database->execute('DROP TABLE IF EXISTS phpunit$test');

            KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
        }
    }


    public function setUp()
    {
        if (defined('PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_HOST'))
        {
            $configuration = new MySQLSchemalessConfiguration();

            $configuration->initDatabase(PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_HOST, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_DBNAME, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_USERNAME, PHPUNIT_CREDENTIALS_MYSQL_SCHEMALESS_PASSWORD);
            $configuration->setCMDLFolder(__DIR__ . '/../../resources/ContentArchiveExample1/cmdl');
            $configuration->setRepositoryName('phpunit');
            $configuration->addContentTypes();

            $connection = $configuration->createReadWriteConnection();

            $this->connection = $connection;
            $repository       = new Repository('phpunit',$connection);

            KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
        }
    }


    public function testDefinition()
    {
        $connection = $this->connection;

        $connection->selectContentType('test');

        $definition = $connection->getCurrentContentTypeDefinition();

        $this->assertContains('a', $definition->getProperties('default'));
        $this->assertContains('b', $definition->getProperties('default'));
        $this->assertNotContains('c', $definition->getProperties('default'));

        $record = $connection->getRecordFactory()->createRecord($definition);
        $record->setProperty('a', 'valuea');
        $record->setProperty('b', 'valueb');
        $this->setExpectedException('CMDL\CMDLParserException');
        $record->setProperty('c', 'valuec');

    }


    public function testSaveRecordDefaultView()
    {
        $connection = $this->connection;

        $connection->selectContentType('test');

        $definition = $connection->getCurrentContentTypeDefinition();

        $record = $connection->getRecordFactory()->createRecord($definition);
        $record->setProperty('a', 'valuea');
        $record->setProperty('b', 'valueb');

        $id = $connection->saveRecord($record);

        $this->assertEquals(1, $id);

    }


    public function testSaveRecordTestView()
    {
        $connection = $this->connection;

        $connection->selectContentType('test');

        $definition = $connection->getCurrentContentTypeDefinition();

        $record = $connection->getRecordFactory()->createRecord($definition, [ ], 'test1');
        $record->setProperty('c', 'valuec');
        $record->setProperty('d', 'valued');
        $record->setId(1);

        $dataDimensions = $connection->getCurrentDataDimensions();
        $dataDimensions->setViewName('test1');

        $id = $connection->saveRecord($record, $dataDimensions);
        $this->assertEquals(1, $id);
        $this->assertEquals(2, $record->getRevision());
        $this->assertEquals('valuec', $record->getProperty('c'));
        $this->assertEquals('valued', $record->getProperty('d'));

        $this->assertArrayHasKey('c', $record->getProperties());
        $this->assertArrayHasKey('d', $record->getProperties());
        $this->assertArrayNotHasKey('a', $record->getProperties());

    }


    public function testGetRecordDifferentViews()
    {
        $connection = $this->connection;

        $connection->selectContentType('test');

        $dataDimensions = $connection->getCurrentDataDimensions();

        $record = $connection->getRecord(1, null, $dataDimensions);

        $this->assertEquals(1, $record->getId());
        $this->assertEquals(2, $record->getRevision());
        $this->assertEquals('valuea', $record->getProperty('a'));
        $this->assertEquals('valueb', $record->getProperty('b'));
        $this->assertArrayHasKey('a', $record->getProperties());
        $this->assertArrayHasKey('b', $record->getProperties());
        $this->assertArrayNotHasKey('c', $record->getProperties());

        $dataDimensions->setViewName('test1');

        $record = $connection->getRecord(1, null, $dataDimensions);

        $this->assertEquals(1, $record->getId());
        $this->assertEquals(2, $record->getRevision());
        $this->assertEquals('valuec', $record->getProperty('c'));
        $this->assertEquals('valued', $record->getProperty('d'));
        $this->assertArrayHasKey('c', $record->getProperties());
        $this->assertArrayHasKey('d', $record->getProperties());
        $this->assertArrayNotHasKey('a', $record->getProperties());

        $dataDimensions->setViewName('test2');

        $record = $connection->getRecord(1, null, $dataDimensions);

        $this->assertEquals(1, $record->getId());
        $this->assertEquals(2, $record->getRevision());
        $this->assertEquals('', $record->getProperty('e'));
        $this->assertEquals('', $record->getProperty('f'));
        $this->assertArrayNotHasKey('a', $record->getProperties());
        $this->assertArrayNotHasKey('c', $record->getProperties());

        $dataDimensions->setViewName('test1');

        $records = $connection->getAllRecords(null, $dataDimensions);

        $this->assertCount(1, $records);
        $record = array_shift($records);

        $this->assertEquals(1, $record->getId());
        $this->assertEquals(2, $record->getRevision());
        $this->assertEquals('valuec', $record->getProperty('c'));
        $this->assertEquals('valued', $record->getProperty('d'));
        $this->assertArrayHasKey('c', $record->getProperties());
        $this->assertArrayHasKey('d', $record->getProperties());
        $this->assertArrayNotHasKey('a', $record->getProperties());
    }
}