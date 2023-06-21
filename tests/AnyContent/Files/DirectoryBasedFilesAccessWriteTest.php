<?php

declare(strict_types=1);

namespace Tests\AnyContent\Connection;

use AnyContent\Connection\FileManager\DirectoryBasedFilesAccess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class DirectoryBasedFilesAccessWriteTest extends TestCase
{
    /** @var  DirectoryBasedFilesAccess */
    public $fileManager;

    public static function setUpBeforeClass(): void
    {
        $target = __DIR__ . '/../../../tmp/files';
        $source = __DIR__ . '/../../resources/Files';

        $fs = new Filesystem();

        if (file_exists($target)) {
            $fs->remove($target);
        }

        $fs->mirror($source, $target);
    }

    public function setUp(): void
    {
        $fileManager = new DirectoryBasedFilesAccess(__DIR__ . '/../../../tmp/files');
        $fileManager->enableImageSizeCalculation();

        $this->fileManager = $fileManager;
    }

    public function testSaveFiles()
    {
        $fileManager = $this->fileManager;

        $file = $fileManager->getFile('Test/test.txt');
        $this->assertFalse($file);

        $fileManager->saveFile('Test/test.txt', 'test');

        $file = $fileManager->getFile('Test/test.txt');
        $this->assertEquals('test', $fileManager->getBinary($file));
    }

    public function testCopyImage()
    {
        $fileManager = $this->fileManager;

        $file = $fileManager->getFile('len_std.jpg');

        $binary = $fileManager->getBinary($file);

        $fileManager->saveFile('Test/test.jpg', $binary);

        $file = $fileManager->getFile('Test/test.jpg');
        $this->assertEquals($binary, $fileManager->getBinary($file));
        $this->assertTrue($file->isImage());
    }

    public function testDeleteFile()
    {
        $fileManager = $this->fileManager;

        $file = $fileManager->getFile('Test/test.jpg');
        $this->assertInstanceOf('AnyContent\Client\File', $file);

        $fileManager->deleteFile('Test/test.jpg', false);
        $file = $fileManager->getFile('Test/test.jpg');
        $this->assertFalse($file);
    }

    public function testDeleteFolder()
    {
        $fileManager = $this->fileManager;

        $folder = $fileManager->getFolder('Test');
        $this->assertInstanceOf('AnyContent\Client\Folder', $folder);
        $this->assertCount(1, $folder->getFiles());

        $result = $fileManager->deleteFolder('Test');
        $this->assertFalse($result);

        $folder = $fileManager->getFolder('Test');
        $this->assertInstanceOf('AnyContent\Client\Folder', $folder);

        $result = $fileManager->deleteFolder('Test', true);
        $this->assertTrue($result);

        $folder = $fileManager->getFolder('Test');
        $this->assertFalse($folder);
    }

    public function testCreateAndDeleteFolder()
    {
        $fileManager = $this->fileManager;

        $folder = $fileManager->getFolder('Test');
        $this->assertFalse($folder);

        $result = $fileManager->createFolder('Test');
        $this->assertTrue($result);

        $result = $fileManager->deleteFolder('Test', true);
        $this->assertTrue($result);

        $folder = $fileManager->getFolder('Test');
        $this->assertFalse($folder);
    }
}
