<?php

namespace AnyContent\Client;


use CMDL\Util;

use CMDL\ContentTypeDefinition;



class Sequence implements \Iterator
{

    protected $position = 0;

    protected $contentTypeDefinition = null;

    protected $items = array();


    public function __construct(ContentTypeDefinition $contentTypeDefinition, $values = array())
    {
        $this->contentTypeDefinition = $contentTypeDefinition;

        $i = 0;
        if (is_array($values))
        {
            foreach ($values as $item)
            {

                $this->items[$i++] = array( 'type' => key($item), 'properties' => array_shift($item) );
            }

        }

    }


    public function getProperty($property, $default = null)
    {
        if (array_key_exists($property, $this->items[$this->position]['properties']))
        {
            return $this->items[$this->position]['properties'][$property];
        }
        else
        {
            return $default;
        }
    }


    public function getContentType()
    {
        return $this->contentTypeDefinition->getName();
    }


    function rewind()
    {
        $this->position = 0;
    }


    /**
     * @return $this Sequence
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