<?php

namespace AnyContent\Client;

use CMDL\Parser;

use AnyContent\Client\Client;
use AnyContent\Client\Record;
use AnyContent\Client\UserInfo;

class LanguagesTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var $client Client
     */
    public $client = null;


    public function setUp()
    {

        // Connect to repository
        $client = new Client('http://anycontent.dev/1/example');
        $client->setUserInfo(new UserInfo('john.doe@example.lorg', 'John', 'Doe'));
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
            $id = $this->client->saveRecord($record, 'default', 'default', 'en');
            $this->assertEquals($i, $id);
        }

        for ($i = 1; $i <= 5; $i++)
        {
            $record = new Record($contentTypeDefinition, 'New Record ' . (5 + $i));
            $record->setProperty('article', 'Test ' . (5 + $i));
            $id = $this->client->saveRecord($record, 'default', 'default', 'es');
            $this->assertEquals(5 + $i, $id);
        }

        for ($i = 1; $i <= 5; $i++)
        {
            $record = new Record($contentTypeDefinition, 'New Record ' . (10 + $i));
            $record->setProperty('article', 'Test ' . (10 + $i));
            $id = $this->client->saveRecord($record, 'live', 'default', 'es');
            $this->assertEquals(10 + $i, $id);
        }

        $cmdl = $this->client->getCMDL('example01');

        $contentTypeDefinition = Parser::parseCMDLString($cmdl);
        $contentTypeDefinition->setName('example01');

        $records = $this->client->getRecords($contentTypeDefinition);
        $this->assertCount(0, $records);

        $records = $this->client->getRecords($contentTypeDefinition,'default','default','es');
        $this->assertCount(5, $records);
        $record = array_shift($records);
        $this->assertEquals('es',$record->getLanguage());
        $this->assertEquals('default',$record->getWorkspace());

        $records = $this->client->getRecords($contentTypeDefinition,'live','default','es');
        $this->assertCount(5, $records);
        $record = array_shift($records);
        $this->assertEquals('es',$record->getLanguage());
        $this->assertEquals('live',$record->getWorkspace());
    }

}