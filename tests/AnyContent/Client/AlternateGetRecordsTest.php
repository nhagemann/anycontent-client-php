<?php

namespace AnyContent\Client;

use CMDL\Parser;

use AnyContent\Client\Client;
use AnyContent\Client\Record;
use AnyContent\Client\UserInfo;

class AlternateGetRecordsTest extends \PHPUnit_Framework_TestCase
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

        for ($i = 1; $i <= 5; $i++)
        {
            $record = new Record($contentTypeDefinition, 'New Record ' . $i);
            $record->setProperty('article', 'Test ' . $i);
            $id = $this->client->saveRecord($record);
            $this->assertEquals($i, $id);
        }

    }


    public function testGetRecords()
    {
        $cmdl = $this->client->getCMDL('example01');

        $contentTypeDefinition = Parser::parseCMDLString($cmdl);
        $contentTypeDefinition->setName('example01');

        /** @var $record Record * */
        $records = $this->client->getRecords($contentTypeDefinition);

        $this->assertCount(5, $records);

        $this->assertEquals(5, $this->client->countRecords($contentTypeDefinition));


        $repository = $this->client->getRepository();
        $repository->selectContentType('example01');

        $result = $repository->getRecordsAsPropertiesArray();

        $i=0;
        foreach ($result as $id => $record)
        {
            $i++;
            $this->assertEquals($i,$id);
            $this->assertEquals('Test '.$i,$record['article']);
        }

        $result = $repository->getRecordsAsIDNameList();
        $i=0;
        foreach ($result as $id => $name)
        {
            $i++;
            $this->assertEquals($i,$id);
            $this->assertEquals('New Record '.$i,$name);
        }

        $result = $repository->getRecordsAsRecordObjects(null,null,1,'AnyContent\Client\AlternateGetRecordsTestRecordClass');
        $i=0;
        foreach ($result as $id => $record)
        {
            $i++;
            $this->assertEquals($i,$id);
            $this->assertEquals('New Record '.$i,$record->getName());
            $this->assertEquals('Test '.$i,$record->getArticle());
        }
    }

}

class AlternateGetRecordsTestRecordClass extends Record
{

    public function getArticle()
    {
        return $this->getProperty('article');
    }
}