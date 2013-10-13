#!/usr/bin/php
<?php

chdir(dirname(__FILE__));
require_once('common.php');

$query = 'SELECT id, name FROM sensor_values';
$data = db_query($query);
$values = array();
foreach($data as $row) {
	$values[$row['id']] = $row['name'];
}

$query = 'SELECT id, sensor, description FROM sensors';
$data = db_query($query);
foreach($data as $row) {
	$sensor = $row['sensor'];
	$description = $row['description'];

	if($description == '') {
		$description = "Sensor $sensor";
	}
	$sensors[$row['id']] = $description;
}

$query = 'SELECT sensor, what FROM sensor_data GROUP BY sensor, what';
$data = db_query($query);
foreach($data as $row) {
	$sensor = $row['sensor'];
	$what = $row['what'];

	echo $sensors[$sensor] . ', ' . $values[$what] . "\n";

	$query2 = 'SELECT UNIX_TIMESTAMP(timestamp) timestamp FROM sensor_data WHERE sensor = ? AND what = ? ORDER BY id ASC';
	$data2 = db_query($query2, array($sensor, $what));
	$previous_timestamp = intval($data2[0]['timestamp']);
	for($a=1; $a<count($data2); $a++) {
		$timestamp = intval($data2[$a]['timestamp']);

		if($timestamp-$previous_timestamp > $config['outage_period']) {
			echo 'Outage from ';
			echo date('Y-m-d H:i', $previous_timestamp);
			echo ' to ';
			echo date('Y-m-d H:i', $timestamp);
			echo "\n";
		}

		$previous_timestamp = $timestamp;
	}

	echo "\n";
}

