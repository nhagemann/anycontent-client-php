<?php

namespace AnyContent\Client;

use CMDL\Parser;

use AnyContent\Client\Client;
use AnyContent\Client\Record;
use AnyContent\Client\UserInfo;

class RecordsTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var $client Client
     */
    public $client = null;


    public function setUp()
    {

        // Connect to repository
        $client = new Client('http://anycontent.dev/1/example');
        $client->setUserInfo(new UserInfo('john@doe.com', 'John', 'Doe'));
        $this->client = $client;
    }


    public function testSaveRecords()
    {
        // Execute admin call to delete all existing data of the test content types
        $guzzle  = new \Guzzle\Http\Client('http://anycontent.dev');
        $request = $guzzle->get('1/admin/delete/example/example01');
        $result  = $request->send()->getBody();

        $cmdl = $this->client->getCMDL('example01');

        $contentTypeDefinition = Parser::parseCMDLString($cmdl);
        $contentTypeDefinition->setName('example01');

        for ($i = 1; $i <= 5; $i++)
        {
            $record = new Record($contentTypeDefinition, 'New Record ' . $i);
            $record->setProperty('article', 'Test ' . $i);
            $id = $this->client->saveRecord($record);
            $this->assertEquals($i, $id);
        }

        for ($i = 2; $i <= 5; $i++)
        {
            $record = new Record($contentTypeDefinition, 'New Record 1 - Revision ' . $i);
            $record->setID(1);
            $id = $this->client->saveRecord($record);
            $this->assertEquals(1, $id);
        }

    }


    public function testGetRecord()
    {
        $cmdl = $this->client->getCMDL('example01');

        $contentTypeDefinition = Parser::parseCMDLString($cmdl);
        $contentTypeDefinition->setName('example01');

        /** @var $record Record * */
        $record = $this->client->getRecord($contentTypeDefinition, 1);
        $this->assertEquals('example01', $record->getContentType());
        $this->assertEquals(1, $record->getID());
        $this->assertEquals('New Record 1 - Revision 5', $record->getName());
        $this->assertEquals('Test 1', $record->getProperty('article'));
        $this->assertEquals(5, $record->getRevision());
    }


    public function testGetRecords()
    {
        $cmdl = $this->client->getCMDL('example01');

        $contentTypeDefinition = Parser::parseCMDLString($cmdl);
        $contentTypeDefinition->setName('example01');

        /** @var $record Record * */
        $records = $this->client->getRecords($contentTypeDefinition);

        $this->assertCount(5,$records);
    }


    public function testDeleteRecords()
    {
        $cmdl = $this->client->getCMDL('example01');

        $contentTypeDefinition = Parser::parseCMDLString($cmdl);
        $contentTypeDefinition->setName('example01');

        $this->assertFalse($this->client->deleteRecord($contentTypeDefinition,99));
        $this->assertTrue($this->client->deleteRecord($contentTypeDefinition,5));

        /** @var $record Record * */
        $records = $this->client->getRecords($contentTypeDefinition);

        $this->assertCount(4,$records);

        $record = new Record($contentTypeDefinition, 'New Record 5');
        $record->setProperty('article', 'Test 5 ');
        $record->setId(5);
        $id = $this->client->saveRecord($record);
        $this->assertEquals(5, $id);

        /** @var $record Record * */
        $records = $this->client->getRecords($contentTypeDefinition);

        $this->assertCount(5,$records);
    }

    public function testTimeShift()
    {
        return;
        $cmdl = $this->client->getCMDL('example01');

        $contentTypeDefinition = Parser::parseCMDLString($cmdl);
        $contentTypeDefinition->setName('example01');

        $timestamp = time();
        $record    = new Record($contentTypeDefinition, 'Warp 7');
        $id        = $this->client->saveRecord($record);
        $record->setID($id);

        sleep(2);
        $this->assertEquals($id, $this->client->saveRecord($record));


        $record    = $this->client->getRecord($contentTypeDefinition, $id, 'default', 'default', 'none', 1);
        $this->assertEquals($id, $record->getID());
        $this->assertEquals(1, $record->getRevision());

        return;
        /** @var $record Record * */
        $record = $this->client->getRecord($contentTypeDefinition, $id);
        $this->assertEquals($id, $record->getID());
        $this->assertEquals(2, $record->getRevision());



    }
}