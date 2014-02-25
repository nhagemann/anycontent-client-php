<?php

namespace AnyContent\Client;

use CMDL\Util;
use AnyContent\Client\File;

class Folder
{

    protected $path;
    protected $files = array();
    protected $subFolders = array();


    public function __construct($path, $result)
    {

        $path = trim($path, '/');

        $this->path = $path;
        foreach ($result['files'] as $file)
        {
            $this->files[$file['id']] = new File($this, $file['id'], $file['name'], $file['type'], $file['urls'], $file['size'], $file['timestamp_lastchange']);
            if ($file['type'] == 'image' AND array_key_exists('width', $file) AND array_key_exists('height', $file))
            {

                $this->files[$file['id']]->setWidth($file['width']);
                $this->files[$file['id']]->setHeight($file['height']);
            }
        }

        foreach ($result['folders'] as $folder)
        {
            $this->subFolders[$this->path . '/' . $folder] = $folder;
        }
    }


    public function getFiles()
    {
        return $this->files;
    }


    public function getFile($identifier)
    {
        if (array_key_exists($identifier, $this->files))
        {
            return $this->files[$identifier];
        }
        /** @var File $file */
        foreach ($this->files as $file)
        {
            if ($file->getName() == $identifier)
            {
                return $file;
            }
        }

        return false;
    }


    public function listSubFolders()
    {
        return $this->subFolders;
    }


    public function isEmpty()
    {
        if (count($this->files) > 0)
        {
            return false;
        }

        if (count($this->subFolders) > 0)
        {
            return false;
        }

        return true;
    }
}