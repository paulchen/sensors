#!/usr/bin/php
<?php

chdir(dirname(__FILE__));
$config_file = 'config.properties';
if(isset($_ENV['config_dir'])) {
	$config_file = $_ENV['config_dir'] . '/config.properties';
}

require_once('common.php');

$parts = explode('_', $argv[0]);
if(count($parts) != 3) {
	echo "Invalid name of symlink.\n";
	die(3);
}
$value = $parts[1];
$sensor_list = $parts[2];

$query = 'SELECT id, name, unit, min, max, decimals FROM sensor_values WHERE short = ?';
$data = db_query($query, array($value));
if(count($data) != 1) {
	echo "Invalid sensor value specified.\n";
	die(3);
}

$value_id = $data[0]['id'];
$value_name = $data[0]['name'];
$value_unit = $data[0]['unit'];
$value_min = $data[0]['min'];
$value_max = $data[0]['max'];
$value_decimals = $data[0]['decimals'];

$sensors = explode('.', $sensor_list);
$sensor_info = array();

$lang = 'de'; // TODO configurable
$query = 'SELECT id FROM languages WHERE language = ?';
$data = db_query($query, array($lang));
$language_id = $data[0]['id'];

$query = 'SELECT s.sensor sensor, s.type type, COALESCE(sdn.name, s.description) description, s.color color
	FROM sensors s
		LEFT JOIN sensor_display_names sdn ON (s.id = sdn.sensor AND sdn.language = ?)
	WHERE id = ?
	ORDER BY id DESC
	LIMIT 0, 1';
foreach($sensors as $sensor_id) {
	$data = db_query($query, array($language_id, $sensor_id));
	if(count($data) != 1) {
		echo "Unknown sensor ID: $sensor_id\n";
		die(3);
	}

	$row = $data[0];
	$row['id'] = $sensor_id;
	$sensor_info[$row['sensor']] = $row;
}

if(isset($argv[1]) && $argv[1] == 'config') {
	$title = 'Sensor';
	if(count($sensor_info) > 1) {
		$title .= 's';
	}
	$title .= ' ' . implode(', ', $sensors);
	$title .= ': ' . $value_name;

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
	echo "graph_scale no\n";
	echo "graph_category sensor_data\n";
	$query = 'SELECT low_crit, low_warn, high_warn, high_crit FROM sensor_limits WHERE sensor = ? AND value = ?';
	foreach($sensor_info as $index => $sensor) {
		echo 'sensor' . $sensor['id'] . '.label ' . ($sensor['description'] != '' ? $sensor['description'] : "Sensor $index"). "\n";
		echo 'sensor' . $sensor['id'] . ".draw LINE1\n";
		$data = db_query($query, array($sensor['id'], $value_id));
		if(count($data) > 0) {
			$low_crit = $data[0]['low_crit'];
			$low_warn = $data[0]['low_warn'];
			$high_warn = $data[0]['high_warn'];
			$high_crit = $data[0]['high_crit'];

			echo 'sensor' . $sensor['id'] . ".warning $low_warn:$high_warn\n";
			echo 'sensor' . $sensor['id'] . ".critical $low_crit:$high_crit\n";
			if($sensor['color'] != '') {
				$color = $sensor['color'];

				echo 'sensor' . $sensor['id'] . ".colour $color\n";
			}
		}
	}

	exit;
}

$query = 'SELECT UNIX_TIMESTAMP(timestamp) timestamp, value FROM sensor_data WHERE sensor = ? AND what = ? AND DATE_SUB(NOW(), INTERVAL 1 DAY) < timestamp ORDER BY id DESC LIMIT 0, 1';
foreach($sensor_info as $index => $sensor) {
	$data = db_query($query, array($sensor['id'], $value_id));
	if(count($data) > 0) {
		$timestamp = intval($data[0]['timestamp']);
		$value = floatval($data[0]['value']);

		if(time()-$timestamp < $config['value_outdated_period']) {
			$value = round($value, $value_decimals);
			echo 'sensor' . $sensor['id'] . ".value $value\n";
		}
	}
}

