<?php

namespace AnyContent\Client;

use CMDL\Parser;

use AnyContent\Client\Client;
use AnyContent\Client\Record;
use AnyContent\Client\Repository;

class RepositoryInfoTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var $client Client
     */
    public $client = null;


    public function setUp()
    {

        // Connect to repository
        $client = new Client('http://anycontent.dev/1/example');
        $client->setUserInfo('john@doe.com', 'John', 'Doe');
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
}