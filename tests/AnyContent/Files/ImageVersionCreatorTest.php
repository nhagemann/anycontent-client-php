<?php

declare(strict_types=1);

namespace Tests\AnyContent\Files;

use AnyContent\Client\Repository;
use AnyContent\Client\Util\ImageVersionCreator;
use AnyContent\Connection\Configuration\RecordsFileConfiguration;
use AnyContent\Connection\FileManager\DirectoryBasedFilesAccess;
use GdImage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class ImageVersionCreatorTest extends TestCase
{
    /** @var  DirectoryBasedFilesAccess */
    protected $fileManager;

    /** @var  Repository */
    protected $repository;

    /** @var  ImageVersionCreator */
    protected $imageVersionCreator;

    protected $basePath;

    public function setUp(): void
    {
        $this->basePath = __DIR__ . '/../../../tmp/imageversions';

        $configuration = new RecordsFileConfiguration();

        $configuration->addContentType(
            'profiles',
            __DIR__ . '/../../resources/RecordsFileExample/profiles.cmdl',
            __DIR__ . '/../../resources/RecordsFileExample/profiles.json'
        );

        $connection = $configuration->createReadOnlyConnection();

        $repository = new Repository('phpunit', $connection);

        $repository->selectContentType('profiles');

        $fileManager = new DirectoryBasedFilesAccess(__DIR__ . '/../../resources/Files');
        $fileManager->enableImageSizeCalculation();

        $repository->setFileManager($fileManager);

        $imageVersionCreator = new ImageVersionCreator(
            $repository,
            $this->basePath,
            'http://www.phpunit.test'
        );

        $this->fileManager         = $fileManager;
        $this->repository          = $repository;
        $this->imageVersionCreator = $imageVersionCreator;

        $fs = new Filesystem();

        if (file_exists($this->basePath)) {
            $fs->remove($this->basePath);
        }

        $fs->mkdir($this->basePath);
    }

    public function testGDPresent(): void
    {
        $info = gd_info();

        $types = ['GIF Read Support', 'GIF Create Support', 'JPEG Support', 'PNG Support', 'WebP Support'];

        foreach ($types as $type) {
            $this->assertTrue($info[$type], sprintf('%s not activated.', $type));
        }

        $data = 'iVBORw0KGgoAAAANSUhEUgAAABwAAAASCAMAAAB/2U7WAAAABl'
            . 'BMVEUAAAD///+l2Z/dAAAASUlEQVR4XqWQUQoAIAxC2/0vXZDr'
            . 'EX4IJTRkb7lobNUStXsB0jIXIAMSsQnWlsV+wULF4Avk9fLq2r'
            . '8a5HSE35Q3eO2XP1A1wQkZSgETvDtKdQAAAABJRU5ErkJggg==';
        $data = base64_decode($data);

        $image = imagecreatefromstring($data);
        $this->assertInstanceOf(GdImage::class, $image);
    }

    public function testDefaultImage()
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

    public function testDefaultResize()
    {
        $fileManager = $this->fileManager;

        $file = $fileManager->getFile('len_std.jpg');

        $file = $this->imageVersionCreator->getResizedImage($file);

        $this->assertInstanceOf('AnyContent\Client\File', $file);

        $this->assertEquals('http://www.phpunit.test/len_std_100x100c.jpg', $file->getUrl());

        list($width, $height) = getimagesize($this->basePath . '/len_std_100x100c.jpg');

        $this->assertEquals(100, $width);
        $this->assertEquals(100, $height);

        $this->assertNotEquals($file->getSize(), filesize($this->basePath . '/len_std_100x100c.jpg'));
    }

    public function testDistinctResize()
    {
        $fileManager = $this->fileManager;

        $file = $fileManager->getFile('len_std.jpg');

        $file = $this->imageVersionCreator->getResizedImage($file, 'default', 256, 256);

        $this->assertInstanceOf('AnyContent\Client\File', $file);

        $this->assertEquals('http://www.phpunit.test/len_std_256x256c.jpg', $file->getUrl());

        list($width, $height) = getimagesize($this->basePath . '/len_std_256x256c.jpg');

        $this->assertEquals(256, $width);
        $this->assertEquals(256, $height);

        $this->assertNotEquals($file->getSize(), filesize($this->basePath . '/len_std_256x256c.jpg'));
    }

    public function testDistinctResizeKeepOriginalImage()
    {
        $this->imageVersionCreator->setKeepOriginalImageIfSizeIsTheSame(true);
        $fileManager = $this->fileManager;

        $file = $fileManager->getFile('len_std.jpg');

        $file = $this->imageVersionCreator->getResizedImage($file, 'default', 256, 256);

        $this->assertInstanceOf('AnyContent\Client\File', $file);

        $this->assertEquals('http://www.phpunit.test/len_std_256x256c.jpg', $file->getUrl());

        list($width, $height) = getimagesize($this->basePath . '/len_std_256x256c.jpg');

        $this->assertEquals(256, $width);
        $this->assertEquals(256, $height);

        $this->assertEquals($file->getSize(), filesize($this->basePath . '/len_std_256x256c.jpg'));
    }

    public function testGetFittingImage()
    {
        $fileManager = $this->fileManager;

        $file = $fileManager->getFile('len_std.jpg');

        $file = $this->imageVersionCreator->getFittingImage($file, 'default', 512, 256);

        $this->assertInstanceOf('AnyContent\Client\File', $file);

        $this->assertEquals('http://www.phpunit.test/len_std_512x256f.jpg', $file->getUrl());

        list($width, $height) = getimagesize($this->basePath . '/len_std_512x256f.jpg');

        $this->assertEquals(256, $width);
        $this->assertEquals(256, $height);

        $this->assertNotEquals($file->getSize(), filesize($this->basePath . '/len_std_512x256f.jpg'));
    }

    public function testGetFittingImageKeepOriginal()
    {
        $this->imageVersionCreator->setKeepOriginalImageIfSizeIsTheSame(true);
        $fileManager = $this->fileManager;

        $file = $fileManager->getFile('len_std.jpg');

        $file = $this->imageVersionCreator->getFittingImage($file, 'default', 512, 256);

        $this->assertInstanceOf('AnyContent\Client\File', $file);

        $this->assertEquals('http://www.phpunit.test/len_std_512x256f.jpg', $file->getUrl());

        list($width, $height) = getimagesize($this->basePath . '/len_std_512x256f.jpg');

        $this->assertEquals(256, $width);
        $this->assertEquals(256, $height);

        $this->assertEquals($file->getSize(), filesize($this->basePath . '/len_std_512x256f.jpg'));
    }

    public function testResizeImageNoCrop()
    {
        $fileManager = $this->fileManager;

        $file = $fileManager->getFile('len_std.jpg');

        $file = $this->imageVersionCreator->getResizedImage($file, 'default', 256, 256, false);

        $this->assertInstanceOf('AnyContent\Client\File', $file);

        $this->assertEquals('http://www.phpunit.test/len_std_256x256r.jpg', $file->getUrl());

        list($width, $height) = getimagesize($this->basePath . '/len_std_256x256r.jpg');

        $this->assertEquals(256, $width);
        $this->assertEquals(256, $height);

        $this->assertNotEquals($file->getSize(), filesize($this->basePath . '/len_std_256x256r.jpg'));
    }

    public function testResizeImageNoCropKeepOriginal()
    {
        $this->imageVersionCreator->setKeepOriginalImageIfSizeIsTheSame(true);
        $fileManager = $this->fileManager;

        $file = $fileManager->getFile('len_std.jpg');

        $file = $this->imageVersionCreator->getResizedImage($file, 'default', 256, 256, false);

        $this->assertInstanceOf('AnyContent\Client\File', $file);

        $this->assertEquals('http://www.phpunit.test/len_std_256x256r.jpg', $file->getUrl());

        list($width, $height) = getimagesize($this->basePath . '/len_std_256x256r.jpg');

        $this->assertEquals(256, $width);
        $this->assertEquals(256, $height);

        $this->assertEquals($file->getSize(), filesize($this->basePath . '/len_std_256x256r.jpg'));
    }

    public function testScaleImage()
    {
        $fileManager = $this->fileManager;

        $file = $fileManager->getFile('len_std.jpg');

        $file = $this->imageVersionCreator->getScaledImage($file, 'default', 256);

        $this->assertInstanceOf('AnyContent\Client\File', $file);

        $this->assertEquals('http://www.phpunit.test/len_std_256x256s.jpg', $file->getUrl());

        list($width, $height) = getimagesize($this->basePath . '/len_std_256x256s.jpg');

        $this->assertEquals(256, $width);
        $this->assertEquals(256, $height);

        $this->assertNotEquals($file->getSize(), filesize($this->basePath . '/len_std_256x256s.jpg'));
    }

    public function testScaleImageKeepOriginal()
    {
        $this->imageVersionCreator->setKeepOriginalImageIfSizeIsTheSame(true);

        $fileManager = $this->fileManager;

        $file = $fileManager->getFile('len_std.jpg');

        $file = $this->imageVersionCreator->getScaledImage($file, 'default', 256);

        $this->assertInstanceOf('AnyContent\Client\File', $file);

        $this->assertEquals('http://www.phpunit.test/len_std_256x256s.jpg', $file->getUrl());

        list($width, $height) = getimagesize($this->basePath . '/len_std_256x256s.jpg');

        $this->assertEquals(256, $width);
        $this->assertEquals(256, $height);

        $this->assertEquals($file->getSize(), filesize($this->basePath . '/len_std_256x256s.jpg'));
    }
}
