<?php

namespace AnyContent\Client;

use AnyContent\AnyContentClientException;
use AnyContent\Client\Traits\Properties;

abstract class AbstractRecord
{
    use Properties;

    protected string $view = 'default';
    protected string $workspace = 'default';
    protected string $language = 'default';

    public int $revision = 0;

    public ?UserInfo $lastChangeUserInfo = null;

    public ?UserInfo $creationUserInfo = null;

    protected ?Repository $repository = null;

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
        if ($this->lastChangeUserInfo == null) {
            $this->lastChangeUserInfo = new UserInfo();
        }

        return $this->lastChangeUserInfo;
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
        ksort($properties);
        return md5(json_encode($properties, true));
    }

    public function reduceProperties($viewName)
    {
        $properties = $this->getProperties();

        $allowedProperties = $this->dataTypeDefinition->getViewDefinition($viewName)->getProperties();

        $properties = array_intersect_key($properties, array_combine($allowedProperties, $allowedProperties));

        $this->setProperties($properties);
    }

    /**
     * @return Repository
     */
    public function getRepository()
    {
        if (!$this->repository) {
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
}
