<?php

namespace AnyContent\Client;

use CMDL\Parser;

use AnyContent\Client\Client;
use AnyContent\Client\Record;

class RepositoryManagerTest extends \PHPUnit_Framework_TestCase
{

    public function testSaveRecord()
    {

        $client = new Client('http://anycontent.dev/1/example', 'admin', 'admin');
        $client->setUserInfo('mail@nilshagemann.de', 'Nils', 'Hagemann');

        $cmdl = $client->getCMDL('example01');

        $contentTypeDefinition = Parser::parseCMDLString($cmdl);
        $contentTypeDefinition->setName('example01');

        $record = new Record($contentTypeDefinition, 'New Record');
        $record->setID(12);
        $record->setProperty('article','bla');

        $client->saveRecord($record);

    }
}