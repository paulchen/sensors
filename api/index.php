<?php

if(!isset($_GET['action'])) {
	// TODO
	die();
}
$action = $_GET['action'];

$http_auth = true;

chdir(dirname(__FILE__));
require_once('common.php');
chdir(dirname(__FILE__));

$stmt = $mysqli->prepare('SELECT sensor, what, UNIX_TIMESTAMP(timestamp) timestamp, value FROM sensor_data WHERE timestamp > ? ORDER BY id ASC');
$start_timestamp = date('Y-m-d H:i', time()-86400);
$stmt->bind_param('s', $start_timestamp);
$stmt->execute();
$stmt->bind_result($sensor, $what, $timestamp, $value);
$first_values = array();
$max_values = array();
$min_values = array();
$current_values = array();
$keys = array();
while($stmt->fetch()) {
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
$stmt->close();

switch($action) {
	case 'status':
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

$tidy = new tidy();
$tidy->parseString($data, array('indent' => true, 'input-xml' => true, 'wrap' => 1000), 'utf8');
$tidy->cleanRepair();

header('Content-Type: application/xml');
echo $tidy;

