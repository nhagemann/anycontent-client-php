<?php

namespace AnyContent\Client;

use CMDL\Parser;

use AnyContent\Client\Client;
use AnyContent\Client\Record;
use AnyContent\Client\Config;

class ExceptionsTest extends \PHPUnit_Framework_TestCase
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



    }

    public function testUnknownDomain()
    {

        $this->setExpectedException('AnyContent\Client\AnyContentClientException','',AnyContentClientException::CLIENT_CONNECTION_ERROR);

        $client = new Client('http://unknown.dev/1/example', null, null, 'Basic', null);

        $client->getRepositoryInfo();

    }


    public function testUnknownRepository()
    {

        $this->setExpectedException('AnyContent\Client\AnyContentClientException','',AnyContentClientException::ANYCONTENT_UNKNOW_REPOSITORY);
        $client = new Client('http://anycontent.dev/1/unknown', null, null, 'Basic', null);

        $client->getRepositoryInfo();

    }

    public function testWrongUrlWith404()
    {
        $this->setExpectedException('AnyContent\Client\AnyContentClientException','',AnyContentClientException::ANYCONTENT_UNKNOW_REPOSITORY);
        $client = new Client('http://anycontent.dev/unknown', null, null, 'Basic', null);

        $client->getRepositoryInfo();
    }

    public function testWrongUrlWith200()
    {
        $this->setExpectedException('AnyContent\Client\AnyContentClientException','',AnyContentClientException::CLIENT_CONNECTION_ERROR);
        $client = new Client('http://www.ard.de', null, null, 'Basic', null);

        $client->getRepositoryInfo();
    }



}
