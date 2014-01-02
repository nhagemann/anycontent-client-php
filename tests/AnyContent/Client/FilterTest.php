<?php

namespace AnyContent\Client;

use CMDL\Parser;

use AnyContent\Client\Client;
use AnyContent\Client\Record;
use AnyContent\Client\UserInfo;

class FilterTest extends \PHPUnit_Framework_TestCase
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

        $record = new Record($contentTypeDefinition, 'New Record');
        $record->setProperty('source', 'a');
        $id = $this->client->saveRecord($record);
        $this->assertEquals(1, $id);

        $record = new Record($contentTypeDefinition, 'New Record');
        $record->setProperty('source', 'b');
        $id = $this->client->saveRecord($record);
        $this->assertEquals(2, $id);

        $record = new Record($contentTypeDefinition, 'Differing Name');
        $record->setProperty('source', 'c');
        $id = $this->client->saveRecord($record);
        $this->assertEquals(3, $id);

        $repository = $this->client->getRepository();
        $repository->selectContentType('example01');

        $filter = new ContentFilter($contentTypeDefinition);
        $filter->addCondition('name', '=', 'New Record');
        $records = $repository->getRecords('default', 'default', 'none', 'id', array(), null, 1, $filter);
        $this->assertCount(2, $records);

        $filter = new ContentFilter($contentTypeDefinition);
        $filter->addCondition('name', '=', 'New Record');
        $filter->addCondition('name', '=', 'Differing Name');
        $records = $repository->getRecords('default', 'default', 'none', 'id', array(), null, 1, $filter);
        $this->assertCount(3, $records);

        $filter = new ContentFilter($contentTypeDefinition);
        $filter->addCondition('source', '>', 'b');
        $records = $repository->getRecords('default', 'default', 'none', 'id', array(), null, 1, $filter);
        $this->assertCount(1, $records);

        $filter = new ContentFilter($contentTypeDefinition);
        $filter->addCondition('source', '>', 'a');
        $filter->nextConditionsBlock();
        $filter->addCondition('name', '=', 'Differing Name');
        $records = $repository->getRecords('default', 'default', 'none', 'id', array(), null, 1, $filter);
        $this->assertCount(1, $records);
    }

}