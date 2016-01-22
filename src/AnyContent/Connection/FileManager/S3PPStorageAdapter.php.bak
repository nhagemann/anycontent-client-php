<?php

namespace AnyContent\Repository\Modules\StorageAdapter\S3;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use AnyContent\Repository\Modules\StorageAdapter\S3\S3StorageAdapter;

use Aws\S3\S3Client;

class S3PPStorageAdapter extends S3StorageAdapter
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


    public function getFolders($path)
    {
        $path = trim(trim($path, '/'));

        if ($this->isRootPath($path))
        {
            return array( 'Public', 'Protected' );
        }

        if (!$this->isValidPath($path))
        {
            return false;
        }

        $folders = parent::getFolders($path);

        if (!$folders)
        {
            if ($path == 'Public' || $path == 'Protected')
            {
                return array();
            }
        }

        return $folders;
    }


    public function getFiles($path)
    {
        $path = trim(trim($path, '/'));

        if ($this->isRootPath($path))
        {
            return array();
        }

        if (!$this->isValidPath($path))
        {
            return false;
        }

        $public = false;

        if ($this->isPublicPath($path))
        {
            $public = true;
        }

        $files = parent::getFiles($path);

        if (!$files)
        {
            if ($path == 'Public' || $path == 'Protected')
            {
                return array();
            }
        }
        else
        {
            $items = array();
            foreach ($files as $file)
            {
                if (!$public)
                {
                    $file['urls'] = array();
                }
                $items[] = $file;
            }

            return $items;
        }

        return $files;

    }


    public function getFile($id)
    {
        $id = trim($id, '/');

        if (strpos($id, '/') === false)
        {
            return false;
        }

        return parent::getFile($id);

    }


    public function saveFile($id, $binary)
    {
        $id = trim($id, '/');

        if (strpos($id, '/') === false)
        {
            return false;
        }

        $acl = 'private';
        if ($this->isPublicPath($id))
        {
            $acl = 'public-read';
        }

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
                                             'ACL'         => $acl,
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
        if (!$this->isValidPath($id))
        {
            return false;
        }

        return parent::deleteFile($id);
    }


    public function createFolder($path)
    {
        if (!$this->isValidPath($path))
        {
            return false;
        }

        return parent::createFolder($path);
    }


    public function deleteFolder($path)
    {
        if (!$this->isValidPath($path))
        {
            return false;
        }

        return parent::deleteFolder($path);
    }


    protected function isRootPath($path)
    {
        $path = trim(trim($path, '/'));

        if ($path == '')
        {
            return true;
        }

        return false;
    }


    protected function isValidPath($path)
    {
        $tokens = explode('/', $path);

        if (in_array($tokens[0], array( 'Public', 'Protected' )))
        {
            return true;
        }

        return false;
    }


    protected function isPublicPath($path)
    {
        $tokens = explode('/', $path);

        if ($tokens[0] == 'Public')
        {
            return true;
        }

        return false;
    }
}