<?php

namespace AnyContent\Client;

use CMDL\CMDLParserException;
use CMDL\Util;

use CMDL\ContentTypeDefinition;

use AnyContent\Client\UserInfo;

class Record
{

    public $id = null;

    protected $contentTypeDefinition = null;

    protected $clipping = 'default';
    protected $workspace = 'default';
    protected $language = 'none';

    public $properties = array();

    public $revision = 1;

    public $creationUserInfo;
    public $lastChangeUserInfo;


    public function __construct(ContentTypeDefinition $contentTypeDefinition, $name, $clipping = 'default', $workspace = 'default', $language = 'none')
    {
        $this->contentTypeDefinition = $contentTypeDefinition;

        $this->setProperty('name', $name);
        $this->clipping  = $clipping;
        $this->workspace = $workspace;
        $this->language  = $language;

    }


    public function setProperty($property, $value)
    {
        $property = Util::generateValidIdentifier($property);
        if ($this->contentTypeDefinition->hasProperty($property, $this->clipping))
        {
            $this->properties[$property] = $value;
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


    public function getID()
    {
        return $this->id;
    }


    public function setID($id)
    {
        $this->id = $id;
    }


    public function getName()
    {
        return $this->getProperty('name');
    }


    public function getContentType()
    {
        return $this->contentTypeDefinition->getName();
    }


    public function setRevision($revision)
    {
        $this->revision = $revision;
    }


    public function getRevision()
    {
        return $this->revision;
    }


    public function getStatus()
    {
        return $this->getProperty('status');
    }


    public function getStatusLabel()
    {
        $statusList = $this->contentTypeDefinition->getStatusList();
        if ($statusList)
        {
            if (array_key_exists($this->getProperty('status'), $statusList))
            {
                return $statusList[$this->getProperty('status')];
            }

        }

        return null;
    }


    public function getSubtype()
    {
        return $this->getProperty('subtype');
    }


    public function getSubtypeLabel()
    {
        $subtypesList = $this->contentTypeDefinition->getSubtypes();
        if ($subtypesList)
        {
            if (array_key_exists($this->getProperty('subtype'), $subtypesList))
            {
                return $subtypesList[$this->getProperty('subtype')];
            }

        }

        return null;
    }


    public function setLastChangeUserInfo($lastChangeUserInfo)
    {
        $this->lastChangeUserInfo = $lastChangeUserInfo;
    }


    public function getLastChangeUserInfo()
    {
        return $this->lastChangeUserInfo;
    }


    public function setCreationUserInfo($creationUserInfo)
    {
        $this->creationUserInfo = $creationUserInfo;
    }


    public function getCreationUserInfo()
    {
        return $this->creationUserInfo;
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


    public function setClippingName($clipping)
    {
        $this->clipping = $clipping;
    }


    public function getClippingName()
    {
        return $this->clipping;
    }


}