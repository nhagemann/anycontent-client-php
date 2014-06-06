<?php

namespace AnyContent\Client;

use CMDL\Parser;

use AnyContent\Client\Client;
use AnyContent\Client\UserInfo;
use AnyContent\Client\Record;

class UserInfoTest extends \PHPUnit_Framework_TestCase
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


    public function testCreationInfo()
    {
        $cmdl = $this->client->getCMDL('example01');

        $contentTypeDefinition = Parser::parseCMDLString($cmdl);
        $contentTypeDefinition->setName('example01');

        $record = $this->client->getRecord($contentTypeDefinition, 1);

        $this->assertInstanceOf('AnyContent\Client\UserInfo', $record->getCreationUserInfo());
        $this->assertInstanceOf('AnyContent\Client\UserInfo', $record->getLastChangeUserInfo());

        /** @var UserInfo $userinfo */
        $userinfo = $record->getCreationUserInfo();

        $this->assertEquals('john.doe@example.org', $userinfo->getUsername());
        $this->assertEquals('John', $userinfo->getFirstname());
        $this->assertEquals('Doe', $userinfo->getLastname());
        $this->assertTrue($userinfo->userNameIsAnEmailAddress());
    }
}