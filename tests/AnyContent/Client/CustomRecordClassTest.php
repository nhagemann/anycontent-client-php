<?php

namespace AnyContent\Client;

use CMDL\Parser;

use AnyContent\Client\Client;
use AnyContent\Client\Record;
use AnyContent\Client\UserInfo;

class CustomRecordClassTest extends \PHPUnit_Framework_TestCase
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
            $cache = new \Doctrine\Common\Cache\ApcCache();
        }

        // Connect to repository
        $client = new Client('http://acrs.github.dev/1/example', null, null, 'Basic', $cache);
        $client->setUserInfo(new UserInfo('john.doe@example.org', 'John', 'Doe'));
        $this->client = $client;
    }


    public function testSaveRecords()
    {
        // Execute admin call to delete all existing data of the test content types
        $guzzle  = new \Guzzle\Http\Client('http://acrs.github.dev');
        $request = $guzzle->delete('1/example/content/example01/records', null, null, array( 'query' => array( 'global' => 1 ) ));
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

        $this->client->registerRecordClassForContentType('example01', 'AnyContent\Client\CustomRecordClassTestRecordClass');

        $records = $this->client->getRecords($contentTypeDefinition);

        $this->assertCount(2, $records);

        $record = array_shift($records);

        $this->assertInstanceOf('AnyContent\Client\CustomRecordClassTestRecordClass', $record);

        $this->assertEquals('a', $record->getSource());

        $record->setProperty('source','b');

        $record = $this->client->getRecord($contentTypeDefinition,1);

        $this->assertEquals('a', $record->getSource());

        $record->setProperty('source','b');

        $record = $this->client->getRecord($contentTypeDefinition,1);

        $this->assertEquals('a', $record->getSource());

        $records = $this->client->getRecords($contentTypeDefinition);

        $this->assertCount(2, $records);

        $record = array_shift($records);

        $this->assertInstanceOf('AnyContent\Client\CustomRecordClassTestRecordClass', $record);

        $this->assertEquals('a', $record->getSource());
    }

}


class CustomRecordClassTestRecordClass extends Record
{

    public function getSource()
    {
        return $this->getProperty('source');
    }
}