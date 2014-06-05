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
        global $testWithCaching;

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
}
