<?php

namespace AnyContent\Client;


class Table implements \Iterator, \Countable
{
    protected $position = 0;

    protected $columns = null;

    protected $rows = array();

    public function __construct($columns=1)
    {
        $this->columns =$columns;
    }


    public function addRow($values=array())
    {
        $values = array_slice($values,0,$this->columns);
        $this->rows[]=$values;
    }

    public function getCell($line,$column)
    {
        if ($line>count($this->rows))
        {
            return false;
        }

        if ($column>$this->columns)
        {
            return false;
        }

        // convert to 0 based array
        $line--;
        $column--;

        $value='';

        if (isset($this->rows[$line][$column]))
        {
            $value = $this->rows[$line][$column];
        }

        return $value;
    }

    function rewind()
    {
        $this->position = 0;
    }


    function current()
    {
        return $this->rows[$this->position];
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
        return isset($this->rows[$this->position]);
    }

    function count()
    {
        return count($this->rows);
    }
}