<?php

namespace AnyContent\Files;

use AnyContent\Client\File;
use AnyContent\Connection\Configuration\RestLikeConfiguration;

use AnyContent\Connection\FileManager\RestLikeFilesAccess;
use AnyContent\Connection\RestLikeBasicReadWriteConnection;
use KVMLogger\KVMLoggerFactory;

class RestLikeFilesTest extends \PHPUnit_Framework_TestCase
{

    /** @var  RestLikeBasicReadWriteConnection */
    public $connection;

    /**
     * @var RestLikeFilesAccess
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
        if (defined('PHPUNIT_CREDENTIALS_RESTLIKE_URL2'))
        {
            $configuration = new RestLikeConfiguration();

            $configuration->setUri(PHPUNIT_CREDENTIALS_RESTLIKE_URL2);
            $connection = $configuration->createReadWriteConnection();

            $configuration->addContentTypes();
            $configuration->addConfigTypes();

            $this->connection = $connection;

            $fileManager       = new RestLikeFilesAccess($configuration);
            $this->fileManager = $fileManager;

            KVMLoggerFactory::createWithKLogger(__DIR__ . '/../../../tmp');
        }

    }


    public function testBasicFolderOperations()
    {
        if (!$this->fileManager)
        {
            $this->markTestSkipped('RestLike Basic Connection credentials missing.');
        }

        $folder = $this->fileManager->getFolder();
        $this->assertInstanceOf('AnyContent\Client\Folder', $folder);

        $folder = $this->fileManager->getFolder('test');
        $this->assertInstanceOf('AnyContent\Client\Folder', $folder);

        $folder = $this->fileManager->getFolder('notfound');
        $this->assertFalse($folder);
    }


    public function testBasicFileOperations()
    {
        if (!$this->fileManager)
        {
            $this->markTestSkipped('RestLike Basic Connection credentials missing.');
        }

        $file = $this->fileManager->getFile('test/dieter.jpg');
        $this->assertFalse($file);

        $file = $this->fileManager->getFile('test/heiko.jpg');
        $this->assertInstanceOf('AnyContent\Client\File', $file);


        $binary = $this->fileManager->getBinary($file);

        $this->assertEquals(5237, strlen($binary));

        $file   = new File('test', 'test/heike.jpg', 'heike.jpg', $binary, [ ]);
        $binary = $this->fileManager->getBinary($file);
        $this->assertFalse($binary);
    }


    public function testCreateAndDeleteFolder()
    {
        if (!$this->fileManager)
        {
            $this->markTestSkipped('RestLike Basic Connection credentials missing.');
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
            $this->markTestSkipped('RestLike Basic Connection credentials missing.');
        }

        $file = $this->fileManager->getFile('test/heiko.jpg');
        $this->assertInstanceOf('AnyContent\Client\File', $file);

        $binary = $this->fileManager->getBinary($file);

        $this->fileManager->saveFile('norbert.jpg', $binary);

        $file = $this->fileManager->getFile('norbert.jpg');
        $this->assertInstanceOf('AnyContent\Client\File', $file);

        $binary = $this->fileManager->getBinary($file);
        $this->assertEquals(5237, strlen($binary));

        $this->fileManager->deleteFile('norbert.jpg');

        $file = $this->fileManager->getFile('norbert.jpg');
        $this->assertFalse($file);
    }
}