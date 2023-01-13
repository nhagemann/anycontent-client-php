<?php

namespace AnyContent\Connection;

use AnyContent\Client\Config;
use AnyContent\Client\DataDimensions;
use AnyContent\Client\Record;
use AnyContent\Connection\Interfaces\AdminConnection;
use AnyContent\Connection\Interfaces\WriteConnection;
use GuzzleHttp\Exception\ClientException;

class RestLikeBasicReadWriteConnection extends RestLikeBasicReadOnlyConnection implements WriteConnection, AdminConnection
{
    public function saveRecord(Record $record, DataDimensions $dataDimensions = null)
    {
        if (!$dataDimensions) {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        $this->finalizeRecord($record, $dataDimensions);

        unset($this->repositoryInfo[(string)$dataDimensions]);

        $url = 'content/' . $record->getContentTypeName() . '/records/' . $dataDimensions->getWorkspace() . '/' . $dataDimensions->getViewName();

        $record = $record->setLastChangeUserInfo($this->userInfo);

        $this->getClient()->setDefaultOption(
            'query',
            [
                'userinfo' => [
                    'username'  => $this->userInfo->getUsername(),
                    'firstname' => $this->userInfo->getFirstname(),
                    'lastname'  => $this->userInfo->getLastname(),
                ],
            ]
        );

        $response = $this->getClient()
            ->post($url, [
                'body' => [
                    'record'   => json_encode($record),
                    'language' => $dataDimensions->getLanguage(),
                ],
            ]);

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
        if (count($records) > 0) {
            if (!$dataDimensions) {
                $dataDimensions = $this->getCurrentDataDimensions();
            }
            unset($this->repositoryInfo[(string)$dataDimensions]);

            $record = reset($records);

            foreach ($records as $record) {
                $record = $record->setLastChangeUserInfo($this->userInfo);
                $this->stashRecord($record, $dataDimensions);
            }

            $url = 'content/' . $record->getContentTypeName() . '/records/' . $dataDimensions->getWorkspace() . '/' . $dataDimensions->getViewName();

            $this->getClient()->setDefaultOption(
                'query',
                [
                    'userinfo' => [
                        'username'  => $this->userInfo->getUsername(),
                        'firstname' => $this->userInfo->getFirstname(),
                        'lastname'  => $this->userInfo->getLastname(),
                    ],
                ]
            );

            $response = $this->getClient()
                ->post($url, [
                    'body' => [
                        'records'  => json_encode($records),
                        'language' => $dataDimensions->getLanguage(),
                    ],
                ]);

            return $response->json();
        }

        return [];
    }

    public function deleteRecord($recordId, $contentTypeName = null, DataDimensions $dataDimensions = null)
    {
        if (!$dataDimensions) {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        if (!$contentTypeName) {
            $contentTypeName = $this->getCurrentContentTypeName();
        }

        unset($this->repositoryInfo[(string)$dataDimensions]);
        $this->unstashRecord($contentTypeName, $recordId, $dataDimensions);

        $url = 'content/' . $contentTypeName . '/record/' . $recordId . '/' . $dataDimensions->getWorkspace() . '?language=' . $dataDimensions->getLanguage();

        $this->getClient()->setDefaultOption(
            'query',
            [
                'userinfo' => [
                    'username'  => $this->userInfo->getUsername(),
                    'firstname' => $this->userInfo->getFirstname(),
                    'lastname'  => $this->userInfo->getLastname(),
                ],
            ]
        );

        $response = $this->getClient()->delete($url);

        if ($response->json() == true) {
            return $recordId;
        }

        return false;
    }

    public function deleteRecords(array $recordsIds, $contentTypeName = null, DataDimensions $dataDimensions = null)
    {
        if (!$dataDimensions) {
            $dataDimensions = $this->getCurrentDataDimensions();
        }
        if (!$contentTypeName) {
            $contentTypeName = $this->getCurrentContentTypeName();
        }

        $recordIds = [];
        foreach ($recordsIds as $recordId) {
            if ($this->deleteRecord($recordId, $contentTypeName, $dataDimensions)) {
                $recordIds[] = $recordId;
            }
        }

        return $recordIds;
    }

    public function deleteAllRecords($contentTypeName = null, DataDimensions $dataDimensions = null)
    {
        if (!$dataDimensions) {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        if (!$contentTypeName) {
            $contentTypeName = $this->getCurrentContentTypeName();
        }

        unset($this->repositoryInfo[(string)$dataDimensions]);
        $this->unstashAllRecords($contentTypeName, $dataDimensions);

        $url = 'content/' . $contentTypeName . '/records/' .
            $dataDimensions->getWorkspace() . '?language=' . $dataDimensions->getLanguage() . '&view=' . $dataDimensions->getViewName() . '&timeshift=' . $dataDimensions->getTimeShift();

        $this->getClient()->setDefaultOption(
            'query',
            [
                'userinfo' => [
                    'username'  => $this->userInfo->getUsername(),
                    'firstname' => $this->userInfo->getFirstname(),
                    'lastname'  => $this->userInfo->getLastname(),
                ],
            ]
        );

        $response = $this->getClient()->delete($url);

        return $response->json();
    }

    public function saveConfig(Config $config, DataDimensions $dataDimensions = null)
    {
        if (!$dataDimensions) {
            $dataDimensions = $this->getCurrentDataDimensions();
        }
        unset($this->repositoryInfo[(string)$dataDimensions]);

        $this->finalizeRecord($config, $dataDimensions);

        $url = 'config/' . $config->getConfigTypeName() . '/record/' . $dataDimensions->getWorkspace();

        $config->setLastChangeUserInfo($this->userInfo);

        $this->getClient()->setDefaultOption(
            'query',
            [
                'userinfo' => [
                    'username'  => $this->userInfo->getUsername(),
                    'firstname' => $this->userInfo->getFirstname(),
                    'lastname'  => $this->userInfo->getLastname(),
                ],
            ]
        );

        $this->getClient()
            ->post($url, ['body' => ['record' => json_encode($config), 'language' => $dataDimensions->getLanguage()]]);

        $this->stashConfig($config, $dataDimensions);

        return true;
    }

    /**
     *
     *
     *   // update cmdl for a content type / create content type
     * $app->post('/1/{repositoryName}/content/{contentTypeName}/cmdl', 'AnyContent\Repository\Modules\Core\Repositories\RepositoryController::postContentTypeCMDL');
     * $app->post('/1/{repositoryName}/content/{contentTypeName}/cmdl/{locale}', 'AnyContent\Repository\Modules\Core\Repositories\RepositoryController::postContentTypeCMDL');
     *
     * // delete content type
     * $app->delete('/1/{repositoryName}/content/{contentTypeName}', 'AnyContent\Repository\Modules\Core\Repositories\RepositoryController::deleteContentType');
     *
     * // update cmdl for a config type / create config type
     * $app->post('/1/{repositoryName}/config/{configTypeName}/cmdl', 'AnyContent\Repository\Modules\Core\Repositories\RepositoryController::postConfigTypeCMDL');
     * $app->post('/1/{repositoryName}/config/{configTypeName}/cmdl/{locale}', 'AnyContent\Repository\Modules\Core\Repositories\RepositoryController::postConfigTypeCMDL');
     *
     * // delete config type
     * $app->delete('/1/{repositoryName}/config/{configTypeName}', 'AnyContent\Repository\Modules\Core\Repositories\RepositoryController::deleteConfigType');
     */

    /**
     * @param $contentTypeName
     * @param $cmdl
     *
     * @return boolean
     */
    public function saveContentTypeCMDL($contentTypeName, $cmdl)
    {
        $this->repositoryInfo = [];

        $url = 'content/' . $contentTypeName . '/cmdl';

        $this->getClient()->setDefaultOption(
            'query',
            [
                'userinfo' => [
                    'username'  => $this->userInfo->getUsername(),
                    'firstname' => $this->userInfo->getFirstname(),
                    'lastname'  => $this->userInfo->getLastname(),
                ],
            ]
        );

        $this->getClient()->post($url, ['body' => ['cmdl' => $cmdl]]);

        $contentTypeNames = $this->getConfiguration()->getContentTypeNames();

        $contentTypeNames[] = $contentTypeName;

        $this->getConfiguration()->addContentTypes($contentTypeNames);

        $this->getCMDLCache()->clear();

        return true;
    }

    /**
     * @param $configTypeName
     * @param $cmdl
     *
     * @return boolean
     */
    public function saveConfigTypeCMDL($configTypeName, $cmdl)
    {
        $this->repositoryInfo = [];

        $url = 'config/' . $configTypeName . '/cmdl';

        $this->getClient()->setDefaultOption(
            'query',
            [
                'userinfo' => [
                    'username'  => $this->userInfo->getUsername(),
                    'firstname' => $this->userInfo->getFirstname(),
                    'lastname'  => $this->userInfo->getLastname(),
                ],
            ]
        );

        $this->getClient()->post($url, ['body' => ['cmdl' => $cmdl]]);

        $configTypeNames = $this->getConfiguration()->getConfigTypeNames();

        $configTypeNames[] = $configTypeName;

        $this->getConfiguration()->addConfigTypes($configTypeNames);

        $this->getCMDLCache()->clear();

        return true;
    }

    /**
     * @param $contentTypeName
     *
     * @return boolean
     */
    public function deleteContentTypeCMDL($contentTypeName)
    {
        $this->repositoryInfo = [];

        $this->getCMDLCache()->clear();

        try {
            $url = 'content/' . $contentTypeName;

            $this->getClient()->setDefaultOption(
                'query',
                [
                    'userinfo' => [
                        'username'  => $this->userInfo->getUsername(),
                        'firstname' => $this->userInfo->getFirstname(),
                        'lastname'  => $this->userInfo->getLastname(),
                    ],
                ]
            );

            $this->getClient()->delete($url);

            $contentTypeNames = $this->getConfiguration()->getContentTypeNames();

            if (($key = array_search($contentTypeName, $contentTypeNames)) !== false) {
                unset($contentTypeNames[$key]);
            }

            $this->getConfiguration()->addContentTypes($contentTypeNames);

            $this->getCMDLCache()->clear();

            return true;
        } catch (ClientException $e) {
            return false;
        }
    }

    /**
     * @param $configTypeName
     *
     * @return boolean
     */
    public function deleteConfigTypeCMDL($configTypeName)
    {
        $this->repositoryInfo = [];

        $this->getCMDLCache()->clear();

        try {
            $url = 'config/' . $configTypeName;

            $this->getClient()->setDefaultOption(
                'query',
                [
                    'userinfo' => [
                        'username'  => $this->userInfo->getUsername(),
                        'firstname' => $this->userInfo->getFirstname(),
                        'lastname'  => $this->userInfo->getLastname(),
                    ],
                ]
            );

            $this->getClient()->delete($url);

            $configTypeNames = $this->getConfiguration()->getConfigTypeNames();

            if (($key = array_search($configTypeName, $configTypeNames)) !== false) {
                unset($configTypeNames[$key]);
            }

            $this->getConfiguration()->addConfigTypes($configTypeNames);

            $this->getCMDLCache()->clear();

            return true;
        } catch (ClientException $e) {
            return false;
        }
    }
}
