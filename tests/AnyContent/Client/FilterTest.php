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
        global $testWithCaching;

        $cache = null;
        if ($testWithCaching)
        {
            $memcached = new \Memcached();

            $memcached->addServer('localhost', 11211);
            $cache = new \Doctrine\Common\Cache\MemcachedCache();
            $cache->setMemcached($memcached);

        }

        // Connect to repository
        $client = new Client('http://anycontent.dev/1/example', null, null, 'Basic', $cache);
        $client->setUserInfo(new UserInfo('john.doe@example.org', 'John', 'Doe'));
        $this->client = $client;
    }


    public function testSaveRecords()
    {
        // Execute admin call to delete all existing data of the test content types
        $guzzle  = new \Guzzle\Http\Client('http://anycontent.dev');
        $request = $guzzle->delete('1/example/content/example01/records',null,null,array('global'=>1));
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


        $t1 = $this->client->getLastChangeTimestamp($contentTypeDefinition);

        $record = new Record($contentTypeDefinition, 'Differing Name');
        $record->setProperty('source', 'c');
        $id = $this->client->saveRecord($record);
        $this->assertEquals(3, $id);

        $t2 = $this->client->getLastChangeTimestamp($contentTypeDefinition);

        $this->assertNotEquals($t1,$t2);

        $repository = $this->client->getRepository();
        $repository->selectContentType('example01');

        $filter = new ContentFilter($contentTypeDefinition);
        $filter->addCondition('name', '=', 'New Record');
        $records = $repository->getRecords('default', 'default', 'default', 'id', array(), null, 1, $filter);
        $this->assertCount(2, $records);

        $filter = new ContentFilter($contentTypeDefinition);
        $filter->addCondition('name', '=', 'New Record');
        $filter->addCondition('name', '=', 'Differing Name');
        $records = $repository->getRecords('default', 'default', 'default', 'id', array(), null, 1, $filter);
        $this->assertCount(3, $records);

        $filter = new ContentFilter($contentTypeDefinition);
        $filter->addCondition('source', '>', 'b');
        $records = $repository->getRecords('default', 'default', 'default', 'id', array(), null, 1, $filter);
        $this->assertCount(1, $records);

        $filter = new ContentFilter($contentTypeDefinition);
        $filter->addCondition('source', '>', 'a');
        $filter->nextConditionsBlock();
        $filter->addCondition('name', '=', 'Differing Name');
        $records = $repository->getRecords('default', 'default', 'default', 'id', array(), null, 1, $filter);
        $this->assertCount(1, $records);
    }




}