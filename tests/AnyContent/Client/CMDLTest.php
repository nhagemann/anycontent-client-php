<?php

namespace AnyContent\Client;

use CMDL\Parser;

use AnyContent\Client\Client;
use AnyContent\Client\Record;
use AnyContent\Client\Config;

class CMDLTest extends \PHPUnit_Framework_TestCase
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


    public function testCreateAndDeleteContentTypes()
    {
        $repository = $this->client->getRepository();
        $this->client->deleteContentType('example101');
        $this->assertFalse($repository->getContentTypeDefinition('example101'));
        $this->client->saveContentTypeCMDL('example101', 'test');
        $this->assertTrue((boolean)$repository->getContentTypeDefinition('example101'));
        $this->client->deleteContentType('example101');

    }


    public function testCMDLChanges()
    {
        $this->client->saveContentTypeCMDL('example101', 'property_a');

        $cmdl = $this->client->getCMDL('example101');

        $this->assertEquals('property_a',$cmdl);

        $contentTypeDefinition = Parser::parseCMDLString($cmdl);
        $contentTypeDefinition->setName('example101');

        $record = new Record($contentTypeDefinition, 'New Record');
        $record->setProperty('property_a', 'test');
        $id = $this->client->saveRecord($record);

        /** @var $record Record * */
        $record = $this->client->getRecord($contentTypeDefinition, $id);

        $this->assertEquals('test',$record->getProperty('property_a'));


        $this->client->saveContentTypeCMDL('example101', 'property_b');
        $cmdl = $this->client->getCMDL('example101');

        $this->assertEquals('property_b',$cmdl);

        $contentTypeDefinition = Parser::parseCMDLString($cmdl);
        $contentTypeDefinition->setName('example101');

        $record = new Record($contentTypeDefinition, 'New Record');
        $record->setID($id);
        $record->setProperty('property_b', 'next');
        $id = $this->client->saveRecord($record);

        /** @var $record Record * */
        $record = $this->client->getRecord($contentTypeDefinition, $id);

        $this->assertEquals('next',$record->getProperty('property_b'));
        $this->assertEquals(null,$record->getProperty('property_a'));

        $this->client->saveContentTypeCMDL('example101', 'property_a');


        $contentTypeDefinition = Parser::parseCMDLString('property_a');
        $contentTypeDefinition->setName('example101');

        /** @var $record Record * */
        $record = $this->client->getRecord($contentTypeDefinition, $id);


        $this->assertEquals('test',$record->getProperty('property_a'));

        $this->client->deleteContentType('example101');
    }

    public function testCreateAndDeleteConfigTypes()
    {
        $repository = $this->client->getRepository();
        $this->client->deleteConfigType('config101');
        $this->assertFalse($repository->getConfigTypeDefinition('config101'));
        $this->client->saveConfigTypeCMDL('config101', 'test');
        $this->assertTrue((boolean)$repository->getConfigTypeDefinition('config101'));
        $this->client->deleteConfigType('config101');

    }
}
