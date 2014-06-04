<?php

namespace AnyContent\Client;

use CMDL\Parser;

use AnyContent\Client\Client;
use AnyContent\Client\Config;

class CMDLTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var $client Client
     */
    public $client = null;


    public function setUp()
    {
        // Connect to repository
        $client = new Client('http://anycontent.dev/1/example');
        $client->setUserInfo(new UserInfo('john.doe@example.org', 'John', 'Doe'));
        $this->client = $client;

        // todo: delete config table
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
}
