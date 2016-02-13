<?php

namespace AnyContent\Connection\FileManager;

use AnyContent\Client\File;
use AnyContent\Client\Folder;
use AnyContent\Connection\Interfaces\FileManager;
use Dflydev\ApacheMimeTypes\JsonRepository;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use Aws\S3\S3Client;

class S3FilesAccess implements FileManager
{

    /** @var S3Client */
    protected $client;

    /**
     * @var Filesystem null
     */
    protected $filesystem = null;

    protected $scheme = null;

    protected $key = null;

    protected $secret = null;

    protected $region = null;

    protected $bucketName = null;

    protected $baseFolder = null;

    protected $imagesize = false;

    protected $publicUrl = false;


    /**
     * @return S3Client
     */
    public function connect()
    {
        if (!$this->client)
        {
            // Create an Amazon S3 client object
            $this->client = S3Client::factory(array( 'key' => $this->key, 'secret' => $this->secret ));


            if ($this->region)
            {
                $this->client->setRegion($this->region);
            }

            // Register the stream wrapper from a client object
            $this->client->registerStreamWrapper();

            $this->scheme = 's3://' . $this->bucketName;

            if (file_exists($this->scheme))
            {
                $this->scheme .= '/' . $this->baseFolder;
                if (!file_exists($this->scheme))
                {
                    $this->filesystem->mkdir($this->scheme);
                }
            }
            else
            {
                throw new \Exception ('Bucket ' . $this->bucketName . ' missing.');
            }
        }



        return $this->client;
    }


    public function __construct($key, $secret, $bucketName, $baseFolder = '', $region = false)
    {

        $this->filesystem = new Filesystem();

        $this->key = $key;

        $this->secret = $secret;

        $this->region = $region;

        $this->bucketName = $bucketName;

        $this->baseFolder = $baseFolder;



    }


    public function enableImageSizeCalculation()
    {
        $this->imagesize = true;

        return $this;
    }


    public function disableImageSizeCalculation()
    {
        $this->imagesize = true;

        return $this;
    }


    /**
     * @return boolean
     */
    public function getPublicUrl()
    {
        return $this->publicUrl;
    }


    /**
     * @param boolean $publicUrl
     */
    public function setPublicUrl($publicUrl)
    {
        $this->publicUrl = $publicUrl;

    }


    /**
     * @param string $path
     *
     * @return Folder|bool
     */
    public function getFolder($path = '')
    {
        $this->connect();

        $path = trim($path, '/');

        if (file_exists($this->scheme . '/' . $path))
        {

            $result = [ 'folders' => $this->listSubFolder($path), 'files' => $this->listFiles($path) ];

            $folder = new Folder($path, $result);

            return $folder;
        }

        return false;
    }


    /**
     * @param $id
     *
     * @return  File|bool
     */
    public function getFile($fileId)
    {
        $this->connect();

        $fileId = trim(trim($fileId, '/'));
        if ($fileId != '')
        {
            if (strpos($fileId, '/') === false)
            {
                $folder = $this->getFolder();
            }
            else
            {
                $pathinfo = pathinfo($fileId);
                $folder   = $this->getFolder($pathinfo['dirname']);
            }
            if ($folder)
            {
                return $folder->getFile($fileId);
            }
        }

        return false;
    }


    public function getBinary(File $file)
    {
        $this->connect();

        if (file_exists($this->scheme . '/' . $file->getId()))
        {
            return @file_get_contents($this->scheme . '/' . $file->getId());
        }

        return false;

    }


    public function saveFile($fileId, $binary)
    {
        $client = $this->connect();

        $fileId   = trim($fileId, '/');
        $fileName = pathinfo($fileId, PATHINFO_FILENAME);

        if ($fileName != '') // No writing of .xxx-files
        {
            $mimeTypeRepository = new JsonRepository();
            $contentType        = $mimeTypeRepository->findType(pathinfo($fileId, PATHINFO_EXTENSION));

            if (!$contentType)
            {
                $contentType = 'binary/octet-stream';
            }
            try
            {
                $client->putObject(array(
                                       'Bucket'      => $this->bucketName,
                                       'Key'         => $this->baseFolder . '/' . $fileId,
                                       'Body'        => $binary,
                                       'ACL'         => 'public-read',
                                       'ContentType' => $contentType
                                   ));

                return true;
            }
            catch (\Exception $e)
            {

            }

        }

        return false;
    }


    public function deleteFile($fileId, $deleteEmptyFolder = true)
    {
        $this->connect();

        try
        {
            if ($this->filesystem->exists($this->scheme . '/' . $fileId))
            {
                $this->filesystem->remove($this->scheme . '/' . $fileId);

                if ($deleteEmptyFolder)
                {
                    $this->deleteFolder(pathinfo($fileId, PATHINFO_DIRNAME));
                }

                return true;
            }
        }
        catch (\Exception $e)
        {

        }

        return false;

    }


    public function createFolder($path)
    {
        $this->connect();

        $path = trim($path, '/');

        $this->filesystem->mkdir($this->scheme . '/' . $path . '/');

        return true;
    }


    public function deleteFolder($path, $deleteIfNotEmpty = false)
    {
        $client = $this->connect();

        $path = trim($path, '/');

        $folder = $this->getFolder($path);

        if ($folder)
        {
            if (count($folder->getFiles()) > 0 && $deleteIfNotEmpty == false)
            {
                return false;
            }

            $path = $this->baseFolder . '/' . $path;
            $path = trim($path, '/');

            $nr = $client->deleteMatchingObjects($this->bucketName, $path);

            if ($nr > 1)
            {
                return true;
            }
        }

        return false;
    }


    protected function listSubFolder($path)
    {
        $this->connect();

        if ($path != '')
        {
            $path = $this->scheme . '/' . trim($path, '/');
        }
        else
        {
            $path = $this->scheme;
        }
        $folders = array();
        $finder  = new Finder();

        $finder->depth('==0');

        try
        {
            /* @var $file \SplFileInfo */
            foreach ($finder->in($path) as $file)
            {

                if ($file->isDir())
                {

                    $folders[] = $file->getFilename();

                }
            }

        }
        catch (\Exception $e)
        {

            return false;
        }

        return $folders;

    }


    protected function listFiles($path)
    {
        $this->connect();

        if ($path != '')
        {
            $dir = $this->scheme . '/' . trim($path, '/');
        }
        else
        {
            $dir = $this->scheme;
        }

        $files  = array();
        $finder = new Finder();

        $finder->depth('==0');

        try
        {
            /* @var $file \SplFileInfo */
            foreach ($finder->in($dir) as $file)
            {

                if (!$file->isDir())
                {
                    $item         = array();
                    $item['id']   = trim($path . '/' . $file->getFilename(), '/');
                    $item['name'] = $file->getFilename();
                    $item['urls'] = array();
                    $item['type'] = 'binary';
                    $item['size'] = $file->getSize();

                    $extension = strtolower($extension = pathinfo($file->getFilename(), PATHINFO_EXTENSION)); // To be compatible with some older PHP 5.3 versions

                    if (in_array($extension, array( 'gif', 'png', 'jpg', 'jpeg' )))
                    {
                        $item['type'] = 'image';
                        if ($this->imagesize == true)
                        {

                            $content = $file->getContents();

                            if (function_exists('imagecreatefromstring'))
                            {
                                $image = @imagecreatefromstring($content);
                                if ($image)
                                {

                                    $item['width']  = imagesx($image);
                                    $item['height'] = imagesy($image);
                                }
                            }
                        }

                    }
                    $item['timestamp_lastchange'] = $file->getMTime();

                    if ($this->publicUrl != false)
                    {
                        $item['url'] = $this->publicUrl . '/' . $item['id'];
                    }

                    $files[$file->getFilename()] = $item;
                }

            }
        }
        catch (\Exception $e)
        {
            return false;
        }

        return $files;
    }

}