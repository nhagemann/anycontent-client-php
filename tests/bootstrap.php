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

global $testWithCaching;
$testWithCaching = true;

if ($testWithCaching == true)
{
    echo PHP_EOL . PHP_EOL . 'CLIENT CACHE ACTIVATED!! ' . PHP_EOL . PHP_EOL;
}




