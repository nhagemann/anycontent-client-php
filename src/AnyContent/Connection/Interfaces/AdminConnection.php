<?php

namespace AnyContent\Connection\Interfaces;

interface AdminConnection extends WriteConnection
{

    /**
     * @param $contentTypeName
     * @param $cmdl
     *
     * @return boolean
     */
    public function saveContentTypeCMDL($contentTypeName, $cmdl);


    /**
     * @param $configTypeName
     * @param $cmdl
     *
     * @return boolean
     */
    public function saveConfigTypeCMDL($configTypeName, $cmdl);


    /**
     * @param $contentTypeName
     *
     * @return boolean
     */
    public function deleteContentTypeCMDL($contentTypeName);


    /**
     * @param $configTypeName
     *
     * @return boolean
     */
    public function deleteConfigTypeCMDL($configTypeName);

}