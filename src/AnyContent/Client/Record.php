<?php

declare(strict_types=1);

namespace AnyContent\Client;

use CMDL\ContentTypeDefinition;

class Record extends AbstractRecord implements \JsonSerializable
{
    public $id = null;

    protected $level = null;

    protected $isADeletedRevision = false;

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

    public function getId()
    {
        return $this->id;
    }

    public function setId(mixed $id)
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
        $position = $this->getProperty('position');
        if ($position !== null) {
            return (int)$position;
        }
        return null;
    }

    public function setPosition($value)
    {
        if ($this->getParent() === null) {
            $this->setParent(0);
        }

        return $this->setProperty('position', $value);
    }

    public function getParent()
    {
        $parent = $this->getProperty('parent');
        if ($parent !== null) {
            return (int)$parent;
        }
        return null;
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
     * @param $level
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

    public function getContentTypeDefinition(): ContentTypeDefinition
    {
        assert($this->dataTypeDefinition instanceof ContentTypeDefinition);
        return $this->dataTypeDefinition;
    }

    public function getStatus()
    {
        assert($this->dataTypeDefinition instanceof ContentTypeDefinition);

        return $this->getProperty('status');
    }

    public function getStatusLabel()
    {
        assert($this->dataTypeDefinition instanceof ContentTypeDefinition);

        $statusList = $this->dataTypeDefinition->getStatusList();
        if ($statusList) {
            if (array_key_exists($this->getProperty('status'), $statusList)) {
                return $statusList[$this->getProperty('status')];
            }
        }

        return null;
    }

    public function getSubtype()
    {
        assert($this->dataTypeDefinition instanceof ContentTypeDefinition);

        return $this->getProperty('subtype');
    }

    public function getSubtypeLabel()
    {
        assert($this->dataTypeDefinition instanceof ContentTypeDefinition);

        $subtypesList = $this->dataTypeDefinition->getSubtypes();
        if ($subtypesList) {
            if (array_key_exists($this->getProperty('subtype'), $subtypesList)) {
                return $subtypesList[$this->getProperty('subtype')];
            }
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isADeletedRevision()
    {
        return $this->isADeletedRevision;
    }

    /**
     * @param bool $isADeletedRevision
     */
    public function setIsADeletedRevision($isADeletedRevision)
    {
        $this->isADeletedRevision = $isADeletedRevision;
    }

    public function jsonSerialize(): array
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
