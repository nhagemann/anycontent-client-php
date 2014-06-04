<?php

$loader = require __DIR__ . "/../vendor/autoload.php";
$loader->add('AnyContent\tests', __DIR__);

if(!function_exists('apc_exists'))
{
    function ap1c_exists($keys)
    {
        $result = false;
        apc_fetch($keys, $result);
        return $result;
    }
}