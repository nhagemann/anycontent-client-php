<?php

namespace AnyContent\Client;

use CMDL\Parser;

use AnyContent\Client\Client;
use AnyContent\Client\Record;

class RepositoryManagerTest extends \PHPUnit_Framework_TestCase
{

    public $client = null;


    public function setUp()
    {
        // Execute admin call to delete all existing data of the test content types
        $guzzle  = new \Guzzle\Http\Client('http://anycontent.dev');
        $request = $guzzle->get('/admin/delete/example/example01');
        $result  = $request->send()->getBody();

        // Connect to repository
        $client = new Client('http://anycontent.dev/1/example');
        $client->setUserInfo('john@doe.com', 'John', 'Doe');
        $this->client = $client;
    }


    public function testSaveRecords()
    {

        $cmdl = $this->client->getCMDL('example01');

        $contentTypeDefinition = Parser::parseCMDLString($cmdl);
        $contentTypeDefinition->setName('example01');

        for ($i = 1; $i <= 5; $i++)
        {
            $record = new Record($contentTypeDefinition, 'New Record ' . $i);
            $record->setProperty('article', 'Test ' . $i);
            $id = $this->client->saveRecord($record);
            $this->assertEquals($i,$id);
        }

        for ($i = 2; $i <= 5; $i++)
        {
            $record = new Record($contentTypeDefinition, 'New Record 1 - Revision ' . $i);
            $record->setID(1);
            $id = $this->client->saveRecord($record);
            $this->assertEquals(1,$id);
        }

    }
}