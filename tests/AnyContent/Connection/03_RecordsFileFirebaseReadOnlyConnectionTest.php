<?php

namespace AnyContent\Connection;

use AnyContent\Connection\Configuration\RecordsFileFirebaseConfiguration;
use AnyContent\Connection\RecordsFileFirebaseReadOnlyConnection;
use KVMLogger\KVMLoggerFactory;
use KVMLogger\KVMLogger;

class RecordsFileFirebaseReadOnlyConnectionTest extends \PHPUnit_Framework_TestCase
{

    /** @var  RecordsFileFirebaseReadOnlyConnection */
    public $connection;


    public function setUp()
    {
        $configuration = new RecordsFileFirebaseConfiguration();

        if (defined('PHPUNIT_CREDENTIALS_FIREBASE_URL'))
        {
            $configuration->setFirebase(PHPUNIT_CREDENTIALS_FIREBASE_URL, PHPUNIT_CREDENTIALS_FIREBASE_TOKEN, 'phpunit');
            $configuration->addContentType('profiles', 'profiles/cmdl', 'profiles/data');

            $connection = $configuration->createReadOnlyConnection();

            $this->connection = $connection;
        }

        KVMLoggerFactory::createWithKLogger(__DIR__.'/../../../tmp');

    }


    public function testContentTypeNotSelected()
    {

        $connection = $this->connection;

        if (!$connection)
        {
            $this->markTestSkipped('Firebase credentials missing.');
        }

        $this->setExpectedException('AnyContent\AnyContentClientException');
        $this->assertEquals(12, $connection->countRecords());
    }


    public function testContentTypeNames()
    {

        $connection = $this->connection;

        if (!$connection)
        {
            $this->markTestSkipped('Firebase credentials missing.');
        }

        $contentTypeNames = $connection->getContentTypeNames();

        $this->assertContains('profiles', $contentTypeNames);
    }


    public function testContentTypeDefinitions()
    {
        $connection = $this->connection;

        if (!$connection)
        {
            $this->markTestSkipped('Firebase credentials missing.');
        }

        $contentTypes = $connection->getContentTypeDefinitions();

        $this->assertArrayHasKey('profiles', $contentTypes);

        $contentType = $contentTypes['profiles'];
        $this->assertInstanceOf('CMDL\ContentTypeDefinition', $contentType);
    }


    public function testCountRecords()
    {
        $connection = $this->connection;

        if (!$connection)
        {
            $this->markTestSkipped('Firebase credentials missing.');
        }

        $connection->selectContentType('profiles');

        $this->assertEquals(608, $connection->countRecords());

    }


    public function testGetRecord()
    {
        $connection = $this->connection;

        if (!$connection)
        {
            $this->markTestSkipped('Firebase credentials missing.');
        }

        $connection->selectContentType('profiles');

        $record = $connection->getRecord(1);

        $this->assertInstanceOf('AnyContent\Client\Record', $record);

        $this->assertEquals('UDG United Digital Group', $record->getProperty('name'));

    }


    public function testGetRecords()
    {
        $connection = $this->connection;

        if (!$connection)
        {
            $this->markTestSkipped('Firebase credentials missing.');
        }

        $connection->selectContentType('profiles');

        $records = $connection->getAllRecords();

        $this->assertCount(607, $records);

        foreach ($records as $record)
        {
            $id          = $record->getID();
            $fetchRecord = $connection->getRecord($id);
            $this->assertEquals($id, $fetchRecord->getID());
        }
    }

    public function testLastModified()
    {
        $connection = $this->connection;

        if (!$connection)
        {
            $this->markTestSkipped('Firebase credentials missing.');
        }

        $this->assertInternalType('int',$connection->getLastModifiedDate());
    }

}