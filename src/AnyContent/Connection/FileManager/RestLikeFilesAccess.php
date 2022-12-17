<?php

namespace AnyContent\Connection\FileManager;

use AnyContent\Client\File;
use AnyContent\Client\Folder;
use AnyContent\Connection\Configuration\RestLikeConfiguration;
use AnyContent\Connection\Interfaces\FileManager;
use GuzzleHttp\Client;
use GuzzleHttp\Event\EndEvent;
use GuzzleHttp\Exception\ClientException;
use KVMLogger\KVMLogger;
use KVMLogger\LogMessage;

class RestLikeFilesAccess implements FileManager
{
    /**
     * @var RestLikeConfiguration
     */
    protected $configuration;

    protected $folders = [];

    /**
     * @var Client
     */
    protected $client;

    protected $publicUrl = false;

    public function __construct(RestLikeConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }


    /**
     * @return RestLikeConfiguration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }


    /**
     * @param RestLikeConfiguration $configuration
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
    }


    /**
     * @return Client
     */
    public function getClient()
    {

        if (!$this->client) {
            $client = new Client([ 'base_url' => $this->getConfiguration()->getUri(),
                                   'defaults' => [ 'timeout' => $this->getConfiguration()->getTimeout() ],
                                 ]);

            $this->client = $client;

            $emitter = $client->getEmitter();

            $emitter->on('end', function (EndEvent $event) {

                $kvm = KVMLogger::instance('anycontent-connection');

                $response = $event->getResponse();

                $duration = (int)($event->getTransferInfo('total_time') * 1000);

                $message = new LogMessage();
                $message->addLogValue('method', $event->getRequest()->getMethod());
                $message->addLogValue('duration', $duration);

                if ($response) {
                    $message->addLogValue('code', $response->getStatusCode());
                    $message->addLogValue('url', $response->getEffectiveUrl());
                }

                $kvm->debug($message);
            });
        }

        return $this->client;
    }


    /**
     * @return boolean
     */
    public function getPublicUrl()
    {
        return $this->publicUrl;
    }


    /**
     * @param boolean $publicUrl
     */
    public function setPublicUrl($publicUrl)
    {
        $this->publicUrl = rtrim($publicUrl, '/');

        return $this;
    }

    /**
     * @param string $path
     *
     * @return Folder|bool
     */
    public function getFolder($path = '')
    {
        if (!array_key_exists($path, $this->folders)) {
            $this->folders[$path] = false;

            $url = 'files';
            $path = trim($path, '/');
            if ($path != '') {
                $url .= '/' . $path;
            }

            $response = $this->getClient()->get($url);
            $json = $response->json();

            if ($json) {
                if ($this->publicUrl != false) {
                    $files = [];
                    foreach ($json['files'] as $file) {
                        $file['urls']['default'] = $this->publicUrl . '/' . $file['id'];
                        $files[] = $file;
                    }
                    $json['files'] = $files;
                }
                $folder = new Folder($path, $json);


                $this->folders[$path] = $folder;
            }
        }

        return $this->folders[$path];
    }


    public function getFile($fileId)
    {
        $id = trim(trim($fileId, '/'));
        if ($id != '') {
            $pathinfo = pathinfo($id);
            $folder   = $this->getFolder($pathinfo['dirname']);
            if ($folder) {
                return $folder->getFile($id);
            }
        }

        return false;
    }


    public function getBinary(File $file)
    {
        $url = 'file/' . trim($file->getId(), '/');

        try {
            $response = $this->getClient()->get($url);

            return $response->getBody()->getContents();
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() == 404) {
                return false;
            }
            throw new ClientException($e->getMessage(), $e->getRequest(), $e->getResponse(), $e);
        }
    }


    public function saveFile($fileId, $binary)
    {
        $this->folders = [];

        $url = 'file/' . trim($fileId, '/');
        $this->getClient()
             ->post($url, ['body' => $binary]);

        return true;
    }


    public function deleteFile($fileId, $deleteEmptyFolder = true)
    {
        $this->folders = [];

        $url = 'file/' . trim($fileId, '/');
        $this->getClient()->delete($url);

        if ($deleteEmptyFolder) {
            $dirName = pathinfo($fileId, PATHINFO_DIRNAME);

            return $this->deleteFolder($dirName);
        }

        return true;
    }


    public function createFolder($path)
    {
        $url = 'files/' . trim($path, '/');
        $this->getClient()->post($url);

        return true;
    }


    public function deleteFolder($path, $deleteIfNotEmpty = false)
    {
        $folder = $this->getFolder($path);
        if ($folder) {
            if ($folder->isEmpty() || $deleteIfNotEmpty) {
                $url = 'files/' . trim($path, '/');

                $this->getClient()->delete($url);

                $this->folders = [];

                return true;
            }
        }

        return false;
    }
}
