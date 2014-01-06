<?php

namespace AnyContent\Client;

use CMDL\Parser;

use AnyContent\Client\Client;
use AnyContent\Client\Record;
use AnyContent\Client\Folder;
use AnyContent\Client\Files;

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
        $this->assertEquals(256,$file->getWidth());
        $this->assertEquals(256,$file->getHeight());

        $file = $folder->getFile('a.txt');
        $this->assertFalse($file->isImage());
    }
}
