<?php

namespace AnyContent\Client;

use Guzzle\Http\Message\Response;
use Guzzle\Log\LogAdapterInterface;

class Logger implements LogAdapterInterface
{

    protected $client;


    public function __construct(Client $client)
    {
        $this->client = $client;
    }


    /**
     * http://guzzle3.readthedocs.org/plugins/log-plugin.html
     *
     * @param string $message
     * @param int    $priority
     * @param array  $extras
     */
    public function log($message, $priority = LOG_INFO, $extras = array())
    {

        $tokens = explode('|', $message);

        /** @var Response $response */
        $response = $extras['response'];

        $size = $response->getContentLength();

        $this->client->log($tokens[0], false, $tokens['1'], (int)($tokens['2'] * 1000), $size);
    }

}