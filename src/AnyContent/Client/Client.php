<?php

declare(strict_types=1);

namespace AnyContent\Client;

use AnyContent\AnyContentClientException;
use AnyContent\Cache\CachingRepository;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class Client
{
    /**
     * @var UserInfo
     */
    protected $userInfo;

    protected AdapterInterface $cacheAdapter;

    protected $repositories = [ ];

    public function __construct(UserInfo $userInfo = null, AdapterInterface $cacheAdapter = null)
    {
        if ($userInfo != null) {
            $this->userInfo = $userInfo;
        }
        if ($cacheAdapter != null) {
            $this->$cacheAdapter = $cacheAdapter;
        }
    }

    /**
     * @return UserInfo
     */
    public function getUserInfo()
    {
        return $this->userInfo;
    }

    /**
     * @param UserInfo $userInfo
     */
    public function setUserInfo($userInfo)
    {
        $this->userInfo = $userInfo;
    }

    public function getCacheAdapter(): AdapterInterface
    {
        if (!isset($this->cacheAdapter)) {
            $this->cacheAdapter = new ArrayAdapter();
        }
        return $this->cacheAdapter;
    }

    public function setCacheAdapter(AdapterInterface $cacheAdapter): void
    {
        $this->cacheAdapter = $cacheAdapter;
        foreach ($this->getRepositories() as $repository) {
            if ($repository instanceof CachingRepository) {
                $repository->setCacheAdapter($this->getCacheAdapter());
            }
        }
    }

    /**
     * @return array
     */
    public function getRepositories()
    {
        return $this->repositories;
    }

    public function addRepository(Repository $repository)
    {
        if ($repository->getName() == '') {
            throw new AnyContentClientException('Cannot add repository without name');
        }
        $this->repositories[$repository->getName()] = $repository;

        if ($repository instanceof CachingRepository) {
            $repository->setCacheAdapter($this->getCacheAdapter());
        }

        return $repository;
    }

    /**
     * @param $repositoryName
     *
     * @return Repository
     * @throws AnyContentClientException
     */
    public function getRepository($repositoryName)
    {
        if (array_key_exists($repositoryName, $this->repositories)) {
            return $this->repositories[$repositoryName];
        }

        throw new AnyContentClientException('Unknown repository ' . $repositoryName);
    }
}
