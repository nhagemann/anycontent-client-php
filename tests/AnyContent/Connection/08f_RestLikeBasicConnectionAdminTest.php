<?php

namespace AnyContent\Connection;

use AnyContent\Client\Record;

use AnyContent\Connection\Configuration\MySQLSchemalessConfiguration;
use AnyContent\Connection\Configuration\RestLikeConfiguration;
use KVMLogger\KVMLoggerFactory;
use KVMLogger\KVMLogger;

class RestLikeBasicConnectionAdminTest extends \PHPUnit_Framework_TestCase
{

    /** @var  RestLikeBasicReadWriteConnection */
    public $connection;


    /**
     * @throws \AnyContent\AnyContentClientException
     */
    public static function setUpBeforeClass()
    {

        // drop & create database
        $pdo = new \PDO('mysql:host=anycontent-client-phpunit-mysql;port=3306;charset=utf8', 'root', 'root');

        $pdo->exec('DROP DATABASE IF EXISTS phpunit');
        $pdo->exec('CREATE DATABASE phpunit');

        $configuration = new MySQLSchemalessConfiguration();

        $configuration->initDatabase('anycontent-client-phpunit-mysql', 'phpunit', 'root', 'root');
        $configuration->setRepositoryName('phpunit');

        $configuration->importCMDL(__DIR__ . '/../../resources/RestLikeBasicConnectionTests');

        $configuration->addContentTypes();

        $connection = $configuration->createReadWriteConnection();

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
    }


    public function setUp()
    {

        $configuration = new RestLikeConfiguration();

        $configuration->setUri(getenv('PHPUNIT_RESTLIKE_URI'));
        $connection = $configuration->createReadWriteConnection();

        $configuration->addContentTypes();
        $configuration->addConfigTypes();

        $this->connection = $connection;

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');

    }


    public function testNrOfContentTypes()
    {

        $this->connection->deleteContentTypeCMDL('add');

        $definitions = $this->connection->getContentTypeDefinitions();

        $this->assertCount(5, $definitions);

        $this->connection->saveContentTypeCMDL('add', 'name');

        $definitions = $this->connection->getContentTypeDefinitions();

        $this->assertCount(6, $definitions);

    }


    public function testChangedCMDLOfContentType()
    {
        $cmdl = $this->connection->getCMDLForContentType('add');

        $this->assertEquals('name', $cmdl);

        $this->connection->saveContentTypeCMDL('add', 'name = textfield');

        $cmdl = $this->connection->getCMDLForContentType('add');

        $this->assertEquals('name = textfield', $cmdl);
    }


    public function testDeleteContentTypes()
    {
        $definitions = $this->connection->getContentTypeDefinitions();

        $this->assertCount(6, $definitions);

        $this->connection->deleteContentTypeCMDL('add');

        $definitions = $this->connection->getContentTypeDefinitions();

        $this->assertCount(5, $definitions);
    }


    public function testNrOfConfigTypes()
    {

        $this->connection->deleteConfigTypeCMDL('add');

        $definitions = $this->connection->getConfigTypeDefinitions();

        $this->assertCount(4, $definitions);

        $this->connection->saveConfigTypeCMDL('add', 'name');

        $definitions = $this->connection->getConfigTypeDefinitions();

        $this->assertCount(5, $definitions);

    }


    public function testChangedCMDLOfConfigType()
    {

        $cmdl = $this->connection->getCMDLForConfigType('add');

        $this->assertEquals('name', $cmdl);

        $this->connection->saveConfigTypeCMDL('add', 'name = textfield');

        $cmdl = $this->connection->getCMDLForConfigType('add');

        $this->assertEquals('name = textfield', $cmdl);
    }


    public function testDeleteConfigTypes()
    {

        $definitions = $this->connection->getConfigTypeDefinitions();

        $this->assertCount(5, $definitions);

        $this->connection->deleteConfigTypeCMDL('add');

        $definitions = $this->connection->getConfigTypeDefinitions();

        $this->assertCount(4, $definitions);
    }

}