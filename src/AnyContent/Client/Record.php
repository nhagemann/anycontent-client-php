<?php

namespace AnyContent\Client;

use CMDL\CMDLParserException;
use CMDL\Util;

use CMDL\ContentTypeDefinition;
use AnyContent\Client\Sequence;

class Record
{

    public $id = null;

    protected $contentTypeDefinition = null;

    protected $view = 'default';
    protected $workspace = 'default';
    protected $language = 'default';

    public $properties = array();

    public $revision = 1;
    public $revisionTimestamp = null;

    public $hash = null;

    public $position = null;
    public $parentRecordId = null;
    public $levelWithinSortedTree = null;

    public $creationUserInfo;
    public $lastChangeUserInfo;


    public function __construct(ContentTypeDefinition $contentTypeDefinition, $name, $view = 'default', $workspace = 'default', $language = 'default')
    {
        $this->contentTypeDefinition = $contentTypeDefinition;

        $this->setProperty('name', $name);
        $this->view  = $view;
        $this->workspace = $workspace;
        $this->language  = $language;

    }


    public function setProperty($property, $value)
    {

        $property = Util::generateValidIdentifier($property);
        if ($this->contentTypeDefinition->hasProperty($property, $this->view))
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

        return new Sequence($this->contentTypeDefinition, $values);
    }


    public function getArrayProperty($property)
    {
        $value = $this->getProperty($property);
        if ($value)
        {
            return explode(',',$value);
        }
        return array();
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


    public function setHash($hash)
    {
        $this->hash = $hash;
    }


    public function getHash()
    {
        return $this->hash;
    }


    public function getContentType()
    {
        return $this->contentTypeDefinition->getName();
    }


    public function getContentTypeDefinition()
    {
        return $this->contentTypeDefinition;
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


    public function setViewName($view)
    {
        $this->view = $view;
    }


    public function getViewName()
    {
        return $this->view;
    }


    public function setPosition($position)
    {
        $this->position = $position;
    }


    public function getPosition()
    {
        return $this->position;
    }


    public function setParentRecordId($parentRecordId)
    {
        $this->parentRecordId = $parentRecordId;
    }


    public function getParentRecordId()
    {
        return $this->parentRecordId;
    }


    public function setLevelWithinSortedTree($levelWithinSortedTree)
    {
        $this->levelWithinSortedTree = $levelWithinSortedTree;
    }


    public function getLevelWithinSortedTree()
    {
        return $this->levelWithinSortedTree;
    }


    public function setProperties($properties)
    {
        $this->properties = $properties;
    }


    public function getProperties()
    {
        return $this->properties;
    }

    public function getAttributes()
    {
        return array('workspace'=>$this->getWorkspace(),'language'=>$this->getLanguage(),'position'=>$this->getPosition(),'parent_id'=>$this->getParentRecordId(),'level'=>$this->getLevelWithinSortedTree());
    }
}