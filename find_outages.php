#!/usr/bin/php
<?php

chdir(dirname(__FILE__));
$config = parse_ini_file('config.properties');
if(!$config) {
	echo 'Could not read configuration file';
	die(3);
}

$mysqli = new mysqli($config['db_host'], $config['db_username'], $config['db_password'], $config['db_database']);
if($mysqli->connect_errno) {
	echo 'Could not connect to database';
	die(3);
}

$stmt = $mysqli->prepare('SELECT id, name FROM sensor_values');
$stmt->execute();
$stmt->bind_result($id, $name);
$values = array();
while($stmt->fetch()) {
	$values[$id] = $name;
}
$stmt->close();

$stmt = $mysqli->prepare('SELECT id, sensor, description FROM sensors');
$stmt->execute();
$stmt->bind_result($id, $sensor, $description);
$sensors = array();
while($stmt->fetch()) {
	if($description == '') {
		$description = "Sensor $sensor";
	}
	$sensors[$id] = $description;
}
$stmt->close();

$stmt = $mysqli->prepare('SELECT sensor, what FROM sensor_data GROUP BY sensor, what');
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($sensor, $what);
while($stmt->fetch()) {
	echo $sensors[$sensor] . ', ' . $values[$what] . "\n";

	$stmt2 = $mysqli->prepare('SELECT UNIX_TIMESTAMP(timestamp) timestamp FROM sensor_data WHERE sensor = ? AND what = ? ORDER BY id ASC');
	$stmt2->bind_param('ii', $sensor, $what);
	$stmt2->execute();
	$stmt2->bind_result($timestamp);
	$stmt2->fetch();
	$previous_timestamp = $timestamp;
	while($stmt2->fetch()) {
		if($timestamp-$previous_timestamp > $config['outage_period']) {
			echo 'Outage from ';
			echo date('Y-m-d H:i', $previous_timestamp);
			echo ' to ';
			echo date('Y-m-d H:i', $timestamp);
			echo "\n";
		}

		$previous_timestamp = $timestamp;
	}
	$stmt2->close();

	echo "\n";
}
$stmt->close();

