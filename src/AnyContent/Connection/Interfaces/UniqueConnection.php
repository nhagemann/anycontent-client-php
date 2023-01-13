<?php

namespace AnyContent\Connection\Interfaces;

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
