<?php

namespace AnyContent\Client;

use CMDL\Parser;

use AnyContent\Client\Client;
use AnyContent\Client\Record;
use AnyContent\Client\Repository;
use AnyContent\Client\UserInfo;

class RepositoryInfoTest extends \PHPUnit_Framework_TestCase
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


    public function testHasContentTypes()
    {
        /** @var Repository $repository */
        $repository = $this->client->getRepository();

        $this->assertTrue($repository->hasContentType('example01'));
        $this->assertFalse($repository->hasContentType('example99'));
    }


    public function testGetContentTypes()
    {
        /** @var Repository $repository */
        $repository   = $this->client->getRepository();
        $contentTypes = $repository->getContentTypes();
        foreach ($contentTypes as $contentTypeName => $contentTypeTitle)
        {
            $this->assertTrue($repository->hasContentType($contentTypeName));
            $this->assertInstanceOf('CMDL\ContentTypeDefinition', $repository->getContentTypeDefinition($contentTypeName));
            $this->assertEquals($repository->getContentTypeDefinition($contentTypeName)->getName(), $contentTypeName);
        }

    }


    public function testGetConfigTypes()
    {
        /** @var Repository $repository */
        $repository   = $this->client->getRepository();
        $configTypes = $repository->getConfigTypes();

        foreach ($configTypes as $configTypeName => $configTypeTitle)
        {
            $this->assertTrue($repository->hasConfigType($configTypeName));
            $this->assertInstanceOf('CMDL\ConfigTypeDefinition', $repository->getConfigTypeDefinition($configTypeName));
            $this->assertEquals($repository->getConfigTypeDefinition($configTypeName)->getName(), $configTypeName);
        }

        $this->assertFalse($repository->hasConfigType('config3'));



    }


    public function testContentCounts()
    {
        /*
        $info = $this->client->getRepositoryInfo();
        $this->assertEquals(5, $info['content']['example01']['count']);

        $info = $this->client->getRepositoryInfo('live');
        $this->assertEquals(0, $info['content']['example01']['count']);

        $info = $this->client->getRepositoryInfo('default', 'none', 600);
        $this->assertEquals(0, $info['content']['example01']['count']);

        $cmdl = $this->client->getCMDL('example01');

        $contentTypeDefinition = Parser::parseCMDLString($cmdl);
        $contentTypeDefinition->setName('example01');

        $record = new Record($contentTypeDefinition, 'New Record 6');
        $record->setProperty('article', 'Test 6');
        $id = $this->client->saveRecord($record);


        $info = $this->client->getRepositoryInfo();
        $this->assertEquals(6, $info['content']['example01']['count']);

        /** @var Repository $repository
        $repository   = $this->client->getRepository();
        $repository->selectContentType('example01');
        $this->assertEquals(6, $repository->getRecordsCount());
        */
    }
}