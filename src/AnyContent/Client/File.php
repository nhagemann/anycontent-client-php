<?php

namespace AnyContent\Client;

use CMDL\Util;

class File
{

    protected $folder;

    protected $id;
    protected $name;
    protected $type;
    protected $urlGet;
    protected $urlHref;
    protected $size;
    protected $timestampLastChange;

    protected $width = null;
    protected $height = null;


    public function __construct($folder, $id, $name, $type = 'binary', $urlGet = null, $urlHref = null, $size = null, $timestampLastchange = null)
    {

        $this->folder              = $folder;
        $this->id                  = $id;
        $this->name                = $name;
        $this->type                = $type;
        $this->urlGet              = $urlGet;
        $this->urlHref             = $urlHref;
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


    public function getUrlGet()
    {
        return $this->urlGet;
    }


    public function getUrlHref()
    {
        return $this->urlHref;
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
        if ($this->type=='image' AND $this->width!=null)
        {
            return true;
        }
        return false;
    }
}