<?php

if(!isset($_GET['action'])) {
	// TODO
	die();
}
$action = $_GET['action'];

$format = 'xml';
if(isset($_GET['format'])) {
	switch($_GET['format']) {
		case 'xml':
		case 'json':
			$format = $_GET['format'];
			break;

		default:
			// TODO
			die();
	}
}

$http_auth = true;

chdir(dirname(__FILE__));
require_once('common.php');
chdir(dirname(__FILE__));

switch($action) {
	case 'status':
	case 'submit':
		require_once("actions/$action.php");
		break;

	default:
		// TODO
		die();
}

ob_start();
if(isset($success) && $success) {
	require_once('templates/success.php');
}
else {
	require_once('templates/error.php');
}
$data = ob_get_contents();
ob_end_clean();

require_once("output/$format.php");

