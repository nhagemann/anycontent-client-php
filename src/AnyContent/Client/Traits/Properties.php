<?php

namespace AnyContent\Client\Traits;

use AnyContent\Client\Sequence;
use AnyContent\Client\Table;
use CMDL\CMDLParserException;
use CMDL\DataTypeDefinition;
use CMDL\Util;

trait Properties
{
    /** @var  DataTypeDefinition */
    protected $dataTypeDefinition;

    public $properties = array();

    public function getDataTypeName()
    {
        return $this->dataTypeDefinition->getName();
    }

    public function getDataTypeDefinition()
    {
        return $this->dataTypeDefinition;
    }

    public function getProperty($property, $default = null)
    {
        if (array_key_exists($property, $this->properties)) {
            if ($this->properties[$property] === '') {
                return $default;
            }
            if ($this->properties[$property] === null) {
                return $default;
            }

            return $this->properties[$property];
        } else {
            return $default;
        }
    }

    public function getIntProperty($property, $default = null)
    {
        return (int)$this->getProperty($property, $default);
    }

    public function getBoolProperty($property, $default = null)
    {
        return (bool)$this->getProperty($property, $default);
    }

    public function setBoolProperty($property, $value)
    {
        $value = (int)(bool)$value;
        $this->setProperty($property, $value);
    }

    public function getTable($property)
    {
        $values = json_decode($this->getProperty($property), true);

        if (!is_array($values)) {
            $values = array();
        }

        $formElementDefinition = $this->dataTypeDefinition->getViewDefinition($this->view)
                                                          ->getFormElementDefinition($property);

        $columns = count($formElementDefinition->getList(1));

        $table = new Table($columns);

        foreach ($values as $row) {
            $table->addRow($row);
        }

        return $table;
    }

    public function getArrayProperty($property)
    {
        $value = $this->getProperty($property);
        if ($value) {
            return explode(',', $value);
        }

        return array();
    }

    public function setProperty($property, $value)
    {

        $property = Util::generateValidIdentifier($property);
        if ($this->dataTypeDefinition->hasProperty($property, $this->view)) {
            $this->properties[$property] = (string)$value;
        } else {
            throw new CMDLParserException('Unknown property ' . $property, CMDLParserException::CMDL_UNKNOWN_PROPERTY);
        }

        return $this;
    }

    public function clearProperty($property)
    {
        if (isset($this->properties[$property])) {
            unset($this->properties[$property]);
        }
    }

    public function getSequence($property)
    {
        $values = json_decode($this->getProperty($property), true);

        if (!is_array($values)) {
            $values = array();
        }

        return new Sequence($this->dataTypeDefinition, $property, $values);
    }

    public function setProperties($properties)
    {
        $this->properties = $properties;

        return $this;
    }

    public function getProperties()
    {
        return $this->properties;
    }
}
