<?php

namespace AnyContent\Admin;

use AnyContent\Client\Repository;
use AnyContent\Connection\Configuration\MySQLSchemalessConfiguration;
use AnyContent\Connection\MySQLSchemalessReadWriteConnection;
use KVMLogger\KVMLoggerFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class AdminTestMySQLSchemaless1Test
 *
 * Testing CMDL operations with cmdl folder
 *
 * @package AnyContent\Admin
 */
class AdminTestMySQLSchemaless1Test extends TestCase
{
    /** @var  MySQLSchemalessReadWriteConnection */
    public $connection;

    /** @var  Repository */
    public $repository;


    /**
     * @throws \AnyContent\AnyContentClientException
     */
    public static function setUpBeforeClass(): void
    {

        $source = __DIR__ . '/../../resources/ContentArchiveExample1/cmdl';
        $target = __DIR__ . '/../../../tmp/MySqlSchemaLessCMDL';

        $fs = new Filesystem();

        if (file_exists($target)) {
            $fs->remove($target);
        }

        $fs->mirror($source, $target);

        // drop & create database
        $pdo = new \PDO('mysql:host=anycontent-client-phpunit-mysql;port=3306;charset=utf8', 'root', 'root');

        $pdo->exec('DROP DATABASE IF EXISTS phpunit');
        $pdo->exec('CREATE DATABASE phpunit');

        $configuration = new MySQLSchemalessConfiguration();

        $configuration->initDatabase('anycontent-client-phpunit-mysql', 'phpunit', 'root', 'root');
        $configuration->setCMDLFolder($target);
        $configuration->setRepositoryName('phpunit');
        $configuration->addContentTypes();

        $configuration->createReadWriteConnection();

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
    }


    /**
     * @throws \AnyContent\AnyContentClientException
     */
    public function setUp(): void
    {
        $target = __DIR__ . '/../../../tmp/MySqlSchemaLessCMDL';

        $configuration = new MySQLSchemalessConfiguration();

        $configuration->initDatabase('anycontent-client-phpunit-mysql', 'phpunit', 'root', 'root');

        $configuration->setCMDLFolder($target);
        $configuration->setRepositoryName('phpunit');
        $configuration->addContentTypes();
        $configuration->addConfigTypes();

        $connection = $configuration->createReadWriteConnection();

        $this->connection = $connection;
        $repository       = new Repository('phpunit', $connection);
        $this->repository = $repository;

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
    }


    public function testSetupAsExpected()
    {
        $connection = $this->connection;

        $repository = $this->repository;

        $this->assertCount(2, $repository->getContentTypeNames());
        $this->assertCount(1, $connection->getConfigTypeNames());

        $this->assertTrue($repository->isWritable());
        $this->assertTrue($repository->isAdministrable());
    }


    public function testAddContentType()
    {
        $connection = $this->connection;

        $cmdl = 'Name';

        $connection->saveContentTypeCMDL('neu', $cmdl);

        $this->assertCount(3, $connection->getContentTypeNames());

        $this->assertEquals($cmdl, $connection->getCMDLForContentType('neu'));
    }


    public function testDeleteContentType()
    {
        $connection = $this->connection;

        $connection->deleteContentTypeCMDL('neu');

        $this->assertCount(2, $connection->getContentTypeNames());

        $this->expectException('AnyContent\AnyContentClientException');
        $connection->getCMDLForContentType('neu');
    }


    public function testAddConfigType()
    {
        $connection = $this->connection;

        $cmdl = 'Name';

        $this->assertCount(1, $connection->getConfigTypeNames());

        $connection->saveConfigTypeCMDL('neu', $cmdl);

        $this->assertCount(2, $connection->getConfigTypeNames());

        $this->assertEquals($cmdl, $connection->getCMDLForConfigType('neu'));
    }


    public function testDeleteConfigType()
    {
        $connection = $this->connection;

        $connection->deleteConfigTypeCMDL('neu');

        $this->assertCount(1, $connection->getConfigTypeNames());

        $this->expectException('AnyContent\AnyContentClientException');
        $connection->getCMDLForConfigType('neu');
    }
}
