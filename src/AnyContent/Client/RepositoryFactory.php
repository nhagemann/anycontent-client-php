<?php

declare(strict_types=1);

namespace AnyContent\Client;

use AnyContent\AnyContentClientException;
use AnyContent\Cache\CachingRepository;
use AnyContent\Client\Traits\Options;
use AnyContent\Connection\Configuration\ContentArchiveConfiguration;
use AnyContent\Connection\Configuration\MySQLSchemalessConfiguration;
use AnyContent\Connection\FileManager\DirectoryBasedFilesAccess;

class RepositoryFactory
{
    use Options;

    /**
     * Convenient way to init repositories - intend to combine with a yaml config
     *
     * Example:
     *
     *  repos_name_1:
     *    type: mysqlschemaless
     *    host:       127.0.0.1
     *    dbname:     anycontent
     *    user:       dbuser
     *    password:   password
     *    cmdl: /var/www/cmdl (or leave empty for reading cmdl from database)
     *
     *   repos_name_2:
     *      type: archive
     *      folder: /var/www/repos/name_3
     *      files: true
     *
     * Additionally you can specify a distinct filemanager
     *
     *   repos_name_2:
     *      ...
     *      filemanager:
     *        type: directory
     *        path: var/www/assets
     *        files_url: http://assets.company.com
     *
     *
     * @param      $name
     * @param      $config
     * @param bool $cache
     *
     * @return CachingRepository|Repository|null
     * @throws AnyContentClientException
     */
    public function createRepositoryFromConfigArray($name, $config, $cache = true)
    {
        $this->options = $config;

        if (!$this->hasOption('type')) {
            throw new AnyContentClientException('Invalid config array. Could not find mandatory key type.');
        }

        $repository = null;

        if ($this->getOption('type') == 'archive') {
            if (!$this->hasOption('folder')) {
                throw new AnyContentClientException('Invalid config array. Could not find mandatory key folder for repository named ' . $name);
            }

            $options          = [];
            $options['files'] = $this->getOption('files', true);

            $repository = $this->createContentArchiveRepository(
                $name,
                $this->getOption('folder'),
                $options,
                $cache
            );
        }

        if ($this->getOption('type') == 'mysqlschemaless') {
            $this->requireOptions(['host', 'dbname', 'user', 'password']);

            $options             = [];
            $options['database'] = ['host' => $this->getOption('host'), 'dbname' => $this->getOption('dbname'), 'user' => $this->getOption('user'), 'password' => $this->getOption('password')];

            if ($this->hasOption('cmdl')) {
                $options['cmdlFolder'] = $this->getOption('cmdl');
            }

            $repository = $this->createMySQLSchemalessRepository($name, $options, $cache);
        }

        if ($repository) {
            $this->options = $config;

            if ($this->hasOption('filemanager')) {
                $this->options = $this->getOption('filemanager');

                $fileManager = null;

                if ($this->getOption('type') == 'directory') {
                    $this->requireOption('path');
                    $fileManager = new DirectoryBasedFilesAccess($this->getOption('path'));
                    $fileManager->disableImageSizeCalculation();
                }

                if (!$fileManager) {
                    throw new AnyContentClientException('Invalid config array. Unknown filemanger type ' . $this->getOption('type'));
                }

                $repository->setFileManager($fileManager);

                if ($this->hasOption('files_url')) {
                    $fileManager->setPublicUrl(trim($this->getOption('files_url'), '/'));
                }
            }

            return $repository;
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
            if ($this->getOption('files') !== true) {
                $fileManager->setPublicUrl($this->getOption('files'));
            }
        }

        return $this->createRepository(
            $name,
            $connection,
            $fileManager,
            $this->getOption('title', null),
            $cache
        );
    }

    public function createMySQLSchemalessRepository($name, $options = [], $cache = true)
    {
        $this->options = $options;

        $configuration = new MySQLSchemalessConfiguration();

        $this->requireOption('database');
        $this->options = $options['database'];

        $this->requireOptions(['host', 'dbname', 'user', 'password']);
        $configuration->initDatabase(
            $this->getOption('host'),
            $this->getOption('dbname'),
            $this->getOption('user'),
            $this->getOption('password'),
            $this->getOption('port', 3306)
        );
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

        return $this->createRepository(
            $name,
            $connection,
            $fileManager,
            $this->getOption('title', null),
            $cache
        );
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
