<?php

namespace AnyContent\Client;

use CMDL\CMDLParserException;
use CMDL\Util;

use CMDL\ContentTypeDefinition;

class Record
{

    public $id = null;

    protected $contentTypeDefinition = null;

    protected $clipping = 'default';
    protected $workspace = 'default';
    protected $language = 'none';

    public $properties = array();

    public $revision = 1;


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


    /*
    public function hasSubtypes()
    {
        return AnyContent_Manager::hasSubtypes($this->content_type);

    }  */

    /*
    public function getSubtype()
    {
        //var_dump($this->label);
        $items = AnyContent_Manager::getPossibleSubtypes($this->content_type);
        //var_dump($this->subtype);
        if (array_key_exists($this->getProperty('subtype'), $items))
        {
            return $items[$this->getProperty('subtype')]['name'];
        }

        //var_dump($labels);
        return false;
    } */

    /*
    public function getSubtypeColor($default = false)
    {
        //var_dump($this->label);
        $items = AnyContent_Manager::getPossibleSubtypes($this->content_type);
        if (array_key_exists($this->getProperty('subtype'), $items))
        {
            return $items[$this->getProperty('subtype')]['color'];
        }

        //var_dump($labels);
        return $default;
    }


    public function hasStatus()
    {
        return AnyContent_Manager::hasStatus($this->content_type);
    }


    public function getStatus()
    {
        //var_dump($this->label);
        $items = AnyContent_Manager::getPossibleStates($this->content_type);
        if (array_key_exists($this->getProperty('status'), $items))
        {
            return $items[$this->getProperty('status')]['name'];
        }

        //var_dump($labels);
        return false;
    }


    public function getStatusColor($default = false)
    {
        //var_dump($this->label);
        $items = AnyContent_Manager::getPossibleStates($this->content_type);
        if (array_key_exists($this->getProperty('status'), $items))
        {
            return $items[$this->getProperty('status')]['color'];
        }

        //var_dump($labels);
        return $default;
    }




    public function save()
    {
        return AnyContent_Manager::saveRecord($this);
    }


    public function delete()
    {
        if ($this->id)
        {
            return AnyContent_Manager::deleteRecord($this->content_type, $this->id);
        }
    } */
}