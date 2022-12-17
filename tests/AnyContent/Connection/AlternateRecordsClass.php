<?php

namespace AnyContent\Connection;

use AnyContent\Client\Record;

class AlternateRecordClass extends Record
{
    public function getArticle()
    {
        return $this->getProperty('article');
    }
}
