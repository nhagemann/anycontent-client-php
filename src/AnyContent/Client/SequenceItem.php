<?php

namespace AnyContent\Client;

use AnyContent\AnyContentClientException;
use AnyContent\Client\Traits\Properties;
use CMDL\CMDLParserException;
use CMDL\DataTypeDefinition;
use CMDL\FormElementDefinitions\SequenceFormElementDefinition;
use CMDL\Util;
use CMDL\ViewDefinition;

class SequenceItem
{
    use Properties;

    /** @var  ViewDefinition */
    protected $viewDefinition;

    protected $type;


    public function __construct(DataTypeDefinition $definition, $property, $type)
    {
        $this->dataTypeDefinition = $definition;
        if (!$definition->hasProperty($property)) {
            throw new AnyContentClientException('Unknown sequence property ' . $property . ' for data type ' . $definition->getName());
        }

        $this->type            = $type;
    }


    public function setProperty($property, $value)
    {

        $property = Util::generateValidIdentifier($property);
        if ($this->dataTypeDefinition->getClippingDefinition($this->type)->hasProperty($property)) {
            $this->properties[$property] = $value;
        } else {
            throw new CMDLParserException('Unknown property ' . $property, CMDLParserException::CMDL_UNKNOWN_PROPERTY);
        }

        return $this;
    }


    public function getItemType()
    {
        return $this->type;
    }
}
