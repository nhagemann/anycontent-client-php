<?php

namespace AnyContent\Connection;
use AnyContent\Connection\Configuration\RecordsFileGitConfiguration;
use AnyContent\Connection\RecordsFileGitReadWriteConnection;
use KVMLogger\KVMLoggerFactory;
use KVMLogger\KVMLogger;

class RecordsFileGitReadWriteConnectionTest extends \PHPUnit_Framework_TestCase
{

    /** @var  RecordsFileGitReadWriteConnection */
    public $connection;

    static $randomString;


    public static function setUpBeforeClass()
    {
        self::$randomString = md5(time());
    }


    public function setUp()
    {
        //$configuration = new RecordsFileGitConfiguration();
        //
        //$configuration->setDirectory(__DIR__ . '/../../../tmp/git')->setPrivateKey('/var/www/github/gitrepos/id_rsa');
        //$configuration->setRemoteUrl('git@bitbucket.org:nhagemann/anycontent-git-repository.git');
        //$configuration->setUniqueConnection(300);
        //
        //$configuration->addContentType('profiles', 'profiles.cmdl', 'profiles.json');
        //
        //$this->connection = $configuration->createReadWriteConnection();
        //
        //KVMLoggerFactory::createWithKLogger(__DIR__.'/../../../tmp');

    }


    public function testContentTypeNotSelected()
    {
        $this->markTestSkipped('Git connection tests skipped.');
        $connection = $this->connection;

        $this->setExpectedException('AnyContent\AnyContentClientException');
        $this->assertEquals(12, $connection->countRecords());
    }


    public function testContentTypeNames()
    {
        $this->markTestSkipped('Git connection tests skipped.');
        $connection = $this->connection;

        $contentTypeNames = $connection->getContentTypeNames();

        $this->assertContains('profiles', $contentTypeNames);
    }


    public function testContentTypeDefinitions()
    {
        $this->markTestSkipped('Git connection tests skipped.');
        $connection = $this->connection;

        $contentTypes = $connection->getContentTypeDefinitions();

        $this->assertArrayHasKey('profiles', $contentTypes);

        $contentType = $contentTypes['profiles'];
        $this->assertInstanceOf('CMDL\ContentTypeDefinition', $contentType);
    }


    public function testCountRecords()
    {
        $this->markTestSkipped('Git connection tests skipped.');
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $this->assertEquals(608, $connection->countRecords());

    }


    public function testGetRecord()
    {
        $this->markTestSkipped('Git connection tests skipped.');
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $record = $connection->getRecord(1);

        $this->assertInstanceOf('AnyContent\Client\Record', $record);

        $this->assertEquals('UDG United Digital Group', $record->getProperty('name'));

    }


    public function testGetRecords()
    {
        $this->markTestSkipped('Git connection tests skipped.');
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $records = $connection->getAllRecords();

        $this->assertCount(608, $records);

        foreach ($records as $record)
        {
            $id          = $record->getID();
            $fetchRecord = $connection->getRecord($id);
            $this->assertEquals($id, $fetchRecord->getID());
        }
    }


    public function testChangeRecord()
    {
        $this->markTestSkipped('Git connection tests skipped.');
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $record = $connection->getRecord(5);

        $this->assertInstanceOf('AnyContent\Client\Record', $record);

        $record->setProperty('name', self::$randomString);

        $connection->saveRecord($record);

    }


    public function testChangedRecord()
    {
        $this->markTestSkipped('Git connection tests skipped.');
        $connection = $this->connection;

        $connection->selectContentType('profiles');

        $record = $connection->getRecord(5);

        $this->assertInstanceOf('AnyContent\Client\Record', $record);

        $this->assertEquals(self::$randomString, $record->getName());

    }

    public function testLastModified()
    {
        $this->markTestSkipped('Git connection tests skipped.');
        $this->assertInternalType('int',$this->connection->getLastModifiedDate());
    }
}