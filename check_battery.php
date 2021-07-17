#!/usr/bin/php
<?php

function usage() {
	echo "Usage: check_battery.php --sensor <sensor> --config-file <directory>\n";
	echo "--sensor and --config-file can be abbreviated to -s and -c, respectively.\n";
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

if($argc == 5 && $argv[3] != '--config-file' && $argv[3] != '-c') {
	usage();
}
else if($argc == 5) {
	$config_file = $argv[4] . '/config.properties';
}

chdir(dirname(__FILE__));
require_once('common.php');

$query = 'SELECT id, name, format, decimals FROM sensor_values';
$data = db_query($query, array(), 86400);
$value_ids = array();
foreach($data as $row) {
	$value_ids[$row['id']] = $row;
}

$query = 'SELECT sensor, description, display_name FROM sensors WHERE id = ? ORDER BY id DESC LIMIT 0, 1';
$data = db_query($query, array($sensor_id), 86400);
if(count($data) == 0) {
	echo "No data for sensor with ID $sensor_id\n";
	die(3);
}

$sensor = $data[0]['sensor'];
if($data[0]['display_name']) {
	$sensor_description = $data[0]['display_name'];
}
else if($data[0]['description']) {
	$sensor_description = $data[0]['description'];
}
else {
	$sensor_description = "Sensor $sensor";
}

$query = 'SELECT UNIX_TIMESTAMP(timestamp) timestamp FROM battery_changes WHERE sensor = ? ORDER BY id DESC LIMIT 0, 1';
$data = db_query($query, array($sensor_id), 3600);
if(count($data) == 1) {
	$timestamp = $data[0]['timestamp'];
}
else {
	$timestamp = 0; 
}

if($timestamp == 0) {
	$message = "$sensor_description - no battery change recorded";
	$state = 3;
}
else {
	$battery_days = floor((time() - $timestamp)/86400);
	if($battery_days > $config['battery_critical']) {
		$critical = $config['battery_critical'];
		$message = "$sensor_description - last battery change was more than $critical days ago";
		$state = 2;
	}
	else if($battery_days > $config['battery_warning']) {
		$warning = $config['battery_warning'];
		$message = "$sensor_description - last battery change was more than $warning days ago";
		$state = 1;
	}
	else {
		$message = "$sensor_description - ok";
		$state = 0;
	}
}

echo "$message\n";
die($state);

