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

$query = 'SELECT sensor, what, UNIX_TIMESTAMP(timestamp) timestamp, value FROM sensor_cache WHERE timestamp > ? ORDER BY id ASC';
$start_timestamp = date('Y-m-d H:i', time()-86400);
$data = db_query($query, array($start_timestamp));
$first_values = array();
$max_values = array();
$min_values = array();
$current_values = array();
$keys = array();
foreach($data as $row) {
	$sensor = $row['sensor'];
	$what = $row['what'];
	$timestamp = $row['timestamp'];
	$value = $row['value'];

	$key = "$sensor-$what";
	if(!isset($keys[$key])) {
		$keys[$key] = array('sensor' => $sensor, 'what' => $what);
	}

	$current_values[$key] = array('timestamp' => $timestamp, 'value' => $value);
	if(!isset($max_values[$key])) {
		$min_values[$key] = array('timestamp' => $timestamp, 'value' => $value);
		$max_values[$key] = array('timestamp' => $timestamp, 'value' => $value);
	}
	else {
		if($value > $max_values[$key]['value']) {
			$max_values[$key] = array('timestamp' => $timestamp, 'value' => $value);
		}
		else if($value < $min_values[$key]['value']) {
			$min_values[$key] = array('timestamp' => $timestamp, 'value' => $value);
		}
	}
	
	if($timestamp < time()-$config['tendency_period'] || !isset($first_values[$key])) {
		$first_values[$key] = array('timestamp' => $timestamp, 'value' => $value);
	}
}

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

