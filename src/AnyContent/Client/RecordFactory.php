<?php

namespace AnyContent\Client;

use AnyContent\AnyContentClientException;
use CMDL\ConfigTypeDefinition;
use CMDL\ContentTypeDefinition;
use CMDL\DataTypeDefinition;
use CMDL\Parser;

class RecordFactory
{
    protected $precalculations = [ ];

    /**
     * @var RecordFactory
     */
    private static $instance = null;

    protected $options = ['validateProperties' => true];

    protected $contentRecordClassMap = array();

    protected $configRecordClassMap = array();


    /**
     * @param string $realm
     *
     * @return RecordFactory
     */
    public static function instance($options = [ ])
    {
        if (!self::$instance) {
            self::$instance = new RecordFactory();
        }
        self::$instance->options = array_merge(self::$instance->options, $options);

        return self::$instance;
    }


    public function __construct($options = [ ])
    {
        $this->options = array_merge($this->options, $options);
    }


    public function getOption($option)
    {
        if (array_key_exists($option, $this->options)) {
            return $this->options[$option];
        }
        throw new AnyContentClientException('Missing option ' . $option . ' in RecordFactory');
    }


    public function createRecordsFromJSONRecordsArray(ContentTypeDefinition $contentTypeDefinition, $jsonRecords, $viewName = "default", $workspace = "default", $language = "default")
    {
        $records = [ ];

        $classname = $this->getRecordClassForContentType($contentTypeDefinition->getName());

        /** @var Record $record */
        $masterRecord = new $classname($contentTypeDefinition, '', $viewName, $workspace, $language);

        foreach ($jsonRecords as $jsonRecord) {
            $record = clone $masterRecord;
            $record->setID($jsonRecord['id']);
            $record = $this->finishRecordCreationFromJSON($record, $jsonRecord);

            $records[$record->getID()] = $record;
        }

        return $records;
    }


    /**
     * Creates record object from (array) decoded JSON record (json_decode($json,true))
     *
     * @param DataTypeDefinition $dataTypeDefinition
     * @param                    $jsonRecord
     * @param string             $viewName
     * @param string             $workspace
     * @param string             $language
     *
     * @return Config|Record
     */
    public function createRecordFromJSON(DataTypeDefinition $dataTypeDefinition, $jsonRecord, $viewName = "default", $workspace = "default", $language = "default")
    {

        if ($dataTypeDefinition instanceof ConfigTypeDefinition) {
            $classname = $this->getRecordClassForConfigType($dataTypeDefinition->getName());

            /** @var Config $record */
            $record = new $classname($dataTypeDefinition, $viewName, $workspace, $language);
        } else {
            $classname = $this->getRecordClassForContentType($dataTypeDefinition->getName());

            /** @var Record $record */
            $record = new $classname($dataTypeDefinition, '', $viewName, $workspace, $language);
            $record->setID($jsonRecord['id']);
        }

        $record = $this->finishRecordCreationFromJSON($record, $jsonRecord);


        return $record;
    }


    /**
     *
     * @param $record
     * @param $jsonRecord
     *
     * @return mixed
     * @throws AnyContentClientException
     */
    protected function finishRecordCreationFromJSON(AbstractRecord $record, $jsonRecord)
    {
        $revision = isset($jsonRecord['info']['revision']) ? $jsonRecord['info']['revision'] : 1;
        $record->setRevision($revision);

        if ($this->getOption('validateProperties') == true) {
            foreach ($jsonRecord['properties'] as $property => $value) {
                $record->setProperty($property, $value);
            }
        } else {
            $record->setProperties($jsonRecord['properties']);
        }

        if (isset($jsonRecord['info'])) {
            if (isset($jsonRecord['info']['creation'])) {
                $record->setCreationUserInfo(new UserInfo($jsonRecord['info']['creation']['username'], $jsonRecord['info']['creation']['firstname'], $jsonRecord['info']['creation']['lastname'], $jsonRecord['info']['creation']['timestamp']));
            }
            if (isset($jsonRecord['info']['lastchange'])) {
                $record->setLastChangeUserInfo(new UserInfo($jsonRecord['info']['lastchange']['username'], $jsonRecord['info']['lastchange']['firstname'], $jsonRecord['info']['lastchange']['lastname'], $jsonRecord['info']['lastchange']['timestamp']));
            }
        }

        return $record;
    }

    public function createRecord(ContentTypeDefinition $contentTypeDefinition, $properties = [ ], $viewName = "default", $workspace = "default", $language = "default")
    {
        $classname = $this->getRecordClassForContentType($contentTypeDefinition->getName());

        /** @var Record $record */
        $record = new $classname($contentTypeDefinition, '', $viewName, $workspace, $language);

        $record->setRevision(0);

        if ($this->getOption('validateProperties') == true) {
            foreach ($properties as $property => $value) {
                $record->setProperty($property, $value);
            }
        } else {
            $record->setProperties($properties);
        }

        return $record;
    }


    public function createRecordFromCMDL($cmdl, $properties = [ ], $viewName = "default", $workspace = "default", $language = "default")
    {
        $contentTypeDefinition = Parser::parseCMDLString($cmdl);

        /** @var Record $record */
        $record = new Record($contentTypeDefinition, '', $viewName, $workspace, $language);

        $record->setRevision(0);

        if ($this->getOption('validateProperties') == true) {
            foreach ($properties as $property => $value) {
                $record->setProperty($property, $value);
            }
        } else {
            $record->setProperties($properties);
        }

        return $record;
    }


    public function createConfig(ConfigTypeDefinition $configTypeDefinition, $properties = [ ], $viewName = "default", $workspace = "default", $language = "default")
    {
        $classname = $this->getRecordClassForConfigType($configTypeDefinition->getName());

        /** @var Config $config */
        $config = new $classname($configTypeDefinition, $viewName, $workspace, $language);

        $config->setRevision(0);

        if ($this->getOption('validateProperties') == true) {
            foreach ($properties as $property => $value) {
                $config->setProperty($property, $value);
            }
        } else {
            $config->setProperties($properties);
        }

        return $config;
    }


    public function registerRecordClassForContentType($contentTypeName, $classname)
    {

        $this->contentRecordClassMap[$contentTypeName] = $classname;
    }


    public function getRecordClassForContentType($contentTypeName)
    {

        if (array_key_exists($contentTypeName, $this->contentRecordClassMap)) {
            return $this->contentRecordClassMap[$contentTypeName];
        }

        return 'AnyContent\Client\Record';
    }


    public function registerRecordClassForConfigType($configTypeName, $classname)
    {

        $this->configRecordClassMap[$configTypeName] = $classname;
    }


    public function getRecordClassForConfigType($configTypeName)
    {

        if (array_key_exists($configTypeName, $this->configRecordClassMap)) {
            return $this->configRecordClassMap[$configTypeName];
        }

        return 'AnyContent\Client\Config';
    }
}
