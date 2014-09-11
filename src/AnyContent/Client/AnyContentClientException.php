<?php
namespace AnyContent\Client;

class AnyContentClientException extends \Exception
{

    const ANYCONTENT_UNKNOW_REPOSITORY             = 1;
    const ANYCONTENT_UNKNOW_CONTENT_TYPE           = 2;
    const ANYCONTENT_UNKNOW_CONFIG_TYPE            = 3;
    const CLIENT_UNKNOWN_FILTER_CONDITION_OPERATOR = 4;
    const CLIENT_CONNECTION_ERROR                  = 5;

}