<?php

$loader = require __DIR__ . "/../vendor/autoload.php";
$loader->add('AnyContent\tests', __DIR__);

if (!function_exists('apc_exists'))
{
    function apc_exists($keys)
    {
        $result = false;
        apc_fetch($keys, $result);

        return $result;
    }
}


if (file_exists(__DIR__.'/_credentials.php'))
{
    require_once(__DIR__.'/_credentials.php');
}





