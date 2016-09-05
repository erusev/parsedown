<?php

// prepare composer autoloader
if (is_file(__DIR__ . '/../vendor/autoload.php')) {
    // composer root package
    require_once(__DIR__ . '/../vendor/autoload.php');
} elseif (is_file(__DIR__ . '/../../../../vendor/autoload.php')) {
    // composer dependency package
    require_once(__DIR__ . '/../../../../vendor/autoload.php');
} else {
    die("Cannot find `vendor/autoload.php`. Run `composer install`.");
}

// load TestParsedown class
if (!class_exists('TestParsedown', false) && is_file('test/TestParsedown.php')) {
    require_once('test/TestParsedown.php');
}
