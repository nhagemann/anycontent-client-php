<?php

declare(strict_types=1);

namespace AnyContent\Client;

use CMDL\ConfigTypeDefinition;
use CMDL\DataTypeDefinition;

class Config extends AbstractRecord implements \JsonSerializable
{
    protected ?DataTypeDefinition $dataTypeDefinition = null;

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

    public function getConfigTypeDefinition(): ConfigTypeDefinition
    {
        return $this->dataTypeDefinition;
    }

    public function jsonSerialize(): array
    {
        $record                       = [ ];
        $record['properties']         = $this->getProperties();
        $record['info']               = [ ];
        $record['info']['revision']   = $this->getRevision();
        $record['info']['lastchange'] = $this->getLastChangeUserInfo();

        return $record;
    }
}
