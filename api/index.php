<?php

if(!isset($_GET['action'])) {
	// TODO
	die();
}
$action = $_GET['action'];

chdir(dirname(__FILE__));
$config = parse_ini_file('../config.properties');
if(!$config) {
	echo "Could not read configuration file.\n";
	die(3);
}

$mysqli = new mysqli($config['db_host'], $config['db_username'], $config['db_password'], $config['db_database']);
if($mysqli->connect_errno) {
	echo "Could not connect to database.\n";
	die(3);
}

require_once('common.php');

if($config['api_authentication'] == 0) {
	/* do nothing */
}
else if($config['api_authentication'] == 1) {
	/* HTTP basic authentication */
	if(!isset($_SERVER['PHP_AUTH_USER'])) {
		header('WWW-Authenticate: Basic realm="Sensors API"');
		header('HTTP/1.0 401 Unauthorized');
		// TODO XML reply
		die();
	}

	$username = $_SERVER['PHP_AUTH_USER'];
	$password = $_SERVER['PHP_AUTH_PW'];
	
	$stmt = $mysqli->prepare('SELECT hash FROM api_accounts WHERE username = ?');
	$stmt->bind_param('s', $username);
	$stmt->execute();
	$stmt->bind_result($db_hash);
	$found = false;
	while($stmt->fetch()) {
		$found = true;
	}
	$stmt->close();

	$hash = crypt($password, $db_hash);
	if($hash != $db_hash) {
		// TODO duplicate code
		header('WWW-Authenticate: Basic realm="Sensors API"');
		header('HTTP/1.0 401 Unauthorized');
		// TODO XML reply
		die();
	}
}
else {
	echo "Wrong value for configuration setting 'api_authentication'.\n";
	die(3);
}

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

