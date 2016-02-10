<?php

namespace AnyContent\Files;

use AnyContent\Client\File;

use AnyContent\Connection\FileManager\S3FilesAccess;

use KVMLogger\KVMLoggerFactory;

class S3Test extends \PHPUnit_Framework_TestCase
{



    /**
     * @var S3FilesAccess
     */
    public $fileManager;

    static $randomString1;
    static $randomString2;


    public static function setUpBeforeClass()
    {
        self::$randomString1 = md5(time());
        self::$randomString2 = md5(time());
    }


    public function setUp()
    {
        if (defined('S3_KEY'))
        {


            $fileManager       = new S3FilesAccess(S3_KEY,S3_SECRET,S3_BUCKET,S3_BASEPATH,S3_REGION);
            $this->fileManager = $fileManager;

            KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
        }

    }


    public function testBasicFolderOperations()
    {
        if (!$this->fileManager)
        {
            $this->markTestSkipped('S3 config missing.');
        }

        $folder = $this->fileManager->getFolder();
        $this->assertInstanceOf('AnyContent\Client\Folder', $folder);

        $folder = $this->fileManager->getFolder('Public');
        $this->assertInstanceOf('AnyContent\Client\Folder', $folder);

        $folder = $this->fileManager->getFolder('notfound');
        $this->assertFalse($folder);
    }


    public function testBasicFileOperations()
    {
        if (!$this->fileManager)
        {
            $this->markTestSkipped('S3 config missing.');
        }

        $file = $this->fileManager->getFile('Public/geek_tasks.png');
        $this->assertInstanceOf('AnyContent\Client\File', $file);

        $binary = $this->fileManager->getBinary($file);
        $this->assertEquals(67098, strlen($binary));

        $file   = new File('test', 'test/heike.jpg', 'heike.jpg', $binary, [ ]);
        $binary = $this->fileManager->getBinary($file);
        $this->assertFalse($binary);
    }


    public function testCreateAndDeleteFolder()
    {
        if (!$this->fileManager)
        {
            $this->markTestSkipped('S3 config missing.');
        }

        $this->fileManager->createFolder('testfolder');

        $folder = $this->fileManager->getFolder('testfolder');
        $this->assertInstanceOf('AnyContent\Client\Folder', $folder);

        $this->fileManager->deleteFolder('testfolder');
        $folder = $this->fileManager->getFolder('testfolder');
        $this->assertFalse($folder);
    }


    public function testSaveAndDelete()
    {
        if (!$this->fileManager)
        {
            $this->markTestSkipped('S3 config missing.');
        }

        $file = $this->fileManager->getFile('Public/geek_tasks.png');
        $this->assertInstanceOf('AnyContent\Client\File', $file);

        $binary = $this->fileManager->getBinary($file);

        $this->fileManager->saveFile('norbert.jpg', $binary);

        $file = $this->fileManager->getFile('norbert.jpg');
        $this->assertInstanceOf('AnyContent\Client\File', $file);

        $binary = $this->fileManager->getBinary($file);
        $this->assertEquals(67098, strlen($binary));

        $this->fileManager->deleteFile('norbert.jpg');

        $file = $this->fileManager->getFile('norbert.jpg');
        $this->assertFalse($file);
    }
}