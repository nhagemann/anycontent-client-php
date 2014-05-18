<?php

namespace AnyContent\Client;

use CMDL\Util;

class File
{

    protected $folder;

    protected $id;
    protected $name;
    protected $type;
    protected $size;
    protected $timestampLastChange;

    protected $width = null;
    protected $height = null;


    public function __construct($folder, $id, $name, $type = 'binary', $urls, $size = null, $timestampLastchange = null)
    {

        $this->folder              = $folder;
        $this->id                  = $id;
        $this->name                = $name;
        $this->type                = $type;
        $this->urls                = $urls;
        $this->size                = $size;
        $this->timestampLastChange = $timestampLastchange;

    }


    public function getFolder()
    {
        return $this->folder;
    }


    public function getId()
    {
        return $this->id;
    }


    public function getName()
    {
        return $this->name;
    }


    public function getSize()
    {
        return $this->size;
    }


    public function getTimestampLastChange()
    {
        return $this->timestampLastChange;
    }


    public function getType()
    {
        return $this->type;
    }


    public function setHeight($height)
    {
        $this->height = $height;
    }


    public function getHeight()
    {
        return $this->height;
    }


    public function setWidth($width)
    {
        $this->width = $width;
    }


    public function getWidth()
    {
        return $this->width;
    }


    public function isImage()
    {
        if ($this->type == 'image')
        {
            return true;
        }

        return false;
    }


    public function getUrl($type = 'default', $fallback = false)
    {
        if (array_key_exists($type, $this->urls))
        {
            return $this->urls[$type];
        }
        if ($type != 'default' AND $fallback == true)
        {
            return $this->getUrl('default');
        }

        return false;
    }


    public function getUrls()
    {
        return $this->urls;
    }


    public function addUrl($type, $url)
    {
        $this->urls[$type] = $url;
    }


    public function removeUrl($type)
    {
        if (array_key_exists($type, $this->urls))
        {
            unset($this->urls[$type]);
        }
    }


    public function hasPublicUrl()
    {
        return (boolean)$this->getUrl('default');
    }

}