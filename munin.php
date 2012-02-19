#!/usr/bin/php
<?php

$config_file = dirname(__FILE__) . '/config.properties';
if(isset($_ENV['config_dir'])) {
	$config_file = $_ENV['config_dir'] . '/config.properties';
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

$parts = explode('_', $argv[0]);
if(count($parts) != 3) {
	# TODO
	die(3);
}
$value = $parts[1];
$sensor_list = $parts[2];

$stmt = $mysqli->prepare('SELECT id, name, unit, min, max FROM sensor_values WHERE short = ?');
$stmt->bind_param('s', $value);
$stmt->execute();
$stmt->bind_result($value_id, $value_name, $value_unit, $value_min, $value_max);
if(!$stmt->fetch()) {
	# TODO
	die(3);
}
$stmt->close();

$sensors = explode(',', $sensor_list);
$sensor_info = array();

$stmt = $mysqli->prepare('SELECT sensor, type, description FROM sensors WHERE id = ? ORDER BY id DESC LIMIT 0, 1');
foreach($sensors as $sensor_id) {
	$stmt->bind_param('i', $sensor_id);
	$stmt->execute();
	$stmt->bind_result($sensor, $type, $description);
	if(!$stmt->fetch()) {
		# TODO
		die(3);
	}
	$sensor_info[$sensor] = array('id' => $sensor_id, 'sensor' => $sensor, 'type' => $type, 'description' => $description);
}
$stmt->close();

if(isset($argv[1]) && $argv[1] == 'config') {
	$title = 'Sensor';
	if(count($sensor_info) > 1) {
		$title .= 's';
	}
	$title .= ' ' . implode(', ', $sensors);
	$title .= ': ' . $value_name;

	# TODO limits
	echo "graph_title $title\n";
	echo "graph_vtitle $value_unit\n";
	if($value_min != '' && $value_max != '') {
		echo "graph_args -l $value_min --upper-limit $value_max\n";
	}
	else if($value_min != '') {
		echo "graph_args -l $value_min\n";
	}
	else if($value_max != '') {
		echo "graph_args --upper-limit $value_max\n";
	}

	echo "graph_category sensor_data\n";
	foreach($sensor_info as $index => $sensor) {
		echo "sensor" . $sensor['id'] . '.label ' . ($sensor['description'] != '' ? $sensor['description'] : "Sensor $index"). "\n";
	}

	exit;
}

# TODO output actual data

exit;

