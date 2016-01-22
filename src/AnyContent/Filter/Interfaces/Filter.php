<?php

namespace AnyContent\Filter\Interfaces;

use AnyContent\Client\Record;

interface Filter
{

    /**
     * @param Record $record
     *
     * @return boolean
     */
    public function match(Record $record);

}