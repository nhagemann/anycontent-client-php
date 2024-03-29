<?php

declare(strict_types=1);

namespace Tests\AnyContent\Connection\RecordFiles;

use AnyContent\Connection\Configuration\RecordFilesConfiguration;
use AnyContent\Connection\RecordFilesReadOnlyConnection;
use PHPUnit\Framework\TestCase;

class RecordFilesReadOnlyConnectionTest extends TestCase
{
    /** @var  RecordFilesReadOnlyConnection */
    public $connection;

    public function setUp(): void
    {
        $configuration = new RecordFilesConfiguration();

        $configuration->addContentType('profiles', __DIR__ . '/../../../resources/RecordsFileExample/profiles.cmdl', __DIR__ . '/../../..//resources/RecordFilesExample/records/profiles');

        $connection = $configuration->createReadOnlyConnection();

        $this->assertInstanceOf(RecordFilesReadOnlyConnection::class, $connection);

        $this->connection = $connection;
    }

    public function testContentTypeNotSelected()
    {
        $connection = $this->connection;

        $this->expectException('AnyContent\AnyContentClientException');
        $this->assertEquals(12, $connection->countRecords());
    }

    public function testContentTypeNames()
    {
        $connection = $this->connection;

        $contentTypeNames = $connection->getContentTypeNames();

        $this->assertContains('profiles', $contentTypeNames);
    }

    public function testContentTypeDefinitions()
    {
        $connection = $this->connection;

        $contentTypes = $connection->getContentTypeDefinitions();

        $this->assertArrayHasKey('profiles', $contentTypes);

        $contentType = $contentTypes['profiles'];
        $this->assertInstanceOf('CMDL\ContentTypeDefinition', $contentType);
    }

    public function testCountRecords()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $this->assertEquals(3, $connection->countRecords());
    }

    public function testGetRecord()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $record = $connection->getRecord(5);

        $this->assertInstanceOf('AnyContent\Client\Record', $record);

        $this->assertEquals('dmc digital media center', $record->getProperty('name'));
    }

    public function testGetRecords()
    {
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $records = $connection->getAllRecords();

        $this->assertCount(3, $records);

        foreach ($records as $record) {
            $id          = $record->getID();
            $fetchRecord = $connection->getRecord($id);
            $this->assertEquals($id, $fetchRecord->getID());
        }
    }

    public function testLastModified()
    {
        $this->assertIsFloat($this->connection->getLastModifiedDate());
    }
}
