<?php

declare(strict_types=1);

namespace AnyContent\Client;

class Table implements \Iterator, \Countable
{
    protected $position = 0;

    protected $columns = null;

    protected $rows = array();

    public function __construct($columns = 1)
    {
        $this->columns = $columns;
    }

    public function addRow($values = array())
    {
        $values       = array_slice($values, 0, $this->columns);
        $this->rows[] = $values;
    }

    public function setRow($line, $values = array())
    {
        if ($line > count($this->rows)) {
            return false;
        }

        // convert to 0 based array
        $line--;

        $values            = array_slice($values, 0, $this->columns);
        $this->rows[$line] = $values;
    }

    public function getCell($line, $column)
    {
        if ($line > count($this->rows)) {
            return false;
        }

        if ($column > $this->columns) {
            return false;
        }

        // convert to 0 based array
        $line--;
        $column--;

        $value = '';

        if (isset($this->rows[$line][$column])) {
            $value = $this->rows[$line][$column];
        }

        return $value;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function current(): mixed
    {
        return $this->rows[$this->position];
    }

    public function key(): mixed
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return isset($this->rows[$this->position]);
    }

    public function count(): int
    {
        return count($this->rows);
    }

    public function getProperty()
    {
        return json_encode($this->rows);
    }
}
