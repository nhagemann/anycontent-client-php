<?php

namespace AnyContent\Client;

use CMDL\DataTypeDefinition;
use CMDL\Util;

use CMDL\ContentTypeDefinition;

class Sequence implements \Iterator
{

    protected $position = 0;

    protected $dataTypeDefinition = null;

    protected $items = array();

    public function __construct(DataTypeDefinition $dataTypeDefinition, $values = array())
    {
        $this->dataTypeDefinition = $dataTypeDefinition;

        $i = 0;
        if (is_array($values)) {
            foreach ($values as $item) {

                $this->items[$i++] = array('type' => key($item), 'properties' => array_shift($item));
            }
        }
    }

    public function getProperties()
    {
        return $this->items[$this->position]['properties'];
    }

    public function getProperty($property, $default = null)
    {
        if (array_key_exists($property, $this->items[$this->position]['properties'])) {
            return $this->items[$this->position]['properties'][$property];
        }
        else {
            return $default;
        }
    }

    public function getContentType()
    {
        return $this->dataTypeDefinition->getName();
    }

    public function getDataType()
    {
        return $this->dataTypeDefinition->getName();
    }

    public function getItemType()
    {
        return $this->items[$this->position]['type'];
    }

    public function getConfigType()
    {
        if (get_class($this->dataTypeDefinition) == 'CMDL\ConfigTypeDefinition') {
            return $this->dataTypeDefinition->getName();
        }
        else {
            return false;
        }
    }

    function rewind()
    {
        $this->position = 0;
    }

    /**
     * @return Sequence
     */
    function current()
    {
        return $this;
    }

    function key()
    {
        return $this->position;
    }

    function next()
    {
        ++$this->position;
    }

    function valid()
    {
        return isset($this->items[$this->position]);
    }
}
