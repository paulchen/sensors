#!/usr/bin/php
<?php

chdir(dirname(__FILE__));
$config = parse_ini_file('config.properties');
if(!$config) {
	# TODO
	die(3);
}

$mysqli = new mysqli($config['db_host'], $config['db_username'], $config['db_password'], $config['db_database']);
if($mysqli->connect_errno) {
	# TODO
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
	# TODO configurable
	if($timestamp < time()-3600) {
		$first_values[$key] = array('timestamp' => $timestamp, 'value' => $value);
	}
}
$stmt->close();

foreach($keys as $index => $key) {
	$sensor = $key['sensor'];
	$what = $key['what'];

	# TODO sensor description
	echo "\n\nSensor $sensor/$what:\n\n";
	# TODO format, round
	echo "Current value: " . $current_values[$index]['value'] . " (" . date('Y-m-d H:i', $current_values[$index]['timestamp']) . ")\n";
	echo "Maximum value (24 hours): " . $max_values[$index]['value'] . " (" . date('Y-m-d H:i', $max_values[$index]['timestamp']) . ")\n";
	echo "Minimum value (24 hours): " . $min_values[$index]['value'] . " (" . date('Y-m-d H:i', $min_values[$index]['timestamp']) . ")\n";
	echo "Current tendency: ";

	$old = $first_values[$index]['value'];
	$new = $current_values[$index]['value'];
	# TODO configurable
	if(abs(1-$old/$new) < .01) {
		echo "stable";
	}
	else if($old > $new) {
		echo "decreasing";
	}
	else {
		echo "increasing";
	};
	echo "\n";
}

# TODO webpage

