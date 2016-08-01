<?php

namespace AnyContent\Connection;

use AnyContent\AnyContentClientException;
use AnyContent\Client\Config;
use AnyContent\Client\DataDimensions;
use AnyContent\Client\Record;
use AnyContent\Connection\Interfaces\WriteConnection;

class RestLikeBasicReadWriteConnection extends RestLikeBasicReadOnlyConnection implements WriteConnection
{

    public function saveRecord(Record $record, DataDimensions $dataDimensions = null)
    {
        if (!$dataDimensions)
        {
            $dataDimensions = $this->getCurrentDataDimensions();
        }
        unset($this->repositoryInfo[(string)$dataDimensions]);

        $url = 'content/' . $record->getContentTypeName() . '/records/' . $dataDimensions->getWorkspace() . '/' . $dataDimensions->getViewName();

        $record = $record->setLastChangeUserInfo($this->userInfo);

        $this->getClient()->setDefaultOption('query',
                                             [ 'userinfo' => [ 'username'  => $this->userInfo->getUsername(),
                                                               'firstname' => $this->userInfo->getFirstname(),
                                                               'lastname'  => $this->userInfo->getLastname()
                                             ]
                                             ]);

        $response = $this->getClient()
                         ->post($url, [ 'body' => [ 'record' => json_encode($record), 'language' => $dataDimensions->getLanguage() ] ]);

        $id = $response->json();
        $record->setId($id);

        $this->stashRecord($record, $dataDimensions);

        return $response->json();

    }


    /**
     * @param Record[] $records
     *
     * @return mixed
     * @throws AnyContentClientException
     */
    public function saveRecords(array $records, DataDimensions $dataDimensions = null)
    {

        if (count($records) > 0)
        {
            if (!$dataDimensions)
            {
                $dataDimensions = $this->getCurrentDataDimensions();
            }
            unset($this->repositoryInfo[(string)$dataDimensions]);

            $record = reset($records);

            foreach ($records as $record)
            {
                $record = $record->setLastChangeUserInfo($this->userInfo);
                $this->stashRecord($record, $dataDimensions);
            }

            $url = 'content/' . $record->getContentTypeName() . '/records/' . $dataDimensions->getWorkspace() . '/' . $dataDimensions->getViewName();

            $this->getClient()->setDefaultOption('query',
                                                 [ 'userinfo' => [ 'username'  => $this->userInfo->getUsername(),
                                                                   'firstname' => $this->userInfo->getFirstname(),
                                                                   'lastname'  => $this->userInfo->getLastname()
                                                 ]
                                                 ]);

            $response = $this->getClient()
                             ->post($url, [ 'body' => [ 'records' => json_encode($records), 'language' => $dataDimensions->getLanguage() ] ]);

            return $response->json();
        }

        return [ ];
    }


    public function deleteRecord($recordId, $contentTypeName = null, DataDimensions $dataDimensions = null)
    {
        if (!$dataDimensions)
        {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        if (!$contentTypeName)
        {
            $contentTypeName = $this->getCurrentContentTypeName();
        }

        unset($this->repositoryInfo[(string)$dataDimensions]);
        $this->unstashRecord($contentTypeName, $recordId, $dataDimensions);

        $url = 'content/' . $contentTypeName . '/record/' . $recordId . '/' . $dataDimensions->getWorkspace() . '?language=' . $dataDimensions->getLanguage();

        $this->getClient()->setDefaultOption('query',
                                             [ 'userinfo' => [ 'username'  => $this->userInfo->getUsername(),
                                                               'firstname' => $this->userInfo->getFirstname(),
                                                               'lastname'  => $this->userInfo->getLastname()
                                             ]
                                             ]);

        $response = $this->getClient()->delete($url);

        if ($response->json() == true)
        {
            return $recordId;
        }

        return false;
    }


    public function deleteRecords(array $recordsIds, $contentTypeName = null, DataDimensions $dataDimensions = null)
    {

        if (!$dataDimensions)
        {
            $dataDimensions = $this->getCurrentDataDimensions();
        }
        if (!$contentTypeName)
        {
            $contentTypeName = $this->getCurrentContentTypeName();
        }

        $recordIds = [ ];
        foreach ($recordsIds as $recordId)
        {
            if ($this->deleteRecord($recordId, $contentTypeName, $dataDimensions))
            {
                $recordIds[] = $recordId;
            }
        }

        return $recordIds;

    }


    public function deleteAllRecords($contentTypeName = null, DataDimensions $dataDimensions = null)
    {
        if (!$dataDimensions)
        {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        if (!$contentTypeName)
        {
            $contentTypeName = $this->getCurrentContentTypeName();
        }

        unset($this->repositoryInfo[(string)$dataDimensions]);
        $this->unstashAllRecords($contentTypeName, $dataDimensions);

        $url = 'content/' . $contentTypeName . '/records/' . $dataDimensions->getWorkspace() . '?language=' . $dataDimensions->getLanguage() . '&view=' . $dataDimensions->getViewName() . '&timeshift=' . $dataDimensions->getTimeShift();

        $this->getClient()->setDefaultOption('query',
                                             [ 'userinfo' => [ 'username'  => $this->userInfo->getUsername(),
                                                               'firstname' => $this->userInfo->getFirstname(),
                                                               'lastname'  => $this->userInfo->getLastname()
                                             ]
                                             ]);

        $response = $this->getClient()->delete($url);

        return $response->json();
    }


    public function saveConfig(Config $config, DataDimensions $dataDimensions = null)
    {
        if (!$dataDimensions)
        {
            $dataDimensions = $this->getCurrentDataDimensions();
        }
        unset($this->repositoryInfo[(string)$dataDimensions]);

        $url = 'config/' . $config->getConfigTypeName() . '/record/' . $dataDimensions->getWorkspace();

        $config->setLastChangeUserInfo($this->userInfo);

        $this->getClient()->setDefaultOption('query',
                                             [ 'userinfo' => [ 'username'  => $this->userInfo->getUsername(),
                                                               'firstname' => $this->userInfo->getFirstname(),
                                                               'lastname'  => $this->userInfo->getLastname()
                                             ]
                                             ]);

        $this->getClient()
             ->post($url, [ 'body' => [ 'record' => json_encode($config), 'language' => $dataDimensions->getLanguage() ] ]);

        $this->stashConfig($config, $dataDimensions);

        return true;
    }

}