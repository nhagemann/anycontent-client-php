<?php

declare(strict_types=1);

namespace AnyContent\Connection\FileManager;

use AnyContent\Client\Folder;
use AnyContent\Connection\Interfaces\FileManager;
use Dflydev\ApacheMimeTypes\JsonRepository;

class S3PPFilesAccess extends S3FilesAccess implements FileManager
{
    /**
     * @param string $path
     *
     * @return Folder|bool
     */
    public function getFolder($path = '')
    {
        $this->connect();

        $path = trim(trim($path, '/'));

        if ($this->isRootPath($path)) {
            $data            = [ ];
            $data['files']   = [ ];
            $data['folders'] = ['Public', 'Protected'];
            return new Folder('', $data);
        }

        if (!$this->isValidPath($path)) {
            return false;
        }

        $folder = parent::getFolder($path);

        if (!$folder && strpos($path, '/') === false) { // Public or Protected folder
            $data            = [ ];
            $data['files']   = [ ];
            $data['folders'] = [ ];
            return new Folder('', $data);
        }

        return $folder;
    }

    public function saveFile($fileId, $binary)
    {
        $client = $this->connect();

        $fileId   = trim($fileId, '/');
        $fileName = pathinfo($fileId, PATHINFO_FILENAME);

        if ($fileName != '') { // No writing of .xxx-files
            $mimeTypeRepository = new JsonRepository();
            $contentType        = $mimeTypeRepository->findType(pathinfo($fileId, PATHINFO_EXTENSION));

            if (!$contentType) {
                $contentType = 'binary/octet-stream';
            }

            $acl = 'private';
            if ($this->isPublicPath($fileId)) {
                $acl = 'public-read';
            }

            try {
                $client->putObject([
                                       'Bucket'      => $this->bucketName,
                                       'Key'         => $this->baseFolder . '/' . $fileId,
                                       'Body'        => $binary,
                                       'ACL'         => $acl,
                                       'ContentType' => $contentType,
                                   ]);

                return true;
            } catch (\Exception $e) {
            }
        }

        return false;
    }

    protected function isRootPath($path)
    {
        if ($path == '') {
            return true;
        }

        return false;
    }

    protected function isValidPath($path)
    {
        $path   = trim($path, '/');
        $tokens = explode('/', $path);

        if (in_array($tokens[0], ['Public', 'Protected'])) {
            return true;
        }

        return false;
    }

    protected function isPublicPath($path)
    {
        $tokens = explode('/', $path);

        if ($tokens[0] == 'Public') {
            return true;
        }

        return false;
    }

    protected function listFiles($path)
    {
        $this->connect();

        $items = parent::listFiles($path);

        if (!$this->isPublicPath($path)) {
            foreach ($items as &$item) {
                unset($item['url']);
            }
        }

        return $items;
    }
}
