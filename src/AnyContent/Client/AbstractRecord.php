<?php

namespace AnyContent\Client;

use CMDL\CMDLParserException;
use CMDL\DataTypeDefinition;
use CMDL\Util;

abstract class AbstractRecord
{

    /** @var  DataTypeDefinition */
    protected $dataTypeDefinition;

    protected $view = 'default';
    protected $workspace = 'default';
    protected $language = 'default';

    public $properties = array();

    public $revision = 0;

    /** @var UserInfo */
    public $lastChangeUserInfo = null;


    public function getDataTypeName()
    {
        return $this->dataTypeDefinition->getName();
    }


    public function getDataTypeDefinition()
    {
        return $this->dataTypeDefinition;
    }


    public function setRevision($revision)
    {
        $this->revision = $revision;
    }


    public function getRevision()
    {
        return $this->revision;
    }


    public function hasProperty($property, $viewName = null)
    {
        return $this->dataTypeDefinition->hasProperty($property, $viewName);
    }


    public function getProperty($property, $default = null)
    {
        if (array_key_exists($property, $this->properties)) {
            if ($this->properties[$property] === '') {
                return $default;
            }
            if ($this->properties[$property] === null) {
                return $default;
            }

            return $this->properties[$property];
        } else {
            return $default;
        }
    }

    public function getIntProperty($property, $default = null)
    {
        return (int)$this->getProperty($property, $default);
    }


    public function setProperty($property, $value)
    {

        $property = Util::generateValidIdentifier($property);
        if ($this->dataTypeDefinition->hasProperty($property, $this->view)) {
            $this->properties[$property] = $value;
        } else {
            throw new CMDLParserException('Unknown property ' . $property, CMDLParserException::CMDL_UNKNOWN_PROPERTY);
        }

        return $this;
    }


    public function getSequence($property)
    {
        $values = json_decode($this->getProperty($property), true);

        if (!is_array($values)) {
            $values = array();
        }

        return new Sequence($this->dataTypeDefinition, $values);
    }


    public function setProperties($properties)
    {
        $this->properties = $properties;

        return $this;
    }


    public function getProperties()
    {
        return $this->properties;
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
        if ($this->lastChangeUserInfo == null) {
            $this->lastChangeUserInfo = new UserInfo();
        }

        return $this->lastChangeUserInfo;
    }
}