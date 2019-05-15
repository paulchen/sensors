#!/usr/bin/php
<?php

function usage() {
	echo "Usage: check_sensor.php --sensor <sensor> --outdated <seconds>\n";
	echo "--sensor and --outdated can be abbreviated to -s and -o respectively.\n";
	die(3);
}

if($argc != 3 && $argc != 5) {
	usage();
}
if($argv[1] != '--sensor' && $argv[1] != '-s') {
	usage();
}

$sensor_id = $argv[2];
if(!preg_match('/^[0-9]+/', $sensor_id)) {
	echo "Invalid sensor ID: $sensor_id\n";
	die(3);
}

$config_file = 'config.properties';

if($argc > 3 && $argv[3] != '--outdated' && $argv[3] != '-o') {
	usage();
}
else if($argc > 3) {
	$outdated = $argv[4];
	if(!preg_match('/^[0-9]+$/', $outdated)) {
		usage();
	}
}
else {
	$outdated = -1;
}

chdir(dirname(__FILE__));
require_once('common.php');

if($outdated == -1) {
	$outdated = $config['value_outdated_period'];
}

$query = 'SELECT id, name, format, decimals FROM sensor_values';
$data = db_query($query);
$value_ids = array();
foreach($data as $row) {
	$value_ids[$row['id']] = $row;
}

$state = 0;
$message = '';

$query = 'SELECT sensor, description FROM sensors WHERE id = ? ORDER BY id DESC LIMIT 0, 1';
$data = db_query($query, array($sensor_id));
if(count($data) == 0) {
	echo "No data for sensor with ID $sensor\n";
	die(3);
}

$sensor = $data[0]['sensor'];
$sensor_description = $data[0]['description'];

if($sensor_description == '') {
	$sensor_description = "Sensor $sensor";
}

$query = 'SELECT value, low_warn, low_crit, high_warn, high_crit FROM sensor_limits WHERE sensor = ?';
$data = db_query($query, array($sensor_id));
$limits = array();
foreach($data as $row) {
	$limits[$row['value']] = $row;
}

$count = count($value_ids);
$query = "SELECT UNIX_TIMESTAMP(timestamp) timestamp, what, value FROM sensor_cache WHERE sensor = ? ORDER BY timestamp DESC LIMIT 0, $count";
$db_data = db_query($query, array($sensor_id));
$data = array();
$timestamps = array();
foreach($db_data as $row) {
	$timestamp = $row['timestamp'];
	$what = $row['what'];
	$value = $row['value'];

	if(!isset($data[$what])) {
		$data[$what] = $value;
		$timestamps[$what] = $timestamp;
	}
}
if(count($data) == 0) {
	echo "No data for '$sensor_description'.\n";
	die(3);
}
$timestamp_warning = false;
$max_state = 0;
foreach($timestamps as $timestamp) {
	if(time()-$timestamp > $outdated && !$timestamp_warning) {
		$timestamp_warning = true;
		$message = "$sensor_description - no recent data";
		$max_state = 3;
	}
}

if(!$timestamp_warning) {
	ksort($data);
	$message = "$sensor_description - ";
	$parts = array();
	foreach($data as $what => $item) {
		if(!isset($limits[$what])) {
			echo "Missing limits for sensor with ID $sensor_id\n";
			die(3);
		}

		$name = $value_ids[$what]['name'];
		$value = str_replace('%s', round($item, $value_ids[$what]['decimals']), $value_ids[$what]['format']);
		$state = 0;
		if($item < $limits[$what]['low_crit']) {
			$state = 2;
		}
		else if($item < $limits[$what]['low_warn']) {
			$state = 1;
		}
		else if($item > $limits[$what]['high_crit']) {
			$state = 2;
		}
		else if($item > $limits[$what]['high_warn']) {
			$state = 1;
		}
		switch ($state) {
			case 0: $state_string = 'OK'; break;
			case 1: $state_string = 'WARNING'; break;
			case 2: $state_string = 'CRITICAL'; break;
		}
		$parts[] = "$name: $state_string ($value)";
		if ($state > $max_state) {
			$max_state = $state;
		}
	}
	$message .= implode('; ', $parts);
}

echo "$message\n";
die($max_state);

