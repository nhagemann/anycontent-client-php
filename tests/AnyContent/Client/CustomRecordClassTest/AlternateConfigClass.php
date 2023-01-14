<?php

namespace Tests\AnyContent\Client\CustomRecordClassTest;

use AnyContent\Client\Config;

class AlternateConfigClass extends Config
{
    public function getCity()
    {
        return $this->getProperty('city');
    }
}
