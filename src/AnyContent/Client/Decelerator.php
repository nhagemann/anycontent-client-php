<?php

namespace AnyContent\Client;

use Guzzle\Http\Message\Response;
use Guzzle\Log\LogAdapterInterface;

class Decelerator implements LogAdapterInterface
{

    protected $count = 0;

    protected $msDelayBetweenRequests;


    public function __construct($msDelayBetweenRequests)
    {
        $this->msDelayBetweenRequests = $msDelayBetweenRequests;
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
        if ($this->count > 1)
        {
            usleep($this->msDelayBetweenRequests * 1000);
        }
        $this->count++;
    }

}