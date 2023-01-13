<?php

namespace AnyContent\Client;

use AnyContent\Connection\Configuration\ContentArchiveConfiguration;
use AnyContent\Connection\ContentArchiveReadWriteConnection;
use KVMLogger\KVMLoggerFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class LanguagesAndWorkspacesTest extends TestCase
{
    /** @var  ContentArchiveReadWriteConnection
     */
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

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
    }

    public function testSaveRecords()
    {
        $this->repository->selectContentType('example01');

        for ($i = 1; $i <= 5; $i++) {
            $record = $this->repository->createRecord('New Record ' . $i);
            $record->setProperty('article', 'Test ' . $i);
            $id = $this->repository->saveRecord($record);
            $this->assertEquals($i, $id);
        }

        $this->repository->selectLanguage('es');

        for ($i = 1; $i <= 5; $i++) {
            $record = $this->repository->createRecord('New Record ' . (5 + $i));
            $record->setProperty('article', 'Test ' . (5 + $i));
            $id = $this->repository->saveRecord($record);
            $this->assertEquals(5 + $i, $id);
        }

        $this->repository->selectWorkspace('live');

        for ($i = 1; $i <= 5; $i++) {
            $record = $this->repository->createRecord('New Record ' . (10 + $i));
            $record->setProperty('article', 'Test ' . (10 + $i));
            $id = $this->repository->saveRecord($record);
            $this->assertEquals(10 + $i, $id);
        }

        $this->repository->reset();

        $c              = $this->repository->countRecords();
        $this->assertEquals(5, $c);

        $this->repository->selectLanguage('es');
        $c = $this->repository->countRecords();
        $this->assertEquals(5, $c);

        $this->repository->selectWorkspace('live');
        $c = $this->repository->countRecords();
        $this->assertEquals(5, $c);

        $this->repository->selectLanguage('default');
        $c = $this->repository->countRecords();
        $this->assertEquals(0, $c);
        ;
    }
}
