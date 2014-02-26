<?php

namespace AnyContent\Client;

use CMDL\Parser;

use AnyContent\Client\Client;
use AnyContent\Client\Record;
use AnyContent\Client\Folder;
use AnyContent\Client\Files;
use AnyContent\Client\File;

use AnyContent\Client\UserInfo;

class FilesTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var $client Client
     */
    public $client = null;


    public function setUp()
    {
        // Connect to repository
        $client = new Client('http://anycontent.dev/1/example');
        $client->setUserInfo(new UserInfo('john.doe@example.lorg', 'John', 'Doe'));
        $this->client = $client;
    }


    public function testListFiles()
    {
        $folder = $this->client->getFolder();
        $this->assertCount(3, $folder->getFiles());
        $this->assertArrayHasKey('a.txt', $folder->getFiles());
        $this->assertArrayHasKey('b.txt', $folder->getFiles());
        $this->assertArrayHasKey('len_std.jpg', $folder->getFiles());

        $folder = $this->client->getFolder('Music');
        $this->assertCount(1, $folder->getFiles());
        $this->assertArrayHasKey('Music/c.txt', $folder->getFiles());

        $folder = $this->client->getFolder('/Music');
        $this->assertCount(1, $folder->getFiles());
        $this->assertArrayHasKey('Music/c.txt', $folder->getFiles());

        $folder = $this->client->getFolder('/Music/');
        $this->assertCount(1, $folder->getFiles());
        $this->assertArrayHasKey('Music/c.txt', $folder->getFiles());

        $folder = $this->client->getFolder('/Music/Alternative');
        $this->assertCount(3, $folder->getFiles());
        $this->assertArrayHasKey('Music/Alternative/d.txt', $folder->getFiles());

        $folder = $this->client->getFolder('/Music/Pop');
        $this->assertCount(0, $folder->getFiles());
        $this->assertArrayNotHasKey('Music/Alternative/d.txt', $folder->getFiles());

        $folder = $this->client->getFolder('/Music/Jazz');
        $this->assertFalse($folder);
    }


    public function testFileTypes()
    {
        $folder = $this->client->getFolder();
        $this->assertCount(3, $folder->getFiles());

        $file = $folder->getFile('len_std.jpg');

        $this->assertTrue($file->isImage());
        $this->assertEquals(256, $file->getWidth());
        $this->assertEquals(256, $file->getHeight());

        $file = $folder->getFile('a.txt');
        $this->assertFalse($file->isImage());
    }


    public function testBinaryFunctions()
    {
        $folder = $this->client->getFolder();
        $file   = $folder->getFile('a.txt');

        $binary = $this->client->getBinary($file);

        $this->assertEquals('a.txt', $binary);

        $binary = $this->client->getRepository()->getBinary($file);

        $this->assertEquals('a.txt', $binary);
    }


    public function testNewFiles()
    {
        $repository = $this->client->getRepository();

        $repository->deleteFolder('Test', true);

        $repository->saveFile('Test/test.txt', 'test');

        $folder = $repository->getFolder('Test');

        $this->assertInstanceOf('AnyContent\Client\Folder', $folder);

        $file = $folder->getFile('test.txt');

        $this->assertInstanceOf('AnyContent\Client\File', $file);

        $binary = $repository->getBinary($file);

        $this->assertEquals('test', $binary);

        $repository->deleteFile($file->getId(), false);

        $folder = $repository->getFolder('Test');

        $this->assertInstanceOf('AnyContent\Client\Folder', $folder);

        $file = $folder->getFile('test.txt');

        $this->assertFalse($file);

        $repository->saveFile('Test/test.txt', 'test');

        $repository->deleteFile('Test/test.txt', true);

        $folder = $repository->getFolder('Test');

        $this->assertFalse($folder);

    }


    public function testBinaryCopy()
    {
        $repository = $this->client->getRepository();

        $file   = $repository->getFile('len_std.jpg');
        $binary = $repository->getBinary($file);

        $repository->saveFile('Test/test.jpg', $binary);

        $file = $repository->getFile('Test/test.jpg');

        $this->assertEquals($binary, $repository->getBinary($file));
    }


    public function testFolderOperations()
    {
        $repository = $this->client->getRepository();

        $repository->deleteFolder('Test', true);

        $repository->createFolder('Test/A/B/C');

        $folder = $repository->getFolder('Test/A/B/C');

        $this->assertInstanceOf('AnyContent\Client\Folder', $folder);

        $repository->saveFile('Test/A/B/test.txt', 'test');

        $repository->deleteFile('Test/test.txt', true);

        $folder = $repository->getFolder('Test/A/B/C');

        $this->assertInstanceOf('AnyContent\Client\Folder', $folder);

        $repository->deleteFolder('Test');

        $folder = $repository->getFolder('Test/A/B/C');

        $this->assertInstanceOf('AnyContent\Client\Folder', $folder);

        $repository->deleteFolder('Test', true);

        $folder = $repository->getFolder('Test/A/B/C');

        $this->assertFalse($folder);

    }
}
