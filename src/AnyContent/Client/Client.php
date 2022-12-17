<?php

namespace AnyContent\Client;

use AnyContent\AnyContentClientException;
use AnyContent\Cache\CachingRepository;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class Client
{
    /**
     * @var UserInfo
     */
    protected $userInfo;

    /** @var  CacheProvider */
    protected $cacheProvider;

    protected $repositories = [ ];


    public function __construct(UserInfo $userInfo = null, CacheProvider $cacheProvider = null)
    {
        if ($userInfo != null) {
            $this->userInfo = $userInfo;
        }
        if ($cacheProvider != null) {
            $this->cacheProvider = $cacheProvider;
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


    /**
     * @return CacheProvider
     */
    public function getCacheProvider()
    {
        if (!$this->cacheProvider) {
            // https://stackoverflow.com/questions/68166221/class-doctrine-common-cache-arraycache-does-not-exist-when-installing-a-symfon
            $this->cacheProvider =  DoctrineProvider::wrap(new ArrayAdapter());
        }

        return $this->cacheProvider;
    }


    /**
     * @param CacheProvider $cache
     */
    public function setCacheProvider($cacheProvider)
    {
        $this->cacheProvider = $cacheProvider;

        foreach ($this->getRepositories() as $repository) {
            if ($repository instanceof CachingRepository) {
                $repository->setCacheProvider($this->getCacheProvider());
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
            $repository->setCacheProvider($this->getCacheProvider());
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
