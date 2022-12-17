<?php

namespace AnyContent\Client\Util;

use AnyContent\Client\File;
use AnyContent\Client\Repository;
use Imagine\Filter\Basic\Crop;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Finder\Finder;

/**
 *
 * This class is responsible for creating image versions/copies of AnyContent Images and storing them as
 * local files in a to be defined folder ($basePath). This should be a public accessible web folder ($baseUrl).
 *
 * The main methods are getFittingImage(), getResizedImage() and getOriginalImage(). All of them expect a File object
 * and return the same file object having added a new url, which then can be used in your markup.
 *
 * @package AnyContent\Util\Imagine
 */
class ImageVersionCreator
{
    /**
     * @var Repository
     */
    protected $repository = null;

    protected $basePath = null;

    protected $baseUrl = null;

    protected $quality = 75;

    protected $timestampCheck = true;

    protected $keepOriginalImageIfSizeIsTheSame = false;

    protected $cacheBinaries = false;

    protected $cachedBinaries = array();


    /**
     * @param      $repository
     * @param      $basePath
     * @param      $baseUrl
     * @param      $quality 0-100, default is 75
     */
    public function __construct($repository, $basePath, $baseUrl, $quality = null)
    {
        $this->selectRepository($repository);

        $this->basePath = $basePath;

        $this->baseUrl = $baseUrl;

        if ($quality) {
            $this->setQuality($quality);
        }
    }


    public function setBasePathAndUrl($basePath, $baseUrl)
    {
        $this->basePath = $basePath;

        $this->baseUrl = $baseUrl;
    }


    /**
     * @return null
     */
    public function getBasePath()
    {
        return $this->basePath;
    }


    /**
     * @return null
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }


    public function selectRepository($repository)
    {
        $this->repository     = $repository;
        $this->cachedBinaries = array();
    }


    public function getCurrentRepository()
    {
        return $this->repository;
    }


    /**
     * @param int $quality
     */
    public function setQuality($quality)
    {
        $this->quality = $quality;
    }


    /**
     * @return int
     */
    public function getQuality()
    {
        return $this->quality;
    }


    /**
     * If the timestamp check is enabled, images won't get rebuild, if the modified date of the generated image
     * file is newer, than the last change date of the file stored in the remote repository. This mode is
     * enabled by default, since it has a huge performance impact. You should only turn it off during
     * development and/or if you need to be sure to replace the content of a file with a distinct filename, even
     * if it's newer than the stated in the remote repository, e.g. if you change parameters of image versions, but keep the same file name.
     */
    public function enableTimestampCheck()
    {
        $this->timestampCheck = true;
    }


    public function disableTimestampCheck()
    {
        $this->timestampCheck = false;
    }


    /**
     * If you create more than one version of an image, you might turn on the (request scoped) binary cache, to avoid multiple fetching
     * of binary data. The cache is turned off by default, since it is very memory intensive.
     *
     * If the repository does not provide a binary url ($urlType='binary'), you should (additionally) generate a local copy of the original
     * image via getOriginalImage(). If you do that before generating a image version the binary will get fetched
     * from your local server instead of the remote repository (unless it has been changed remotely).
     */
    public function enableBinaryCache()
    {
        $this->cacheBinaries  = true;
        $this->cachedBinaries = array();
    }


    public function disableBinaryCache()
    {
        $this->cacheBinaries  = false;
        $this->cachedBinaries = array();
    }


    /**
     * @return boolean
     */
    public function isKeepOriginalImageIfSizeIsTheSame()
    {
        return $this->keepOriginalImageIfSizeIsTheSame;
    }


    /**
     * @param boolean $keepOriginalImageIfSizeIsTheSame
     */
    public function setKeepOriginalImageIfSizeIsTheSame($keepOriginalImageIfSizeIsTheSame)
    {
        $this->keepOriginalImageIfSizeIsTheSame = $keepOriginalImageIfSizeIsTheSame;
    }


    /**
     * @param File   $file
     * @param string $urlType
     * @param int    $width
     * @param null   $height
     * @param null   $filename
     * @param null   $quality
     *
     * @return File|bool
     */
    public function getFittingImage(
        File $file,
        $urlType = 'default',
        $width = 100,
        $height = null,
        $filename = null,
        $quality = null
    ) {
        if ($height == null) {
            $height = $width;
        }
        if ($this->repository) {
            if ($file->isImage()) {
                $filename = $this->determineFileName($file, $width, $height, 'f', $filename);

                if ($this->mustBuildImage($file, $filename)) {
                    $binary = $this->getBinary($file);

                    if ($binary) {
                        $imagine = new Imagine();
                        $image   = $imagine->load($binary);

                        $size  = $image->getSize();
                        $ratio = $size->getWidth() / $size->getHeight();

                        if ($ratio > $width / $height) {
                            $size = $size->widen($width);
                        } else {
                            $size = $size->heighten($height);
                        }

                        if ($this->keepOriginalImage($binary, $size->getWidth(), $size->getHeight())) {
                            return $this->getOriginalImage($file, $urlType, $filename);
                        }

                        $image->resize($size);

                        $quality = $this->determineQuality($quality);
                        $image->save($this->basePath . '/' . $filename, array('quality' => $quality));
                    } else {
                        return false;
                    }
                }

                $url = $this->baseUrl . '/' . $filename;

                $file->addUrl($urlType, $url);

                return $file;
            }
        }

        return false;
    }


    /**
     * @param File   $file
     * @param string $urlType
     * @param int    $width
     * @param null   $height
     * @param bool   $crop
     * @param null   $filename
     * @param null   $quality
     *
     * @return File|bool
     */
    public function getResizedImage(
        File $file,
        $urlType = 'default',
        $width = 100,
        $height = null,
        $crop = true,
        $filename = null,
        $quality = null
    ) {
        if ($crop) {
            return $this->getCroppedImage($file, $urlType, $width, $height, $filename, $quality);
        }

        if ($height == null) {
            $height = $width;
        }

        if ($this->repository) {
            if ($file->isImage()) {
                $filename = $this->determineFileName($file, $width, $height, 'r', $filename);

                if ($this->mustBuildImage($file, $filename)) {
                    $binary = $this->getBinary($file);

                    if ($this->keepOriginalImage($binary, $width, $height)) {
                        return $this->getOriginalImage($file, $urlType, $filename);
                    }

                    if ($binary) {
                        $imagine = new Imagine();
                        $image   = $imagine->load($binary);

                        $image->resize(new Box($width, $height));

                        $quality = $this->determineQuality($quality);
                        $image->save($this->basePath . '/' . $filename, array('quality' => $quality));
                    } else {
                        return false;
                    }
                }

                $url = $this->baseUrl . '/' . $filename;
                $file->addUrl($urlType, $url);

                return $file;
            }
        }

        return false;
    }


    /**
     * @param File   $file
     * @param string $urlType
     * @param null   $width
     * @param null   $height
     * @param null   $filename
     * @param null   $quality
     *
     * @return File|bool
     */
    public function getScaledImage(
        File $file,
        $urlType = 'default',
        $width = null,
        $height = null,
        $filename = null,
        $quality = null
    ) {

        if ($width != null && $height != null) {
            return $this->getResizedImage($file, $urlType, $width, $height, false, $filename, $quality);
        }

        if ($this->mustBuildImage($file, $filename) || $filename == null) {
            $binary = $this->getBinary($file);

            if ($binary) {
                $imagine = new Imagine();
                $image   = $imagine->load($binary);

                $size  = $image->getSize();
                $ratio = $size->getWidth() / $size->getHeight();

                if ($width == null) {
                    $width = $height * $ratio;
                } else {
                    $height = $width / $ratio;
                }

                $filename = $this->determineFileName($file, $width, $height, 's', $filename);
                if ($this->mustBuildImage($file, $filename)) {
                    if ($this->keepOriginalImage($binary, $width, $height)) {
                        return $this->getOriginalImage($file, $urlType, $filename);
                    }

                    $image->resize(new Box($width, $height));

                    $quality = $this->determineQuality($quality);
                    $image->save($this->basePath . '/' . $filename, array('quality' => $quality));
                }
            } else {
                return false;
            }
        }

        $url = $this->baseUrl . '/' . $filename;

        $file->addUrl($urlType, $url);

        return $file;
    }


    /**
     * @param File   $file
     * @param string $urlType
     * @param int    $width
     * @param null   $height
     * @param null   $filename
     * @param null   $quality
     *
     * @return File|bool
     */
    protected function getCroppedImage(
        File $file,
        $urlType = 'default',
        $width = 100,
        $height = null,
        $filename = null,
        $quality = null
    ) {
        if ($height == null) {
            $height = $width;
        }

        if ($this->repository) {
            if ($file->isImage()) {
                $filename = $this->determineFileName($file, $width, $height, 'c', $filename);

                if ($this->mustBuildImage($file, $filename)) {
                    $binary = $this->getBinary($file);

                    if ($this->keepOriginalImage($binary, $width, $height)) {
                        return $this->getOriginalImage($file, $urlType, $filename);
                    }

                    if ($binary) {
                        $imagine = new Imagine();
                        $image   = $imagine->load($binary);

                        $size = $image->getSize();

                        $ratioOriginal = $size->getWidth() / $size->getHeight();

                        if ($ratioOriginal > ($width / $height)) {
                            // create image that has the desired height and an oversize width to crop from
                            $y = $height;
                            $x = $size->getWidth() * ($y / $size->getHeight());

                            $size = new Box($x, $y);

                            $image->resize($size);

                            $start = (int)(($x - $width) / 2);

                            $upperLeft = new Point($start, 0);

                            $image->crop($upperLeft, new Box($width, $height));
                        } else {
                            // create image that has the desired width and an oversize height to crop from
                            $x = $width;
                            $y = $size->getHeight() * ($x / $size->getWidth());

                            $size = new Box($x, $y);
                            $image->resize($size);

                            $start = (int)(($y - $height) / 2);

                            $upperLeft = new Point(0, $start);

                            $image->crop($upperLeft, new Box($width, $height));
                        }

                        $quality = $this->determineQuality($quality);
                        $image->save($this->basePath . '/' . $filename, array('quality' => $quality));
                    } else {
                        return false;
                    }
                }

                $url = $this->baseUrl . '/' . $filename;
                $file->addUrl($urlType, $url);

                return $file;
            }
        }

        return false;
    }


    public function getOriginalImage(File $file, $urlType = 'binary', $filename = null)
    {
        if ($this->repository) {
            if ($file->isImage()) {
                $filename = $this->determineFileName($file, null, null, 'o', $filename);

                if ($this->mustBuildImage($file, $filename)) {
                    $binary = $this->getBinary($file);

                    if ($binary) {
                        file_put_contents($this->basePath . '/' . $filename, $binary);
                    } else {
                        return false;
                    }
                }

                $url = $this->baseUrl . '/' . $filename;
                $file->addUrl($urlType, $url);

                return $file;
            }
        }

        return false;
    }


    public function deleteRecentlyNotAccessedFiles($minutes = 1440, $path = null)
    {

        $fs     = new Filesystem();
        $finder = new Finder();

        if ($path == null) {
            $path = $this->basePath;
        } else {
            $path = $this->basePath . '/' . $path;
        }

        $path = realpath($path);

        if (!$path) {
            return false;
        }

        $filter = function (\SplFileInfo $file) use ($minutes) {
            $fileAgeMinutes = (int)((time() - $file->getCTime()) / 60);

            if ($fileAgeMinutes < $minutes) {
                return false;
            }

            return true;
        };

        $finder->files()->in($this->basePath)->filter($filter);

        $c = 0;

        foreach ($finder as $file) {
            $c++;
            $fs->remove($file->getRealpath());
        }

        return $c;
    }


    protected function getBinary(File $file)
    {
        if ($this->cacheBinaries == true) {
            if (array_key_exists($file->getId(), $this->cachedBinaries)) {
                return $this->cachedBinaries[$file->getId()];
            }
            $binary                               = $this->repository->getBinary($file);
            $this->cachedBinaries[$file->getId()] = $binary;
        } else {
            $binary = $this->repository->getBinary($file);
        }

        return $binary;
    }


    protected function determineFileName($file, $width, $height, $mode, $filename = null)
    {
        if (!$filename) {
            $info = pathinfo($file->getName());
            if ($mode == 'o') {
                $filename = $info['filename'] . '.orig.' . $info['extension'];
            } else {
                $filename = $info['filename'] . '_' . $width . 'x' . $height . $mode . '.' . $info['extension'];
            }
        }

        $fs   = new Filesystem();
        $info = pathinfo($this->basePath . '/' . $filename);
        $fs->mkdir($info['dirname']);

        return $filename;
    }


    protected function determineQuality($quality = null)
    {
        if (!$quality) {
            return $this->getQuality();
        }

        return $quality;
    }


    protected function mustBuildImage(File $file, $filename)
    {

        if ($this->timestampCheck == false) {
            return true;
        }

        $fs = new Filesystem();
        if (!$fs->exists($this->basePath . '/' . $filename)) {
            return true;
        }

        $info = new \SplFileInfo($this->basePath . '/' . $filename);

        if ($file->getTimestampLastChange() > $info->getCTime()) {
            return true;
        }

        // Change timestamp of the file, to mark it as "active"

        $fs->touch($this->basePath . '/' . $filename);

        return false;
    }


    protected function keepOriginalImage($binary, $width, $height)
    {
        if ($this->isKeepOriginalImageIfSizeIsTheSame() == true) {
            list($originalWidth, $originalHeight) = getimagesizefromstring($binary);

            if ($originalWidth == $width && $originalHeight == $height) {
                return true;
            }
        }

        return false;
    }
}
