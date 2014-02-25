<?php

namespace AnyContent\Client;

use CMDL\CMDLParserException;
use CMDL\Util;

use CMDL\ConfigTypeDefinition;
use AnyContent\Client\Sequence;

class Config
{

    public $id = null;

    protected $configTypeDefinition = null;

    protected $workspace = 'default';
    protected $language = 'default';

    public $properties = array();

    public $revision = 1;
    public $revisionTimestamp = null;

    public $hash = null;

    public $lastChangeUserInfo;


    public function __construct(ConfigTypeDefinition $configTypeDefinition, $workspace = 'default', $language = 'default')
    {
        $this->configTypeDefinition = $configTypeDefinition;

        $this->workspace = $workspace;
        $this->language  = $language;

    }


    public function setProperty($property, $value)
    {

        $property = Util::generateValidIdentifier($property);
        if ($this->configTypeDefinition->hasProperty($property))
        {
            $this->properties[$property] = $value;
            $this->hash                  = null;
            $this->revisionTimestamp     = null;
        }
        else
        {
            throw new CMDLParserException('Unknown property ' . $property, CMDLParserException::CMDL_UNKNOWN_PROPERTY);
        }

    }


    public function getProperty($property, $default = null)
    {
        if (array_key_exists($property, $this->properties))
        {
            return $this->properties[$property];
        }
        else
        {
            return $default;
        }
    }


    public function getSequence($property)
    {
        $values = json_decode($this->getProperty($property), true);

        if (!is_array($values))
        {
            $values = array();
        }

        return new Sequence($this->configTypeDefinition, $values);
    }


    public function getID()
    {
        return $this->id;
    }


    public function setID($id)
    {
        $this->id = $id;
    }


    public function setHash($hash)
    {
        $this->hash = $hash;
    }


    public function getHash()
    {
        return $this->hash;
    }


    public function getConfigType()
    {
        return $this->configTypeDefinition->getName();
    }


    public function getConfigTypeDefinition()
    {
        return $this->configTypeDefinition;
    }


    public function setRevision($revision)
    {
        $this->revision = $revision;
    }


    public function getRevision()
    {
        return $this->revision;
    }


    public function setRevisionTimestamp($revisionTimestamp)
    {
        $this->revisionTimestamp = $revisionTimestamp;
    }


    public function getRevisionTimestamp()
    {
        return $this->revisionTimestamp;
    }


    public function setLastChangeUserInfo($lastChangeUserInfo)
    {
        $this->lastChangeUserInfo = $lastChangeUserInfo;
    }


    public function getLastChangeUserInfo()
    {
        return $this->lastChangeUserInfo;
    }


    public function setLanguage($language)
    {
        $this->language = $language;
    }


    public function getLanguage()
    {
        return $this->language;
    }


    public function setWorkspace($workspace)
    {
        $this->workspace = $workspace;
    }


    public function getWorkspace()
    {
        return $this->workspace;
    }


    public function setProperties($properties)
    {
        $this->properties = $properties;
    }


    public function getProperties()
    {
        return $this->properties;
    }

}