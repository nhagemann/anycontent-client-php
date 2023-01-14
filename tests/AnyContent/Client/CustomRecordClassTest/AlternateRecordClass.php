<?php

namespace Tests\AnyContent\Client\CustomRecordClassTest;

use AnyContent\Client\Record;

class AlternateRecordClass extends Record
{
    public function getArticle()
    {
        return $this->getProperty('article');
    }
}
