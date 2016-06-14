<?php

namespace AnyContent\Client;

use AnyContent\AnyContentClientException;
use CMDL\ContentTypeDefinition;

class Record extends AbstractRecord implements \JsonSerializable
{

    public $id = null;

    /** @var ContentTypeDefinition */
    protected $dataTypeDefinition = null;

    protected $level = null;

    /** @var  UserInfo */
    public $creationUserInfo = null;

    /**
     * @var Repository
     */
    protected $repository;

    public function __construct(ContentTypeDefinition $contentTypeDefinition, $name, $view = 'default', $workspace = 'default', $language = 'default')
    {
        $this->dataTypeDefinition = $contentTypeDefinition;

        $this->setProperty('name', $name);
        $this->view      = $view;
        $this->workspace = $workspace;
        $this->language  = $language;
        $this->setCreationUserInfo(new UserInfo());
        $this->setLastChangeUserInfo(new UserInfo());

    }

    /**
     * @return Repository
     */
    public function getRepository()
    {
        if (!$this->repository)
        {
            throw new AnyContentClientException('Record does not know it\'s repository.');
        }
        return $this->repository;
    }

    /**
     * @param Repository $repository
     */
    public function setRepository($repository)
    {
        $this->repository = $repository;
    }




    public function getTable($property)
    {
        $values = json_decode($this->getProperty($property), true);

        if (!is_array($values))
        {
            $values = array();
        }

        $formElementDefinition = $this->dataTypeDefinition->getViewDefinition($this->view)
                                                          ->getFormElementDefinition($property);

        $columns = count($formElementDefinition->getList(1));

        $table = new Table($columns);

        foreach ($values as $row)
        {
            $table->addRow($row);
        }

        return $table;
    }


    public function getArrayProperty($property)
    {
        $value = $this->getProperty($property);
        if ($value)
        {
            return explode(',', $value);
        }

        return array();
    }


    public function getId()
    {
        return $this->id;
    }


    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }


    public function setName($value)
    {
        return $this->setProperty('name', $value);
    }


    public function getName()
    {
        return $this->getProperty('name');
    }


    public function getPosition()
    {
        return $this->getProperty('position');
    }


    public function setPosition($value)
    {
        if ($this->getParent() === null)
        {
            $this->setParent(0);
        }

        return $this->setProperty('position', $value);
    }


    public function getParent()
    {
        $parent = $this->getProperty('parent');
        if ($parent !== null)
        {
            return (int)$parent;
        }
    }


    public function setParent($value)
    {
        return $this->setProperty('parent', $value);
    }


    /**
     * @return null
     */
    public function getLevel()
    {
        return $this->level;
    }


    /**
     * @param null $level
     */
    public function setLevel($level)
    {
        $this->level = $level;
    }


    public function getDataType()
    {
        return 'content';
    }


    /**
     * @deprecated
     */
    public function getContentType()
    {
        return $this->dataTypeDefinition->getName();
    }


    public function getContentTypeName()
    {
        return $this->dataTypeDefinition->getName();
    }


    public function getContentTypeDefinition()
    {
        return $this->dataTypeDefinition;
    }


    public function getStatus()
    {
        return $this->getProperty('status');
    }


    public function getStatusLabel()
    {
        $statusList = $this->dataTypeDefinition->getStatusList();
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
        $subtypesList = $this->dataTypeDefinition->getSubtypes();
        if ($subtypesList)
        {
            if (array_key_exists($this->getProperty('subtype'), $subtypesList))
            {
                return $subtypesList[$this->getProperty('subtype')];
            }

        }

        return null;
    }


    public function setCreationUserInfo(UserInfo $creationUserInfo)
    {
        $this->creationUserInfo = clone $creationUserInfo;

        return $this;
    }


    public function getCreationUserInfo()
    {
        return $this->creationUserInfo;
    }

    public function getHash()
    {
        $properties = $this->getProperties();
        sort($properties);
        return md5(json_encode($properties,true));
    }

    function jsonSerialize()
    {
        $record                       = [ ];
        $record['id']                 = $this->getId();
        $record['properties']         = $this->getProperties();
        $record['info']               = [ ];
        $record['info']['revision']   = $this->getRevision();
        $record['info']['creation']   = $this->getCreationUserInfo();
        $record['info']['lastchange'] = $this->getLastChangeUserInfo();

        return $record;
    }
}