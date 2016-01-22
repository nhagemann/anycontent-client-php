<?php

namespace AnyContent\Repository\Modules\StorageAdapter\S3;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use Aws\S3\S3Client;

class S3StorageAdapter
{

    /**
     * @var Filesystem null
     */
    protected $filesystem = null;

    protected $scheme = null;

    protected $client = null;

    protected $bucketname = null;

    protected $baseFolder = null;

    protected $baseUrl = null;

    protected $imagesize = false;


    public function __construct($config, $baseFolder, $options = array())
    {
        $this->filesystem = new Filesystem();

        // Create an Amazon S3 client object
        $this->client = S3Client::factory(array( 'key' => $config['key'], 'secret' => $config['secret'] ));

        $this->bucketname = $config['bucketname'];

        $this->baseFolder = $baseFolder;

        // Register the stream wrapper from a client object
        $this->client->registerStreamWrapper();

        $this->scheme = 's3://' . $this->bucketname;

        if (isset($config['region']))
        {
            $this->client->setRegion($config['region']);
        }

        if (file_exists($this->scheme))
        {
            $this->scheme .= '/' . $baseFolder;
            if (!file_exists($this->scheme))
            {
                $this->filesystem->mkdir($this->scheme);
            }
        }
        else
        {
            throw new \Exception ('Bucket ' . $this->bucketname . ' missing.');
        }

        if (isset($config['url']))
        {
            $this->baseUrl = trim($config['url'], '/') . '/' . $this->baseFolder;
        }

        if (isset($config['imagesize']))
        {
            $this->imagesize = (boolean)$config['imagesize'];
        }
    }


    public function getFolders($path)
    {
        $path    = trim($path, '/');
        $folders = array();
        $finder  = new Finder();

        $finder->depth('==0');

        try
        {
            /* @var $file \SplFileInfo */
            foreach ($finder->in($this->scheme . '/' . $path) as $file)
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


    public function getFiles($path)
    {

        $path = trim($path, '/');

        $files  = array();
        $finder = new Finder();

        $finder->depth('==0');

        try
        {
            /* @var $file \SplFileInfo */
            foreach ($finder->in($this->scheme . '/' . $path) as $file)
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

                    if ($this->baseUrl != null)
                    {
                        $item['urls']['default'] = $this->baseUrl . '/' . $item['id'];
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


    public function getFile($id)
    {

        $id = trim($id, '/');

        $fileName = pathinfo($id, PATHINFO_FILENAME);

        if ($fileName != '') // No access to .xxx-files
        {
            if (file_exists($this->scheme . '/' . $id))
            {
                return @file_get_contents($this->scheme . '/' . $id);
            }

        }

        return false;

    }


    public function saveFile($id, $binary)
    {
        $id       = trim($id, '/');
        $fileName = pathinfo($id, PATHINFO_FILENAME);

        if ($fileName != '') // No writing of .xxx-files
        {
            $mimeTypeRepository = new \Dflydev\ApacheMimeTypes\JsonRepository;
            $contentType        = $mimeTypeRepository->findType(pathinfo($id, PATHINFO_EXTENSION));

            if (!$contentType)
            {
                $contentType = 'binary/octet-stream';
            }
            try
            {
                $this->client->putObject(array(
                                             'Bucket'      => $this->bucketname,
                                             'Key'         => $this->baseFolder . '/' . $id,
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


    public function deleteFile($id)
    {
        try
        {
            if ($this->filesystem->exists($this->scheme . '/' . $id))
            {
                $this->filesystem->remove($this->scheme . '/' . $id);

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
        $path = trim($path, '/');

        return $this->filesystem->mkdir($this->scheme . '/' . $path . '/');
    }


    public function deleteFolder($path)
    {

        $path = trim($path, '/');
        $path = $this->baseFolder . '/' . $path;
        $path = trim($path, '/');

        $nr = $this->client->deleteMatchingObjects($this->bucketname, $path);

        if ($nr > 1)
        {
            return true;
        }

        return false;
    }
}