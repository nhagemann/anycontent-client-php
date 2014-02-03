<?php

namespace AnyContent\Client;

use CMDL\Parser;

use AnyContent\Client\Client;
use AnyContent\Client\Config;

class ConfigTest extends \PHPUnit_Framework_TestCase
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


    public function testGetConfig()
    {
        /** @var Repository $repository */
        $repository = $this->client->getRepository();

        $config = $repository->getConfig('config1');

        $this->assertEquals('Madrid',$config->getProperty('city'));
        $this->assertEquals('Spain',$config->getProperty('country'));

        $config = $repository->getConfig('config2');

        $this->assertEquals('',$config->getProperty('value1'));
        $this->assertEquals('',$config->getProperty('value2'));
        $this->assertEquals('',$config->getProperty('value3'));
        $this->assertEquals('',$config->getProperty('value4'));


        $config->setProperty('value1','a');
        $repository->saveConfig($config);
        $config->setProperty('value1','');
        $repository->saveConfig($config);
    }
}
