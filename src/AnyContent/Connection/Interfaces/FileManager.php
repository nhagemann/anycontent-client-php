<?php

declare(strict_types=1);

namespace AnyContent\Connection\Interfaces;

use AnyContent\Client\File;
use AnyContent\Client\Folder;

interface FileManager
{
    /**
     * @param string $path
     *
     * @return Folder|bool
     */
    public function getFolder($path = '');

    /**
     * @param $id
     *
     * @return  File|bool
     */
    public function getFile($fileId);

    /**
     * @param File $file
     * @return File|bool
     */
    public function getBinary(File $file);

    public function saveFile($fileId, $binary);

    public function deleteFile($fileId, $deleteEmptyFolder = true);

    public function createFolder($path);

    public function deleteFolder($path, $deleteIfNotEmpty = false);

    public function getPublicUrl();

    public function setPublicUrl($publicUrl);
}
