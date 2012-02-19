#!/usr/bin/php
<?php

function usage() {
	# TODO
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
	# TODO
	die(3);
}

$mysqli = new mysqli($config['db_host'], $config['db_username'], $config['db_password'], $config['db_database']);
if($mysqli->connect_errno) {
	# TODO
	die(3);
}

$stmt = $mysqli->prepare('SELECT id, name FROM sensor_values');
$stmt->execute();
$stmt->bind_result($id, $name);
$value_ids = array();
while($stmt->fetch()) {
	$value_ids[$id] = $name;
}
$stmt->close();

$states = array(0);
$messages = array();
foreach($sensors as $sensor) {
	$stmt = $mysqli->prepare('SELECT id FROM sensors WHERE sensor = ? ORDER BY id DESC LIMIT 0, 1');
	$stmt->bind_param('i', $sensor);
	$stmt->execute();
	$stmt->bind_result($sensor_id);
	if(!$stmt->fetch()) {
		echo "No data for sensor with ID $sensor\n";
		die(3);
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
		# TODO use sensor description
		echo "No data for sensor with ID $sensor\n";
		die(3);
	}
	$timestamp_warning = false;
	foreach($timestamps as $timestamp) {
		if(time()-$timestamp > 15*60 && !$timestamp_warning) {
			$timestamp_warning = true;
			# TODO use sensor description
			$messages[] = "sensor $sensor - no recent data";
			$states[] = 3;
		}
	}

	if(!$timestamp_warning) {
		ksort($data);
		foreach($data as $what => $item) {
			if(!isset($limits[$what])) {
				echo "Missing limits for sensor with ID $sensor\n";
				die(3);
			}

			if($item <= $limits[$what]['low_crit']) {
				# TODO use sensor description
				# TODO replace what by something meaningful
				# TODO format value accordingly
				$messages[] = "sensor $sensor $what - CRITICAL ($item)";
				$states[] = 2;
			}
			else if($item <= $limits[$what]['low_warn']) {
				$messages[] = "sensor $sensor $what - WARNING ($item)";
				$states[] = 1;
			}
			else if($item >= $limits[$what]['high_crit']) {
				$messages[] = "sensor $sensor $what - CRITICAL ($item)";
				$states[] = 2;
			}
			else if($item >= $limits[$what]['high_warn']) {
				$messages[] = "sensor $sensor $what - WARNING ($item)";
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

