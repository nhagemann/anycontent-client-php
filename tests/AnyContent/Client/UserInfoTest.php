<?php

declare(strict_types=1);

namespace Tests\AnyContent\Client;

use AnyContent\Client\Repository;
use AnyContent\Client\UserInfo;
use AnyContent\Connection\Configuration\ContentArchiveConfiguration;
use AnyContent\Connection\ContentArchiveReadWriteConnection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class UserInfoTest extends TestCase
{
    /** @var  ContentArchiveReadWriteConnection */
    public $connection;

    /** @var  Repository */
    public $repository;

    public static function setUpBeforeClass(): void
    {
        $target = __DIR__ . '/../../../tmp/ExampleContentArchive';
        $source = __DIR__ . '/../../resources/ContentArchiveExample2';

        $fs = new Filesystem();

        if (file_exists($target)) {
            $fs->remove($target);
        }

        $fs->mirror($source, $target);
    }

    public function setUp(): void
    {
        $target = __DIR__ . '/../../../tmp/ExampleContentArchive';

        $configuration = new ContentArchiveConfiguration();

        $configuration->setContentArchiveFolder($target);

        $connection = $configuration->createReadWriteConnection();

        $this->connection = $connection;

        $this->repository = new Repository('phpunit', $this->connection);
    }

    public function testUserInfo()
    {
        $this->repository->setUserInfo(new UserInfo('john.doe@example.org', 'John', 'Doe'));

        $this->repository->selectContentType('example01');

        $record = $this->repository->createRecord('New Record ');

        $id = $this->repository->saveRecord($record);
        $this->assertEquals(1, $id);

        $this->assertInstanceOf('AnyContent\Client\UserInfo', $record->getCreationUserInfo());
        $this->assertInstanceOf('AnyContent\Client\UserInfo', $record->getLastChangeUserInfo());

        /** @var UserInfo $userinfo */
        $userinfo = $record->getCreationUserInfo();

        $this->assertEquals('john.doe@example.org', $userinfo->getUsername());
        $this->assertEquals('John', $userinfo->getFirstname());
        $this->assertEquals('Doe', $userinfo->getLastname());
        $this->assertTrue($userinfo->userNameIsAnEmailAddress());
    }

    public function testUserNewConnectionInfo()
    {
        $this->repository->selectContentType('example01');

        $record = $this->repository->getRecord(1);

        $this->assertInstanceOf('AnyContent\Client\UserInfo', $record->getCreationUserInfo());
        $this->assertInstanceOf('AnyContent\Client\UserInfo', $record->getLastChangeUserInfo());

        /** @var UserInfo $userinfo */
        $userinfo = $record->getCreationUserInfo();

        $this->assertEquals('john.doe@example.org', $userinfo->getUsername());
        $this->assertEquals('John', $userinfo->getFirstname());
        $this->assertEquals('Doe', $userinfo->getLastname());
        $this->assertTrue($userinfo->userNameIsAnEmailAddress());
    }

    public function testChangeUserInfo()
    {
        $this->repository->setUserInfo(new UserInfo('john.tester@example.org', 'John', 'Tester'));

        $this->repository->selectContentType('example01');

        $record = $this->repository->getRecord(1);

        $this->repository->saveRecord($record);

        /** @var UserInfo $userinfo */
        $userinfo = $record->getCreationUserInfo();

        $this->assertEquals('john.doe@example.org', $userinfo->getUsername());
        $this->assertEquals('John', $userinfo->getFirstname());
        $this->assertEquals('Doe', $userinfo->getLastname());
        $this->assertTrue($userinfo->userNameIsAnEmailAddress());

        /** @var UserInfo $userinfo */
        $userinfo = $record->getLastChangeUserInfo();

        $this->assertEquals('john.tester@example.org', $userinfo->getUsername());
        $this->assertEquals('John', $userinfo->getFirstname());
        $this->assertEquals('Tester', $userinfo->getLastname());
        $this->assertTrue($userinfo->userNameIsAnEmailAddress());
    }
}
