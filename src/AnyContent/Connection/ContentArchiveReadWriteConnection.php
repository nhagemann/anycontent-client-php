<?php

namespace AnyContent\Connection;

use AnyContent\Connection\Configuration\ContentArchiveConfiguration;
use AnyContent\Connection\Configuration\MySQLSchemalessConfiguration;
use AnyContent\Connection\Configuration\RecordFilesConfiguration;
use AnyContent\Connection\Interfaces\AdminConnection;
use AnyContent\Connection\Interfaces\ReadOnlyConnection;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ContentArchiveReadWriteConnection extends RecordFilesReadWriteConnection implements ReadOnlyConnection, AdminConnection
{


    protected function getNextId($contentTypeName, $dataDimensions)
    {
        $finder = new Finder();
        assert ( $this->getConfiguration() instanceof ContentArchiveConfiguration);
        $path   = $this->getConfiguration()->getContentArchiveFolder() . '/data/content/' . $contentTypeName;
        $path   = realpath($path);
        if ($path) {
            $finder->in($path);
            $finder->files()->name('*.json');

            $next = 0;
            foreach ($finder as $file) {
                // Sorting by name won't help here
                $next = max($next, (int)($file->getBasename('.json')));
            }

            return ++$next;
        } else {
            return 1;
        }
    }

    public function saveContentTypeCMDL($contentTypeName, $cmdl)
    {
        assert ( $this->getConfiguration() instanceof ContentArchiveConfiguration);
        $uri = 'file://' . $this->getConfiguration()->getContentArchiveFolder() . '/cmdl/' . $contentTypeName . '.cmdl';

        $sf = new Filesystem();
        $sf->dumpFile($uri, $cmdl);

        $this->getCMDLCache()->clear();

        assert ( $this->getConfiguration() instanceof ContentArchiveConfiguration);
        $this->getConfiguration()->apply($this);

        return true;
    }

    public function saveConfigTypeCMDL($configTypeName, $cmdl)
    {
        assert ( $this->getConfiguration() instanceof ContentArchiveConfiguration);
        $uri = 'file://' . $this->getConfiguration()->getContentArchiveFolder() . '/cmdl/config/' . $configTypeName . '.cmdl';

        $sf = new Filesystem();
        $sf->dumpFile($uri, $cmdl);

        $this->getCMDLCache()->clear();

        assert ( $this->getConfiguration() instanceof ContentArchiveConfiguration);
        $this->getConfiguration()->apply($this);

        return true;
    }

    public function deleteContentTypeCMDL($contentTypeName)
    {
        assert ( $this->getConfiguration() instanceof ContentArchiveConfiguration);
        $uri = 'file://' . $this->getConfiguration()->getContentArchiveFolder() . '/cmdl/' . $contentTypeName . '.cmdl';

        $sf = new Filesystem();
        $sf->remove($uri);

        $this->getCMDLCache()->clear();

        assert ( $this->getConfiguration() instanceof ContentArchiveConfiguration);
        $this->getConfiguration()->apply($this);

        return true;
    }

    public function deleteConfigTypeCMDL($configTypeName)
    {
        assert ( $this->getConfiguration() instanceof ContentArchiveConfiguration);
        $uri = 'file://' . $this->getConfiguration()->getContentArchiveFolder() . '/cmdl/config/' . $configTypeName . '.cmdl';

        $sf = new Filesystem();
        $sf->remove($uri);

        $this->getCMDLCache()->clear();

        assert ( $this->getConfiguration() instanceof ContentArchiveConfiguration);
        $this->getConfiguration()->apply($this);

        return true;
    }
}
