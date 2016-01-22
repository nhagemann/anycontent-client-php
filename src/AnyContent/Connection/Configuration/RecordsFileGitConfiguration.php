<?php
namespace AnyContent\Connection\Configuration;

use AnyContent\AnyContentClientException;
use AnyContent\Connection\AbstractConnection;
use AnyContent\Connection\RecordsFileGitReadWriteConnection;

class RecordsFileGitConfiguration extends RecordsFileConfiguration
{

    /**
     * @var string url of the remote git repository
     */
    protected $remoteUrl;

    /**
     * @var string directory containing the local copy of the git repository
     */
    protected $directory;

    protected $privateKey;

    protected $uniqueConnection = false;

    /**
     * @var int seconds not checking for remote changes
     */
    protected $confidence = 300;


    /**
     * @return string
     */
    public function getRemoteUrl()
    {
        return $this->remoteUrl;
    }


    /**
     * @param string $remoteUrl
     */
    public function setRemoteUrl($remoteUrl)
    {
        $this->remoteUrl = $remoteUrl;
    }


    /**
     * @return mixed
     */
    public function getDirectory()
    {
        if ($this->directory == '')
        {
            throw new AnyContentClientException ('No git working directory set.');
        }

        return rtrim($this->directory, '/');
    }


    /**
     * @param mixed $directory
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;

        return $this;
    }


    /**
     * @return mixed
     */
    public function getPrivateKey()
    {
        return $this->privateKey;
    }


    /**
     * @param mixed $privateKey
     */
    public function setPrivateKey($privateKey)
    {
        $this->privateKey = $privateKey;
        return $this;
    }


    public function setUniqueConnection($confidence = 60)
    {
        $this->confidence       = (int)$confidence;
        $this->uniqueConnection = (boolean)$confidence;
        return $this;
    }


    public function apply(AbstractConnection $connection)
    {
        parent::apply($connection);

        $connection->setPrivateKey($this->privateKey);
        $connection->setUniqueConnection($this->confidence);
    }


    public function createReadOnlyConnection()
    {
        // TODO: Downgrade
        return new RecordsFileGitReadWriteConnection($this);
    }


    public function createReadWriteConnection()
    {
        return new RecordsFileGitReadWriteConnection($this);
    }

}