<?php

namespace AnyContent\Client;

use CMDL\ConfigTypeDefinition;

class Config extends AbstractRecord implements \JsonSerializable
{

    /**
     * @var ConfigTypeDefinition
     */
    protected $dataTypeDefinition = null;


    public function __construct(ConfigTypeDefinition $configTypeDefinition, $view = 'default', $workspace = 'default', $language = 'default')
    {
        $this->dataTypeDefinition = $configTypeDefinition;

        $this->workspace = $workspace;
        $this->language  = $language;
        $this->view      = $view;
    }


    public function getDataType()
    {
        return 'config';
    }


    public function getConfigTypeName()
    {
        return $this->dataTypeDefinition->getName();
    }


    public function getConfigTypeDefinition()
    {
        return $this->dataTypeDefinition;
    }


    function jsonSerialize()
    {
        $record                       = [ ];
        $record['properties']         = $this->getProperties();
        $record['info']               = [ ];
        $record['info']['revision']   = $this->getRevision();
        $record['info']['lastchange'] = $this->getLastChangeUserInfo();

        return $record;
    }
}