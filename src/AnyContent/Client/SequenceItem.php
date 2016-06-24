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
        if (!$definition->hasProperty($property))
        {
            throw new AnyContentClientException('Unknown sequence property ' . $property . ' for data type ' . $definition->getName());
        }

        /** @var SequenceFormElementDefinition $formElementDefinition */
        $formElementDefinition = false;
        foreach ($definition->getViewDefinitions() as $viewDefinition)
        {
            if ($viewDefinition->hasProperty($property))
            {
                $formElementDefinition = $viewDefinition->getFormElementDefinition($property);
                $this->type            = $type;
                break;
            }
        }

        if (!$formElementDefinition)
        {
            throw new AnyContentClientException('Unexpected error. Could not find form element definition for sequence ' . $property);
        }

        if (!$formElementDefinition->hasInsert($type))
        {
            throw new AnyContentClientException('Unknown insert type ' . $type . ' for sequence ' . $property . ' of data type ' . $definition->getName());
        }
    }


    public function setProperty($property, $value)
    {

        $property = Util::generateValidIdentifier($property);
        if ($this->dataTypeDefinition->getClippingDefinition($this->type)->hasProperty($property))
        {
            $this->properties[$property] = $value;
        }
        else
        {
            throw new CMDLParserException('Unknown property ' . $property, CMDLParserException::CMDL_UNKNOWN_PROPERTY);
        }

        return $this;
    }


    public function getItemType()
    {
        return $this->type;
    }

}
