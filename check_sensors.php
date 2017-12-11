#!/usr/bin/php
<?php

function usage() {
	echo "Usage: check_sensors.php --sensors <sensor>,<sensor>,... --outdated <seconds>\n";
	echo "--sensors and --outdated can be abbreviated to -s and -o respectively.\n";
	die(3);
}

if($argc != 3 && $argc != 5) {
	usage();
}
if($argv[1] != '--sensors' && $argv[1] != '-s') {
	usage();
}

$sensors = explode(',', $argv[2]);
foreach($sensors as $sensor) {
	if(!preg_match('/^[0-9]+/', $sensor)) {
		echo "Invalid sensor ID: $sensor\n";
		die(3);
	}
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

$states = array(0);
$messages = array();
foreach($sensors as $sensor_id) {
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

	$query = 'SELECT UNIX_TIMESTAMP(timestamp) timestamp, what, value FROM sensor_cache WHERE sensor = ? ORDER BY timestamp DESC LIMIT 0, ?';
	$db_data = db_query($query, array($sensor_id, count($value_ids)));
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
	foreach($timestamps as $timestamp) {
		if(time()-$timestamp > $outdated && !$timestamp_warning) {
			$timestamp_warning = true;
			$messages[] = "$sensor_description - no recent data";
			$states[] = 3;
		}
	}

	if(!$timestamp_warning) {
		ksort($data);
		foreach($data as $what => $item) {
			if(!isset($limits[$what])) {
				echo "Missing limits for sensor with ID $sensor_id\n";
				die(3);
			}

			$name = $value_ids[$what]['name'];
			$value = str_replace('%s', round($item, $value_ids[$what]['decimals']), $value_ids[$what]['format']);
			if($item < $limits[$what]['low_crit']) {
				$messages[] = "$sensor_description/$name - CRITICAL ($value)";
				$states[] = 2;
			}
			else if($item < $limits[$what]['low_warn']) {
				$messages[] = "$sensor_description/$name - WARNING ($value)";
				$states[] = 1;
			}
			else if($item > $limits[$what]['high_crit']) {
				$messages[] = "$sensor_description/$name - CRITICAL ($value)";
				$states[] = 2;
			}
			else if($item > $limits[$what]['high_warn']) {
				$messages[] = "$sensor_description/$name - WARNING ($value)";
				$states[] = 1;
			}
		}
	}
}

if(count($messages) == 0) {
	$message = 'all sensors ok';
}
else {
	$message = implode('; ', $messages);
}
echo "$message\n";
die(max($states));

