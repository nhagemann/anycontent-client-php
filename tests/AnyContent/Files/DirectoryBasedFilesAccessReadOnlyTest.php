<?php

namespace AnyContent\Connection;

use AnyContent\Connection\FileManager\DirectoryBasedFilesAccess;
use AnyContent\Connection\Interfaces\FileManager;
use KVMLogger\KVMLoggerFactory;
use KVMLogger\KVMLogger;

class DirectoryBasedFilesAccessReadOnlyTest extends \PHPUnit_Framework_TestCase
{

    /** @var  DirectoryBasedFilesAccess */
    public $fileManager;


    public function setUp()
    {

        $fileManager = new DirectoryBasedFilesAccess(__DIR__ . '/../../resources/Files');
        $fileManager->enableImageSizeCalculation();

        $this->fileManager = $fileManager;

        KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');

    }


    public function testGetFolder()
    {
        $fileManager = $this->fileManager;

        $folder = $fileManager->getFolder();
        $this->assertInstanceOf('AnyContent\Client\Folder', $folder);
        $this->assertCount(2, $folder->listSubFolders());

        $folder = $fileManager->getFolder('Music/');
        $this->assertCount(2, $folder->listSubFolders());

        $folder = $fileManager->getFolder('Music/Alternative');
        $this->assertCount(0, $folder->listSubFolders());

        $folder = $fileManager->getFolder('Music/Pop');
        $this->assertCount(0, $folder->listSubFolders());

        $folder = $fileManager->getFolder('Music/Jazz');
        $this->assertFalse($folder);
    }


    public function testGetFile()
    {
        $fileManager = $this->fileManager;

        $file = $fileManager->getFile('a.txt');
        $this->assertInstanceOf('AnyContent\Client\File', $file);

        $file = $fileManager->getFile('Music/c.txt');
        $this->assertEquals('c.txt', $file->getName());
        $this->assertEquals('Music/c.txt', $file->getId());
        $this->assertFalse($file->getUrl());

        $folder = $file->getFolder();
        $this->assertInstanceOf('AnyContent\Client\Folder', $folder);
        $this->assertCount(2, $folder->listSubFolders());

        $file = $fileManager->getFile('z.txt');
        $this->assertFalse($file);

    }


    public function testUrl()
    {
        $fileManager = $this->fileManager->setPublicUrl('http://www.example.org');

        $file = $fileManager->getFile('a.txt');
        $this->assertInstanceOf('AnyContent\Client\File', $file);

        $file = $fileManager->getFile('Music/c.txt');
        $this->assertEquals('c.txt', $file->getName());
        $this->assertEquals('Music/c.txt', $file->getId());
        $this->assertEquals('http://www.example.org/Music/c.txt', $file->getUrl());
    }


    public function testImage()
    {
        $fileManager = $this->fileManager;

        $file = $fileManager->getFile('len_std.jpg');
        $this->assertInstanceOf('AnyContent\Client\File', $file);
        $this->assertTrue($file->isImage());
        $this->assertEquals(256, $file->getHeight());
        $this->assertEquals(256, $file->getWidth());
        $this->assertEquals(20401, $file->getSize());

        $file = $fileManager->getFile('Music/c.txt');
        $this->assertFalse($file->isImage());
    }


    public function testBinary()
    {
        $fileManager = $this->fileManager;
        $file        = $fileManager->getFile('a.txt');
        $binary = $fileManager->getBinary($file);

        $this->assertEquals('a.txt',$binary);

    }

}