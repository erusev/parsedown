<?php 

include '../Parsedown.php';

$page = $_SERVER['QUERY_STRING']
	? 'test'
	: 'index';

$dir = 'test_data/';

include $page.'_controller.php';
include $page.'_view.php';
