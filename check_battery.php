#!/usr/bin/php
<?php

function usage() {
	echo "Usage: check_battery.php --sensors <sensor>,<sensor>,... --config-file <directory>\n";
	echo "--sensors and --config-file can be abbreviated to -s and -c, respectively.\n";
	die(3);
}

if($argc != 3 && $argc != 5) {
	usage();
}
if($argv[1] != '--sensors' && $argv[1] != '-s') {
	usage();
}

$sensors = split(',', $argv[2]);
foreach($sensors as $sensor) {
	if(!preg_match('/^[0-9]+/', $sensor)) {
		echo "Invalid sensor ID: $sensor\n";
		die(3);
	}
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

$stmt = $mysqli->prepare('SELECT id, name, format, decimals FROM sensor_values');
$stmt->execute();
$stmt->bind_result($id, $name, $format, $decimals);
$value_ids = array();
while($stmt->fetch()) {
	$value_ids[$id] = array('name' => $name, 'format' => $format, 'decimals' => $decimals);
}
$stmt->close();

$states = array(0);
$messages = array();
foreach($sensors as $sensor_id) {
	$stmt = $mysqli->prepare('SELECT sensor, description FROM sensors WHERE id = ? ORDER BY id DESC LIMIT 0, 1');
	$stmt->bind_param('i', $sensor_id);
	$stmt->execute();
	$stmt->bind_result($sensor, $sensor_description);
	if(!$stmt->fetch()) {
		echo "No data for sensor with ID $sensor\n";
		die(3);
	}
	if($sensor_description == '') {
		$sensor_description = "Sensor $sensor";
	}
	$stmt->close();

	$stmt = $mysqli->prepare('SELECT UNIX_TIMESTAMP(timestamp) timestamp FROM battery_changes WHERE sensor = ? ORDER BY id DESC LIMIT 0, 1');
	$stmt->bind_param('i', $sensor_id);
	$stmt->execute();
	$timestamp = 0;
	$stmt->bind_result($value);
	while($stmt->fetch()) {
		$timestamp = $value;
	}
	$stmt->close();

	if($timestamp == 0) {
		$messages[] = "$sensor_description - no battery change recorded";
		$states[] = 3;
	}
	$battery_days = floor((time() - $timestamp)/86400);
	if($battery_days > $config['battery_critical']) {
		$critical = $config['battery_critical'];
		$messages[] = "$sensor_description - last battery change was more than $critical days ago";
		$states[] = 2;
	}
	else if($battery_days > $config['battery_warning']) {
		$warning = $config['battery_warning'];
		$messages[] = "$sensor_description - last battery change was more than $warning days ago";
		$states[] = 1;
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

