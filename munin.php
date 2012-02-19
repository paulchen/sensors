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

$pos = strpos($argv[0], '_');
if($pos === false) {
	# TODO
	die(3);
}

$sensor_list = substr($argv[0], $pos+1);
$sensors = explode(',', $sensor_list);
$sensor_info = array();

$stmt = $mysqli->prepare('SELECT id, type, description FROM sensors WHERE sensor = ? ORDER BY id DESC LIMIT 0, 1');
foreach($sensors as $sensor) {
	$stmt->bind_param('i', $sensor);
	$stmt->execute();
	$stmt->bind_result($id, $type, $description);
	if(!$stmt->fetch()) {
		# TODO
		die(3);
	}
	$sensor_info[$sensor] = array('id' => $id, 'type' => $type, 'description' => $description);
}
$stmt->close();

# TODO check: don't mix temperatures, humidities, ...

if(isset($argv[1]) && $argv[1] == 'config') {
	$title = 'Sensor';
	if(count($sensor_info) > 1) {
		$title .= 's';
	}
	$title .=  ' ' . implode(', ', $sensors);

	# TODO what if not temperature?
	# TODO limits
	echo "graph_title $title\n";
	echo "graph_vtitle Celsius\n";
	echo "graph_category sensor_data\n";
	foreach($sensor_info as $index => $sensor) {
		echo "sensor" . $sensor['id'] . '.label ' . ($sensor['description'] != '' ? $sensor['description'] : "Sensor $index"). "\n";
	}

	exit;
}

# TODO output actual data

exit;

