<?php

namespace AnyContent\Client;

use AnyContent\AnyContentClientException;
use AnyContent\Cache\CachingRepository;
use AnyContent\Client\Traits\Options;
use AnyContent\Connection\Configuration\ContentArchiveConfiguration;
use AnyContent\Connection\Configuration\MySQLSchemalessConfiguration;
use AnyContent\Connection\Configuration\RestLikeConfiguration;
use AnyContent\Connection\FileManager\DirectoryBasedFilesAccess;
use AnyContent\Connection\FileManager\RestLikeFilesAccess;

class RepositoryFactory
{

    use Options;


    public function createRepositoryFromConfigArray($name, $config, $cache = true)
    {
        $this->options = $config;

        if (!$this->hasOption('type')) {
            throw new AnyContentClientException('Invalid config array. Could not find mandatory key type.');
        }

        if ($this->getOption('type') == 'archive') {

            if (!$this->hasOption('folder')) {
                throw new AnyContentClientException('Invalid config array. Could not find mandatory key folder for repository named '.$name);
            }

            $options = [];
            $options['files']=$this->getOption('files',true);

            return $this->createContentArchiveRepository($name, $this->getOption('folder'), $options,
                                                         $cache);
        }

        if ($this->getOption('type') == 'restlike') {

            if (!$this->hasOption('url')) {
                throw new AnyContentClientException('Invalid config array. Could not find mandatory key url for repository named '.$name);
            }

            $options = [];

            if ($this->hasOption('contenttypes'))
            {
                $options['contenttypes']=$this->getOption('contenttypes');
            }

            if ($this->hasOption('configtypes'))
            {
                $options['configtypes']=$this->getOption('configtypes');
            }

            $files=$this->getOption('files',true);

            return $this->createRestLikeRepository($name, $this->getOption('url'), $options,
                                                   $cache,$files);
        }

        throw new AnyContentClientException('Invalid config array. Unknown type ' . $config['type']);

    }


    public function createContentArchiveRepository($name, $folder, $options = ['files' => true], $cache = true)
    {
        $this->options = $options;

        $folder = rtrim($folder, '/');

        $configuration = new ContentArchiveConfiguration();

        $configuration->setContentArchiveFolder($folder);

        $connection = $configuration->createReadWriteConnection();

        $fileManager = null;

        if ($this->getOption('files', true) == true) {
            $fileManager = new DirectoryBasedFilesAccess($folder . '/files');
            if ($this->getOption('files')!==true)
            {
                $fileManager->setPublicUrl($this->getOption('files'));
            }

        }

        $repository = $this->createRepository($name, $connection, $fileManager, $this->getOption('title', null),
                                              $cache);

        return $repository;

    }


    public function createMySQLSchemalessRepository($name, $options = [], $cache = true)
    {
        $this->options = $options;

        $configuration = new MySQLSchemalessConfiguration();

        $this->requireOption('database');
        $this->options = $options['database'];

        $this->requireOptions(['host', 'dbName', 'user', 'password']);
        $configuration->initDatabase($this->getOption('host'), $this->getOption('dbName'), $this->getOption('user'),
                                     $this->getOption('password'), $this->getOption('port', 3306));
        $this->options = $options;

        if ($this->hasOption('cmdlFolder')) {
            $configuration->setCMDLFolder($this->getOption('cmdlFolder'));
        }

        $configuration->setRepositoryName($name);
        $configuration->addContentTypes($this->getOption('contentTypes'));
        $configuration->addConfigTypes($this->getOption('configTypes'));

        $connection = $configuration->createReadWriteConnection();

        $fileManager = null;

        if ($this->hasOption('filesFolder')) {
            $fileManager = new DirectoryBasedFilesAccess($this->getOption('filesFolder'));
        }

        $repository = $this->createRepository($name, $connection, $fileManager, $this->getOption('title', null),
                                              $cache);

        return $repository;
    }


    public function createRestLikeRepository($name, $baseUrl, $options = [], $cache = true, $files = true)
    {
        $this->options = $options;

        $configuration = new RestLikeConfiguration();
        $configuration->setUri($baseUrl);
        $connection = $configuration->createReadWriteConnection();

        $contentTypeNames = false;
        if ($this->hasOption('contenttypes')) {
            $contentTypeNames = $this->getOption('contenttypes');
        }

        $configuration->addContentTypes($contentTypeNames);

        $configTypeNames = false;
        if ($this->hasOption('configtypes')) {
            $configTypeNames = $this->getOption('configtypes');
        }
        $configuration->addConfigTypes($configTypeNames);

        $fileManager = null;

        if ($files) {
            $fileManager = new RestLikeFilesAccess($configuration);
        }

        $repository = $this->createRepository($name, $connection, $fileManager, $this->getOption('title', null),
                                              $cache);

        return $repository;

    }


    protected function createRepository($name, $connection, $fileManager, $title, $cache)
    {
        if ($cache == true) {
            $repository = new CachingRepository($name, $connection, $fileManager);

            $repository->enableSingleContentRecordCaching(60);
            $repository->enableAllContentRecordsCaching(60);
            $repository->enableContentQueryRecordsCaching(60);
            $repository->enableCmdlCaching(60);
        } else {
            $repository = new Repository($name, $connection, $fileManager);
        }

        $repository->setTitle($title);

        return $repository;
    }
}