<?php

namespace AnyContent\Cache;

use Doctrine\Common\Cache\CacheProvider;
use KVMLogger\KVMLogger;

class Wrapper extends CacheProvider
{
    /** @var  CacheProvider */
    protected $cacheProvider;

    protected $hit = 0;
    protected $miss = 0;


    public function __construct(CacheProvider $cacheProvider)
    {
        $this->setCacheProvider($cacheProvider);
    }


    /**
     * @return CacheProvider
     */
    public function getCacheProvider()
    {
        return $this->cacheProvider;
    }


    /**
     * @param CacheProvider $cacheProvider
     */
    public function setCacheProvider($cacheProvider)
    {
        $this->cacheProvider = $cacheProvider;
    }


    public function clearHitMissCounter()
    {
        $this->hit  = 0;
        $this->miss = 0;
    }


    /**
     * @return int
     */
    public function getHitCounter()
    {
        return $this->hit;
    }


    /**
     * @return int
     */
    public function getMissCounter()
    {
        return $this->miss;
    }


    /**
     * Fetches an entry from the cache.
     *
     * @param string $id The id of the cache entry to fetch.
     *
     * @return string|boolean The cached data or FALSE, if no cache entry exists for the given id.
     */
    protected function doFetch($id)
    {
        $md5Key = md5($id);

        $kvm = KVMLogger::instance('anycontent-cache');

        $data = $this->getCacheProvider()->doFetch($md5Key);

        if ($data) {
            $this->hit++;
            $message = $kvm->createLogMessage('Cache hit', [ 'key' => $id, 'md5' => md5($id), 'doctrine-namespace' => $this->getNamespace() ]);
            $kvm->debug($message);
        } else {
            $this->miss++;
            $message = $kvm->createLogMessage('Cache miss', [ 'key' => $id, 'md5' => md5($id), 'doctrine-namespace' => $this->getNamespace() ]);
            $kvm->debug($message);
        }

        return $data;
    }


    /**
     * Tests if an entry exists in the cache.
     *
     * @param string $id The cache id of the entry to check for.
     *
     * @return boolean TRUE if a cache entry exists for the given cache id, FALSE otherwise.
     */
    protected function doContains($id)
    {

        $md5Key = md5($id);

        $kvm = KVMLogger::instance('anycontent-cache');

        $hit = $this->getCacheProvider()->doContains($md5Key);

        if ($hit) {
            $this->hit++;
            $message = $kvm->createLogMessage('Cache hit', [ 'key' => $id, 'md5' => $md5Key, 'namespace' => $this->getNamespace() ]);
            $kvm->debug($message);
        } else {
            $this->miss++;
            $message = $kvm->createLogMessage('Cache miss', [ 'key' => $id, 'namespace' => $this->getNamespace() ]);
            $kvm->debug($message);
        }

        return $hit;
    }


    /**
     * Puts data into the cache.
     *
     * @param string $id         The cache id.
     * @param string $data       The cache entry/data.
     * @param int    $lifeTime   The lifetime. If != 0, sets a specific lifetime for this
     *                           cache entry (0 => infinite lifeTime).
     *
     * @return boolean TRUE if the entry was successfully stored in the cache, FALSE otherwise.
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        $md5Key = md5($id);

        return $this->getCacheProvider()->doSave($md5Key, $data, $lifeTime);
    }


    /**
     * Deletes a cache entry.
     *
     * @param string $id The cache id.
     *
     * @return boolean TRUE if the cache entry was successfully deleted, FALSE otherwise.
     */
    protected function doDelete($id)
    {
        $md5Key = md5($id);

        return $this->getCacheProvider()->doDelete($md5Key);
    }


    /**
     * Flushes all cache entries.
     *
     * @return boolean TRUE if the cache entry was successfully deleted, FALSE otherwise.
     */
    protected function doFlush()
    {
        return $this->getCacheProvider()->doFlush();
    }


    /**
     * Retrieves cached information from the data store.
     *
     * @since 2.2
     *
     * @return array|null An associative array with server's statistics if available, NULL otherwise.
     */
    protected function doGetStats()
    {
        return $this->getCacheProvider()->doGetStats();
    }
}
