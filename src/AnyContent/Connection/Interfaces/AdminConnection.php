<?php

namespace AnyContent\Connection\Interfaces;

interface AdminConnection extends WriteConnection
{

    public function saveContentTypeCMDL($contentTypeName, $cmdl);


    public function saveConfigTypeCMDL($contentTypeName, $cmdl);


    public function deleteContentTypeCMDL($contentTypeName);


    public function deleteConfigTypeCMDL($contentTypeName);

}