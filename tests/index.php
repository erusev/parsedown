<?php 

error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
ini_set('display_errors', 'true');

include '../Parsedown.php';

$page = $_SERVER['QUERY_STRING']
	? 'test'
	: 'index';

$dir = 'tests/';

include $page.'_controller.php';
include $page.'_view.php';