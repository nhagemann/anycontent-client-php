<?php

namespace AnyContent\Connection;

use AnyContent\Client\Record;

use AnyContent\Connection\Configuration\RestLikeConfiguration;
use KVMLogger\KVMLoggerFactory;
use KVMLogger\KVMLogger;

class RestLikeBasicConnectionAdminTest extends \PHPUnit_Framework_TestCase
{

    /** @var  RestLikeBasicReadWriteConnection */
    public $connection;


    public function setUp()
    {
        if (defined('PHPUNIT_CREDENTIALS_RESTLIKE_URL2')) {
            $configuration = new RestLikeConfiguration();

            $configuration->setUri(PHPUNIT_CREDENTIALS_RESTLIKE_URL2);
            $connection = $configuration->createReadWriteConnection();

            $configuration->addContentTypes();
            $configuration->addConfigTypes();

            $this->connection = $connection;

            KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
        }

    }


    public function testNrOfContentTypes()
    {
        $connection = $this->connection;

        if (!$connection) {
            $this->markTestSkipped('RestLike Basic Connection credentials missing.');
        }

        $this->connection->deleteContentTypeCMDL('add');

        $definitions = $this->connection->getContentTypeDefinitions();

        $this->assertCount(2, $definitions);

        $this->connection->saveContentTypeCMDL('add', 'name');

        $definitions = $this->connection->getContentTypeDefinitions();

        $this->assertCount(3, $definitions);

    }


    public function testChangedCMDLOfContentType()
    {
        $connection = $this->connection;

        if (!$connection) {
            $this->markTestSkipped('RestLike Basic Connection credentials missing.');
        }

        $cmdl = $this->connection->getCMDLForContentType('add');

        $this->assertEquals('name', $cmdl);

        $this->connection->saveContentTypeCMDL('add', 'name = textfield');

        $cmdl = $this->connection->getCMDLForContentType('add');

        $this->assertEquals('name = textfield', $cmdl);
    }


    public function testDeleteContentTypes()
    {
        $connection = $this->connection;

        if (!$connection) {
            $this->markTestSkipped('RestLike Basic Connection credentials missing.');
        }

        $definitions = $this->connection->getContentTypeDefinitions();

        $this->assertCount(3, $definitions);

        $this->connection->deleteContentTypeCMDL('add');

        $definitions = $this->connection->getContentTypeDefinitions();

        $this->assertCount(2, $definitions);
    }


    public function testNrOfConfigTypes()
    {
        $connection = $this->connection;

        if (!$connection) {
            $this->markTestSkipped('RestLike Basic Connection credentials missing.');
        }

        $this->connection->deleteConfigTypeCMDL('add');

        $definitions = $this->connection->getConfigTypeDefinitions();

        $this->assertCount(1, $definitions);

        $this->connection->saveConfigTypeCMDL('add', 'name');

        $definitions = $this->connection->getConfigTypeDefinitions();

        $this->assertCount(2, $definitions);

    }


    public function testChangedCMDLOfConfigType()
    {
        $connection = $this->connection;

        if (!$connection) {
            $this->markTestSkipped('RestLike Basic Connection credentials missing.');
        }

        $cmdl = $this->connection->getCMDLForConfigType('add');

        $this->assertEquals('name', $cmdl);

        $this->connection->saveConfigTypeCMDL('add', 'name = textfield');

        $cmdl = $this->connection->getCMDLForConfigType('add');

        $this->assertEquals('name = textfield', $cmdl);
    }


    public function testDeleteConfigTypes()
    {
        $connection = $this->connection;

        if (!$connection) {
            $this->markTestSkipped('RestLike Basic Connection credentials missing.');
        }

        $definitions = $this->connection->getConfigTypeDefinitions();

        $this->assertCount(2, $definitions);

        $this->connection->deleteConfigTypeCMDL('add');

        $definitions = $this->connection->getConfigTypeDefinitions();

        $this->assertCount(1, $definitions);
    }

}