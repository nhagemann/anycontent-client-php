<?php

namespace AnyContent\Client;

use AnyContent\Client\Traits\Properties;
use CMDL\CMDLParserException;
use CMDL\DataTypeDefinition;
use CMDL\Util;

abstract class AbstractRecord
{

    use Properties;

    protected $view = 'default';
    protected $workspace = 'default';
    protected $language = 'default';

    public $revision = 0;

    /** @var UserInfo */
    public $lastChangeUserInfo = null;


    /**
     * Check if a property is allowed by definition
     *
     * @param      $property
     * @param null $viewName
     *
     * @return bool
     */
    public function hasProperty($property, $viewName = null)
    {
        return $this->dataTypeDefinition->hasProperty($property, $viewName);
    }


    public function setRevision($revision)
    {
        $this->revision = $revision;
    }


    public function getRevision()
    {
        return $this->revision;
    }


    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }


    public function getLanguage()
    {
        return $this->language;
    }


    public function setWorkspace($workspace)
    {
        $this->workspace = $workspace;

        return $this;
    }


    public function getWorkspace()
    {
        return $this->workspace;
    }


    public function setViewName($view)
    {
        $this->view = $view;

        return $this;
    }


    public function getViewName()
    {
        return $this->view;
    }


    public function setLastChangeUserInfo(UserInfo $lastChangeUserInfo)
    {
        $this->lastChangeUserInfo = clone $lastChangeUserInfo;

        return $this;
    }


    public function getLastChangeUserInfo()
    {
        if ($this->lastChangeUserInfo == null)
        {
            $this->lastChangeUserInfo = new UserInfo();
        }

        return $this->lastChangeUserInfo;
    }
}