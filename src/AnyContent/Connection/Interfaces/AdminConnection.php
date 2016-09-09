<?php

namespace AnyContent\Connection\Interfaces;

interface AdminConnection extends WriteConnection
{

    public function saveContentTypeCMDL($contentTypeName, $cmdl);


    public function saveConfigTypeCMDL($configTypeName, $cmdl);


    public function deleteContentTypeCMDL($contentTypeName);


    public function deleteConfigTypeCMDL($configTypeName);

}