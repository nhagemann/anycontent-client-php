<?php

namespace AnyContent\Connection;

use AnyContent\AnyContentClientException;
use AnyContent\Client\Config;
use AnyContent\Client\DataDimensions;
use AnyContent\Client\Record;
use AnyContent\Connection\Interfaces\ReadOnlyConnection;
use GuzzleHttp\Client;
use GuzzleHttp\Event\CompleteEvent;
use GuzzleHttp\Event\EndEvent;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Message\Response;
use KVMLogger\KVMLogger;
use KVMLogger\LogMessage;

class RestLikeBasicReadOnlyConnection extends AbstractConnection implements ReadOnlyConnection
{

    /**
     * @var Client
     */
    protected $client;

    protected $repositoryInfo = [ ];


    /**
     * @return Client
     */
    public function getClient()
    {

        if (!$this->client)
        {

            $client = new Client([ 'base_url' => $this->getConfiguration()->getUri(),
                                   'defaults' => [ 'timeout' => $this->getConfiguration()->getTimeout() ]
                                 ]);

            $this->client = $client;

            $emitter = $client->getEmitter();

            $emitter->on('end', function (EndEvent $event)
            {

                $kvm = KVMLogger::instance('anycontent-connection');

                $response = $event->getResponse();

                $duration = (int)($event->getTransferInfo('total_time') * 1000);

                $message = new LogMessage();
                $message->addLogValue('method', $event->getRequest()->getMethod());
                $message->addLogValue('code', $response->getStatusCode());
                $message->addLogValue('duration', $duration);
                $message->addLogValue('url', $response->getEffectiveUrl());

                $kvm->debug($message);

            });
        }

        return $this->client;
    }


    public function getRepositoryInfo(DataDimensions $dataDimensions = null)
    {
        if ($dataDimensions == null)
        {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        if (!array_key_exists((string)$dataDimensions, $this->repositoryInfo))
        {
            $url = 'info/' . $dataDimensions->getWorkspace() . '?language=' . $dataDimensions->getLanguage() . '&view=' . $dataDimensions->getViewName() . '&timeshift=' . $dataDimensions->getTimeShift();

            $response = $this->getClient()->get($url);
            $json     = $response->json();

            $this->repositoryInfo[(string)$dataDimensions] = $json;
        }

        return $this->repositoryInfo[(string)$dataDimensions];
    }


    /**
     * @return array[]
     */
    public function getContentTypeList()
    {
        $info   = $this->getRepositoryInfo();
        $result = [ ];
        foreach ($info['content'] as $name => $item)
        {
            $title = $name;
            if ($item['title'] != '')
            {
                $title = $item['title'];
            }
            $result[$name] = $title;
        }

        return $result;
    }


    /**
     * @return array[]
     */
    public function getConfigTypeList()
    {
        $info   = $this->getRepositoryInfo();
        $result = [ ];
        foreach ($info['config'] as $name => $item)
        {
            $title = $name;
            if ($item['title'] != '')
            {
                $title = $item['title'];
            }
            $result[$name] = $title;
        }

        return $result;
    }


    /**
     * @param $contentTypeName
     *
     * @return string
     */
    public function getCMDLForContentType($contentTypeName)
    {
        if ($this->hasContentType($contentTypeName))
        {
            $url = 'content/' . $contentTypeName . '/cmdl';

            $response = $this->getClient()->get($url);
            $json     = $response->json();

            return $json['cmdl'];
        }

        throw new AnyContentClientException ('Unknown content type ' . $contentTypeName);

    }


    /**
     * @param $configTypeName
     *
     * @return string
     */
    public function getCMDLForConfigType($configTypeName)
    {
        if ($this->getConfiguration()->hasConfigType($configTypeName))
        {

            $response = $this->getClient()->get('config/' . $configTypeName . '/cmdl');
            $json     = $response->json();

            return $json['cmdl'];

        }

        throw new AnyContentClientException ('Unknown config type ' . $configTypeName);

    }


    /**
     * @param null $contentTypeName
     *
     * @return int
     */
    public function countRecords($contentTypeName = null, DataDimensions $dataDimensions = null)
    {
        if ($contentTypeName == null)
        {
            $contentTypeName = $this->getCurrentContentTypeName();
        }

        if ($dataDimensions == null)
        {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        if ($this->hasContentType($contentTypeName))
        {

            $info = $this->getRepositoryInfo($dataDimensions);

            return $info['content'][$contentTypeName]['count'];
        }

        throw new AnyContentClientException ('Unknown content type ' . $contentTypeName);

    }


    /**
     * @param null $contentTypeName
     *
     * @return Record[]
     */
    public function getAllRecords($contentTypeName = null, DataDimensions $dataDimensions = null)
    {
        if ($contentTypeName == null)
        {
            $contentTypeName = $this->getCurrentContentTypeName();
        }

        if ($dataDimensions == null)
        {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        if ($this->hasContentType($contentTypeName))
        {
            if ($this->hasStashedAllRecords($contentTypeName, $dataDimensions, $this->getRecordClassForContentType($contentTypeName)))
            {
                return $this->getStashedAllRecords($contentTypeName, $dataDimensions, $this->getRecordClassForContentType($contentTypeName));
            }

            $url = 'content/' . $contentTypeName . '/records/' . $dataDimensions->getWorkspace() . '?language=' . $dataDimensions->getLanguage() . '&view=' . $dataDimensions->getViewName() . '&timeshift=' . $dataDimensions->getTimeShift();

            $response = $this->getClient()->get($url);

            $json    = $response->json();
            $records = $this->getRecordFactory()
                            ->createRecordsFromJSONRecordsArray($this->getContentTypeDefinition($contentTypeName), $json['records']);

            $this->stashAllRecords($records, $dataDimensions);

            return $records;
        }
    }


    /**
     * @param $recordId
     *
     * @return Record
     */
    public function getRecord($recordId, $contentTypeName = null, DataDimensions $dataDimensions = null)
    {

        if ($contentTypeName == null)
        {
            $contentTypeName = $this->getCurrentContentTypeName();
        }

        if ($dataDimensions == null)
        {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        if ($this->hasContentType($contentTypeName))
        {
            if ($this->hasStashedRecord($contentTypeName, $recordId, $dataDimensions))
            {
                return $this->getStashedRecord($contentTypeName, $recordId, $dataDimensions, $this->getRecordClassForContentType($contentTypeName));
            }
            $url = 'content/' . $contentTypeName . '/record/' . $recordId . '/' . $dataDimensions->getWorkspace() . '?language=' . $dataDimensions->getLanguage() . '&view=' . $dataDimensions->getViewName() . '&timeshift=' . $dataDimensions->getTimeShift();;

            try
            {
                $response = $this->getClient()->get($url);
            }
            catch (ClientException $e)
            {
                if ($e->getCode() == 404)
                {
                    return false;
                }
                throw new AnyContentClientException ($e->getMessage());
            }

            $json = $response->json();

            $record = $this->getRecordFactory()
                           ->createRecordFromJSON($this->getContentTypeDefinition($contentTypeName), $json['record']);

            $this->stashRecord($record, $dataDimensions);

            return $record;
        }

        throw new AnyContentClientException ('Unknown content type ' . $contentTypeName);

    }


    /**
     *
     * @return Config
     */
    public function getConfig($configTypeName = null, DataDimensions $dataDimensions = null)
    {
        if ($dataDimensions == null)
        {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        if ($this->hasConfigType($configTypeName))
        {
            if ($this->hasStashedConfig($configTypeName, $dataDimensions))
            {
                return $this->getStashedConfig($configTypeName, $dataDimensions, $this->getRecordClassForConfigType($configTypeName));
            }

            $url = 'config/' . $configTypeName . '/record/' . $dataDimensions->getWorkspace() . '?language=' . $dataDimensions->getLanguage() . '&view=' . $dataDimensions->getViewName() . '&timeshift=' . $dataDimensions->getTimeShift();;

            try
            {
                $response = $this->getClient()->get($url);

                $json = $response->json();

                $config = $this->getRecordFactory()
                               ->createRecordFromJSON($this->getConfigTypeDefinition($configTypeName), $json['record'], $dataDimensions->getViewName(), $dataDimensions->getWorkspace(), $dataDimensions->getLanguage());

                return $config;
            }
            catch (ClientException $e)
            {
                if ($e->getCode() == 404)
                {
                    $config = $this->getRecordFactory()
                                   ->createConfig($this->getConfigTypeDefinition($configTypeName), [ ], $dataDimensions->getViewName(), $dataDimensions->getWorkspace(), $dataDimensions->getLanguage());

                    return $config;
                }
                throw new AnyContentClientException ($e->getMessage());
            }
        }

        throw new AnyContentClientException ('Unknown config type ' . $configTypeName);

    }


    public function getLastModifiedDate($contentTypeName = null, $configTypeName = null, DataDimensions $dataDimensions = null)
    {
        if ($dataDimensions == null)
        {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        $t = 0;

        $configuration = $this->getConfiguration();

        if ($contentTypeName == null && $configTypeName == null)
        {
            foreach ($configuration->getContentTypeNames() as $contentTypeName)
            {
                $t = max($t, $this->getLastModifedDateForContentType($contentTypeName, $dataDimensions));
            }

            foreach ($configuration->getConfigTypeNames() as $configTypeName)
            {
                $t = max($t, $this->getLastModifedDateForConfigType($configTypeName, $dataDimensions));
            }
        }
        elseif ($contentTypeName != null)
        {
            return $this->getLastModifedDateForContentType($contentTypeName, $dataDimensions);
        }
        elseif ($configTypeName != null)
        {
            return $this->getLastModifedDateForConfigType($configTypeName, $dataDimensions);
        }

        return $t;

    }


    protected function getLastModifedDateForContentType($contentTypeName, DataDimensions $dataDimensions)
    {
        $t = 0;

        $info = $this->getRepositoryInfo($dataDimensions);

        if (isset($info['content'][$contentTypeName]['lastchange_content']))
        {
            $t = max($t, $info['content'][$contentTypeName]['lastchange_content']);
        }

        if (isset($info['content'][$contentTypeName]['lastchange_cmdl']))
        {
            $t = max($t, $info['content'][$contentTypeName]['lastchange_cmdl']);
        }

        return $t;
    }


    protected function getLastModifedDateForConfigType($configTypeName, DataDimensions $dataDimensions)
    {
        $t = 0;

        $info = $this->getRepositoryInfo($dataDimensions);

        if (isset($info['config'][$configTypeName]['lastchange_config']))
        {
            $t = max($t, $info['config'][$configTypeName]['lastchange_config']);
        }

        if (isset($info['config'][$configTypeName]['lastchange_cmdl']))
        {
            $t = max($t, $info['config'][$configTypeName]['lastchange_cmdl']);
        }

        return $t;
    }

}