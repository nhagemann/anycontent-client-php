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

        /*
        $contentTypeDefinition = Parser::parseCMDLString('Title = textfield {name}
Date = date
Article = textarea
Source
');     */



        $cmdl = $client->getCMDL('example01');


        $contentTypeDefinition = Parser::parseCMDLString($cmdl);
        $contentTypeDefinition->setName('example01');

        $record = new Record($contentTypeDefinition, 'New Record');

        $client->saveRecord($record);

    }
}