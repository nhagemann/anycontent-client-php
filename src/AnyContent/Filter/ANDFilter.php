<?php

namespace AnyContent\Filter;

use AnyContent\Client\Record;
use AnyContent\Filter\Interfaces\Filter;
use AnyContent\Filter\Util\ParensParser;
use CMDL\Util;

class ANDFilter implements Filter
{

    /**
     * @var array Filter
     */
    protected $terms;


    /**
     * @param array $terms
     */
    public function __construct(array $terms)
    {
        $this->terms = $terms;

    }


    public function match(Record $record)
    {
        /** @var PropertyFilter $term */
        foreach ($this->terms as $term)
        {
            if (!$term->match($record))
            {
                return false;
            }

        }

        return true;
    }


    public function __toString()
    {
        return '(' . join(' AND ', $this->terms) . ')';
    }
}