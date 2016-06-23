<?php

namespace AnyContent\Client;

use CMDL\DataTypeDefinition;
use CMDL\Util;

use CMDL\ContentTypeDefinition;

class Sequence implements \Iterator, \Countable
{

    protected $position = 0;

    protected $dataTypeDefinition = null;

    protected $items = array();


    public function __construct(DataTypeDefinition $dataTypeDefinition, $values = array())
    {
        $this->dataTypeDefinition = $dataTypeDefinition;

        $i = 0;
        if (is_array($values))
        {
            foreach ($values as $item)
            {

                $this->items[$i++] = array( 'type' => key($item), 'properties' => array_shift($item) );
            }
        }
    }


    public function getProperties()
    {
        return $this->items[$this->position]['properties'];
    }


    public function getProperty($property, $default = null)
    {
        if (array_key_exists($property, $this->items[$this->position]['properties']))
        {

            if ($this->items[$this->position]['properties'][$property] === '')
            {
                return $default;
            }
            if ($this->items[$this->position]['properties'][$property] === null)
            {
                return $default;
            }

            return $this->items[$this->position]['properties'][$property];
        }
        else
        {
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
        if (get_class($this->dataTypeDefinition) == 'CMDL\ConfigTypeDefinition')
        {
            return $this->dataTypeDefinition->getName();
        }
        else
        {
            return false;
        }
    }


    public function rewind()
    {
        $this->position = 0;
    }


    /**
     * @return Sequence
     */
    public function current()
    {
        return $this;
    }


    public function key()
    {
        return $this->position;
    }


    public function next()
    {
        ++$this->position;
    }


    public function valid()
    {
        return isset($this->items[$this->position]);
    }


    public function count()
    {
        return (count($this->items));
    }
}
