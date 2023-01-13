<?php

namespace AnyContent\Connection;

use AnyContent\Connection\Configuration\RecordsFileConfiguration;
use KVMLogger\KVMLogger;
use KVMLogger\KVMLoggerFactory;
use PHPUnit\Framework\TestCase;

class RecordsFileReadOnlyConnectionTest extends TestCase
{
    /** @var  RecordsFileReadOnlyConnection */
    public $connection;

    public function setUp(): void
    {
        $configuration = new RecordsFileConfiguration();

        $configuration->addContentType('profiles', __DIR__ . '/../../resources/RecordsFileExample/profiles.cmdl', __DIR__ . '/../../resources/RecordsFileExample/profiles.json');

        $connection = $configuration->createReadOnlyConnection();

        $this->connection = $connection;

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
    }

    public function testContentTypeNotSelected()
    {
        KVMLogger::instance()->debug(__METHOD__);

        $connection = $this->connection;

        $this->expectException('AnyContent\AnyContentClientException');
        $this->assertEquals(12, $connection->countRecords());
    }

    public function testContentTypeNames()
    {
        KVMLogger::instance()->debug(__METHOD__);

        $connection = $this->connection;

        $contentTypeNames = $connection->getContentTypeNames();

        $this->assertContains('profiles', $contentTypeNames);
    }

    public function testContentTypeDefinitions()
    {
        KVMLogger::instance()->debug(__METHOD__);

        $connection = $this->connection;

        $contentTypes = $connection->getContentTypeDefinitions();

        $this->assertArrayHasKey('profiles', $contentTypes);

        $contentType = $contentTypes['profiles'];
        $this->assertInstanceOf('CMDL\ContentTypeDefinition', $contentType);
    }

    public function testCountRecords()
    {
        KVMLogger::instance()->debug(__METHOD__);

        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $this->assertEquals(608, $connection->countRecords());
    }

    public function testGetRecord()
    {
        KVMLogger::instance()->debug(__METHOD__);

        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $record = $connection->getRecord(1);

        $this->assertInstanceOf('AnyContent\Client\Record', $record);

        $this->assertEquals('UDG United Digital Group', $record->getProperty('name'));
    }

    public function testGetRecords()
    {
        KVMLogger::instance()->debug(__METHOD__);

        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $records = $connection->getAllRecords();

        $this->assertCount(608, $records);

        foreach ($records as $record) {
            $id          = $record->getId();
            $fetchRecord = $connection->getRecord($id);
            $this->assertEquals($id, $fetchRecord->getId());
        }
    }

    public function testLastModified()
    {
        $this->assertIsInt($this->connection->getLastModifiedDate());
    }
}
