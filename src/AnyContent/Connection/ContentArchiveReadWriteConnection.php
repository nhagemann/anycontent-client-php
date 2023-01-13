<?php

namespace AnyContent\Connection;

use AnyContent\Connection\Interfaces\AdminConnection;
use AnyContent\Connection\Interfaces\ReadOnlyConnection;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ContentArchiveReadWriteConnection extends RecordFilesReadWriteConnection implements ReadOnlyConnection, AdminConnection
{
    /**
     * @var ContentArchiveConfiguration
     */
    protected $configuration;

    protected function getNextId($contentTypeName, $dataDimensions)
    {
        $finder = new Finder();
        $path   = $this->configuration->getContentArchiveFolder() . '/data/content/' . $contentTypeName;
        $path   = realpath($path);
        if ($path) {
            $finder->in($path);
            $finder->files()->name('*.json');

            $next = 0;
            /** @var SplFileInfo $file */
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
        $uri = 'file://' . $this->configuration->getContentArchiveFolder() . '/cmdl/' . $contentTypeName . '.cmdl';

        $sf = new Filesystem();
        $sf->dumpFile($uri, $cmdl);

        $this->getCMDLCache()->flushAll();

        $this->getConfiguration()->apply($this);

        return true;
    }

    public function saveConfigTypeCMDL($configTypeName, $cmdl)
    {
        $uri = 'file://' . $this->configuration->getContentArchiveFolder() . '/cmdl/config/' . $configTypeName . '.cmdl';

        $sf = new Filesystem();
        $sf->dumpFile($uri, $cmdl);

        $this->getCMDLCache()->flushAll();

        $this->getConfiguration()->apply($this);

        return true;
    }

    public function deleteContentTypeCMDL($contentTypeName)
    {
        $uri = 'file://' . $this->configuration->getContentArchiveFolder() . '/cmdl/' . $contentTypeName . '.cmdl';

        $sf = new Filesystem();
        $sf->remove($uri);

        $this->getCMDLCache()->flushAll();

        $this->getConfiguration()->apply($this);

        return true;
    }

    public function deleteConfigTypeCMDL($configTypeName)
    {
        $uri = 'file://' . $this->configuration->getContentArchiveFolder() . '/cmdl/config/' . $configTypeName . '.cmdl';

        $sf = new Filesystem();
        $sf->remove($uri);

        $this->getCMDLCache()->flushAll();

        $this->getConfiguration()->apply($this);

        return true;
    }
}
