<?php

namespace AnyContent\Connection\Interfaces;

use AnyContent\Client\Record;

interface UniqueConnection
{

    public function isUniqueConnection();


    /**
     * @param int $confidence nr of seconds not checking for any external changes
     *
     * @return UniqueConnection
     */
    public function setUniqueConnection($confidence = 60);


}