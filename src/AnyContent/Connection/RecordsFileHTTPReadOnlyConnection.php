<?php

namespace AnyContent\Connection;

use AnyContent\Client\DataDimensions;
use AnyContent\Connection\Interfaces\ReadOnlyConnection;
use GuzzleHttp\Client;

class RecordsFileHttpReadOnlyConnection extends RecordsFileReadOnlyConnection implements ReadOnlyConnection
{
    /**
     * @return RecordsFileHttpConfiguration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }



    /**
     * @param $fileName
     *
     * @return \GuzzleHttp\Stream\StreamInterface|null
     * @throws ClientException
     */
    protected function readData($fileName)
    {
        $client   = new Client([ 'defaults' => [ 'timeout' => $this->getConfiguration()->getTimeout() ] ]);
        $response = $client->get($fileName);

        return $response->getBody();
    }

    public function getLastModifiedDate($contentTypeName = null, $configTypeName = null, DataDimensions $dataDimensions = null)
    {
        //@upgrade
        return time();
    }
}
