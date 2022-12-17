<?php

$loader = require __DIR__ . "/../vendor/autoload.php";
$loader->add('AnyContent\tests', __DIR__);

if (file_exists(__DIR__ . '/_credentials.php')) {
    require_once(__DIR__ . '/_credentials.php');
}
