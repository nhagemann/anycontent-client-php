<?php

namespace AnyContent\Client;

use CMDL\Parser;

use AnyContent\Client\Client;
use AnyContent\Client\Record;
use AnyContent\Client\UserInfo;

class SortRecordsTest extends \PHPUnit_Framework_TestCase
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


    public function testSimpleSort()
    {

        // Execute admin call to delete all existing data of the test content types
        $guzzle  = new \Guzzle\Http\Client('http://anycontent.dev');
        $request = $guzzle->get('1/admin/delete/example/example01');
        $result  = $request->send()->getBody();

        $cmdl = $this->client->getCMDL('example01');

        $contentTypeDefinition = Parser::parseCMDLString($cmdl);
        $contentTypeDefinition->setName('example01');

        for ($i = 1; $i <= 10; $i++)
        {
            $record = new Record($contentTypeDefinition, 'New Record ' . $i);
            $record->setProperty('article', 'Test ' . $i);
            $id = $this->client->saveRecord($record);
            $this->assertEquals($i, $id);
        }

        $list   = array();
        $list[] = array( 'id' => 1, 'parent_id' => 0 );
        $list[] = array( 'id' => 2, 'parent_id' => 1 );
        $list[] = array( 'id' => 3, 'parent_id' => 1 );
        $list[] = array( 'id' => 4, 'parent_id' => 0 );
        $list[] = array( 'id' => 5, 'parent_id' => 4 );
        $list[] = array( 'id' => 6, 'parent_id' => 4 );
        $list[] = array( 'id' => 7, 'parent_id' => 5 );
        $list[] = array( 'id' => 8, 'parent_id' => 5 );
        $list[] = array( 'id' => 9, 'parent_id' => 6 );

        $result = $this->client->sortRecords($contentTypeDefinition, $list);

        $this->assertTrue($result);

        // Now do some of the sorting queries of the original SortRecordsTest

        $records = $this->client->getRecords($contentTypeDefinition);
        $this->assertCount(10, $records);

        $records = $this->client->getRecords($contentTypeDefinition, 'default', 'default', 'default', 'id', array(), null, 1, null, '1');
        $this->assertCount(3, $records);

        $records = $this->client->getRecords($contentTypeDefinition, 'default', 'default', 'default', 'id', array(), null, 1, null, '4');
        $this->assertCount(6, $records);

        $records = $this->client->getRecords($contentTypeDefinition, 'default', 'default', 'default', 'id', array(), null, 1, null, '4,0');
        $this->assertCount(5, $records);

        $records = $this->client->getRecords($contentTypeDefinition, 'default', 'default', 'default', 'id', array(), null, 1, null, '4,0,1');
        $this->assertCount(2, $records);

        $records = $this->client->getRecords($contentTypeDefinition, 'default', 'default', 'default', 'id', array(), null, 1, null, '5,0');
        $this->assertCount(2, $records);

    }


    public function testSubsetMethod()
    {
        $cmdl = $this->client->getCMDL('example01');

        $contentTypeDefinition = Parser::parseCMDLString($cmdl);
        $contentTypeDefinition->setName('example01');

        $records = $this->client->getSubset($contentTypeDefinition, 0);
        $this->assertCount(9, $records);

        $records = $this->client->getSubset($contentTypeDefinition, 1);
        $this->assertCount(3, $records);

        $records = $this->client->getSubset($contentTypeDefinition, 4);
        $this->assertCount(6, $records);

        $records = $this->client->getSubset($contentTypeDefinition, 4, false);
        $this->assertCount(5, $records);

        $records = $this->client->getSubset($contentTypeDefinition, 4, false, 1);
        $this->assertCount(2, $records);

        $records = $this->client->getSubset($contentTypeDefinition, 5, false);
        $this->assertCount(2, $records);
    }
}
