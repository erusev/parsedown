<?php

$test = $_SERVER['QUERY_STRING'];

preg_match('/^\w+$/', $test) or die('illegal test name');

$md_file = $dir.$test.'.md';
$mu_file = $dir.$test.'.html';

file_exists($md_file) or die("$md_file not found");
file_exists($mu_file) or die("$mu_file not found");

$md = file_get_contents($md_file);

$expected_mu = file_get_contents($mu_file);
$actual_mu = Parsedown::instance()->parse($md);

$result = $expected_mu === $actual_mu
	? 'pass'
	: 'fail';

$md = htmlentities($md, ENT_NOQUOTES);
$expected_mu = htmlentities($expected_mu, ENT_NOQUOTES);
$actual_mu = htmlentities($actual_mu, ENT_NOQUOTES);

$name = str_replace('_', ' ', $test);
$name = ucwords($name);