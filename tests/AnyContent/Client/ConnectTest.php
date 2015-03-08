<?php

namespace AnyContent\Client;

use CMDL\Parser;

use AnyContent\Client\Client;
use AnyContent\Client\Config;

class ConnectTest extends \PHPUnit_Framework_TestCase
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


    public function testGetUrl()
    {
        /** @var Repository $repository */
        $repository = $this->client->getRepository();

        /** @var Client $client */
        $client = $repository->getClient();

        $this->assertInstanceOf('AnyContent\Client\Client', $client);

        $this->assertEquals('http://acrs.github.dev/1/example',$client->getUrl());

        $this->assertEquals('example',$repository->getRepositoryName());

    }
}
