#!/usr/bin/php
<?php

function usage() {
	echo "Usage: check_sensors.php --sensors <sensor>,<sensor>,... --config-file <directory>\n";
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

$config_file = dirname(__FILE__) . '/config.properties';

if($argc == 5 && $argv[3] != '--config-file' && $argv[3] != '-c') {
	usage();
}
else if($argc == 5) {
	$config_file = $argv[4] . '/config.properties';
}

$config = parse_ini_file($config_file);
if(!$config) {
	echo "Could not read configuration file.\n";
	die(3);
}

$mysqli = new mysqli($config['db_host'], $config['db_username'], $config['db_password'], $config['db_database']);
if($mysqli->connect_errno) {
	echo "Could not connect to database.\n";
	die(3);
}

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

	$stmt = $mysqli->prepare('SELECT value, low_warn, low_crit, high_warn, high_crit FROM sensor_limits WHERE sensor = ?');
	$stmt->bind_param('i', $sensor_id);
	$stmt->execute();
	$limits = array();
	$stmt->bind_result($value, $low_warn, $low_crit, $high_warn, $high_crit);
	while($stmt->fetch()) {
		$limits[$value] = array('low_warn' => $low_warn, 'low_crit' => $low_crit, 'high_warn' => $high_warn, 'high_crit' => $high_crit);
	}
	$stmt->close();

	$stmt = $mysqli->prepare('SELECT UNIX_TIMESTAMP(timestamp) timestamp, what, value FROM sensor_data WHERE sensor = ? ORDER BY id DESC LIMIT 0, ' . count($value_ids));
	$stmt->bind_param('i', $sensor_id);
	$stmt->execute();
	$stmt->bind_result($timestamp, $what, $value);
	$data = array();
	$timestamps = array();
	while($stmt->fetch()) {
		if(!isset($data[$what])) {
			$data[$what] = $value;
			$timestamps[$what] = $timestamp;
		}
	}
	$stmt->close();
	if(count($data) == 0) {
		echo "No data for '$sensor_description'.\n";
		die(3);
	}
	$timestamp_warning = false;
	foreach($timestamps as $timestamp) {
		if(time()-$timestamp > $config['value_outdated_period'] && !$timestamp_warning) {
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
			if($item <= $limits[$what]['low_crit']) {
				$messages[] = "$sensor_description/$name - CRITICAL ($value)";
				$states[] = 2;
			}
			else if($item <= $limits[$what]['low_warn']) {
				$messages[] = "$sensor_description/$name - WARNING ($value)";
				$states[] = 1;
			}
			else if($item >= $limits[$what]['high_crit']) {
				$messages[] = "$sensor_description/$name - CRITICAL ($value)";
				$states[] = 2;
			}
			else if($item >= $limits[$what]['high_warn']) {
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

