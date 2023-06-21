<?php

declare(strict_types=1);

namespace AnyContent\Client;

use CMDL\DataTypeDefinition;

class Sequence implements \Iterator, \Countable
{
    protected $position = 0;

    protected $dataTypeDefinition = null;

    protected $items = array();

    protected $property;

    public function __construct(DataTypeDefinition $dataTypeDefinition, $property, $values = array())
    {
        $this->dataTypeDefinition = $dataTypeDefinition;

        $this->property = $property;

        $i = 0;
        if (is_array($values)) {
            foreach ($values as $item) {
                $this->items[$i++] = array('type' => key($item), 'properties' => array_shift($item));
            }
        }
    }

    public function getProperties()
    {
        if (isset($this->items[$this->position])) {
            return $this->items[$this->position]['properties'];
        }

        return false;
    }

    public function getProperty($property, $default = null)
    {
        if (isset($this->items[$this->position])) {
            if (array_key_exists($property, $this->items[$this->position]['properties'])) {
                if ($this->items[$this->position]['properties'][$property] === '') {
                    return $default;
                }
                if ($this->items[$this->position]['properties'][$property] === null) {
                    return $default;
                }

                return $this->items[$this->position]['properties'][$property];
            } else {
                return $default;
            }
        }

        return false;
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
        } else {
            return false;
        }
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function current(): SequenceItem
    {
        $item = new SequenceItem($this->dataTypeDefinition, $this->property, $this->items[$this->position]['type']);
        $item->setProperties($this->items[$this->position]['properties']);

        return $item;
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
        return isset($this->items[$this->position]);
    }

    public function count(): int
    {
        return (count($this->items));
    }

    public function addItem(SequenceItem $item)
    {
        $this->items[count($this->items)] = array(
            'type' => $item->getItemType(),
            'properties' => $item->getProperties(),
        );
        $this->position = count($this->items);
    }

    public function getPosition()
    {
        return $this->position + 1;
    }

    public function __toString()
    {
        $values = [];
        foreach ($this->items as $item) {
            $values[] = [$item['type'] => $item['properties']];
        }

        return json_encode($values);
    }
}
