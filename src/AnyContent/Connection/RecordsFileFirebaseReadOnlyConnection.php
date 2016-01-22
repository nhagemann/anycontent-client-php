<?php

namespace AnyContent\Connection;

use AnyContent\AnyContentClientException;
use AnyContent\Client\DataDimensions;
use AnyContent\Client\Record;
use AnyContent\Connection\Configuration\RecordsFileFirebaseConfiguration;
use AnyContent\Connection\Interfaces\ReadOnlyConnection;

use Firebase\FirebaseLib;

class RecordsFileFirebaseReadOnlyConnection extends RecordsFileReadOnlyConnection implements ReadOnlyConnection
{

    /** @var  FirebaseLib */
    protected $firebase;

    protected $numberOfSingleRecordFetches = 0;


    /**
     * @param RecordsFileFirebaseConfiguration $configuration
     */
    public function __construct(RecordsFileFirebaseConfiguration $configuration)
    {
        parent::__construct($configuration);

        $firebase       = new FirebaseLib($configuration->getBaseUri(), $configuration->getToken());
        $this->firebase = $firebase;

    }


    /**
     * @return RecordsFileFirebaseConfiguration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }


    protected function readData($fileName)
    {

        $data = $this->firebase->get($this->getConfiguration()->getDefaultPath() . $fileName);

        return $data;
    }


    protected function readCMDL($fileName)
    {

        $data = $this->firebase->get($this->getConfiguration()->getDefaultPath() . $fileName);

        $data = json_decode($data);

        return $data;
    }


    /**
     * @return int
     * @throws AnyContentClientException
     */
    public function countRecords($contentTypeName = null, DataDimensions $dataDimensions = null)
    {
        if ($contentTypeName == null)
        {
            $contentTypeName = $this->getCurrentContentTypeName();
        }
        if ($dataDimensions ==null)
        {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        if (!$this->hasStashedAllRecords($contentTypeName,$dataDimensions,$this->getRecordClassForContentType($contentTypeName)))
        {
            // try to get the count information directly

            $path = $this->getConfiguration()
                         ->getDefaultPath() . $this->getConfiguration()->getUriRecords($contentTypeName) . '/info/count';
            $c    = json_decode($this->firebase->get($path));

            if ($c !== null)
            {
                return $c;
            }
        }

        return count($this->getAllRecords($contentTypeName));

    }



    /**
     * @param $recordId
     *
     * @return Record
     * @throws AnyContentClientException
     */
    public function getRecord($recordId, $contentTypeName = null ,DataDimensions $dataDimensions = null)
    {
        if ($contentTypeName == null)
        {
            $contentTypeName = $this->getCurrentContentTypeName();
        }
        if ($dataDimensions ==null)
        {
            $dataDimensions = $this->getCurrentDataDimensions();
        }

        if (!$this->hasStashedAllRecords($contentTypeName,$dataDimensions,$this->getRecordClassForContentType($contentTypeName)))
        {
            // try to get the record directly
            if ($this->numberOfSingleRecordFetches < $this->getConfiguration()->getMaxNumberOfSingleRecordFetches())
            {
                $this->numberOfSingleRecordFetches++;

                $path = $this->getConfiguration()
                             ->getDefaultPath() . $this->getConfiguration()->getUriRecords($contentTypeName) . '/records/' . $recordId;
                $data = $this->firebase->get($path);

                $data = json_decode($data, true);

                $record = $this->getRecordFactory()->createRecordFromJSON($this->getCurrentContentTypeDefinition(), $data);

                if ($record !== null)
                {
                    return $record;
                }

                throw new AnyContentClientException ('Record ' . $recordId . ' not found for content type ' . $this->getCurrentContentTypeName());
            }

        }

        $records = $this->getAllRecords($this->getCurrentContentTypeName());

        if (array_key_exists($recordId, $records))
        {
            return $records[$recordId];
        }

        throw new AnyContentClientException ('Record ' . $recordId . ' not found for content type ' . $this->getCurrentContentTypeName());
    }

    public function getLastModifiedDate($contentTypeName = null, $configTypeName = null, DataDimensions $dataDimensions = null)
    {
        //@upgrade
        return time();
    }
}