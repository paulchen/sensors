#!/usr/bin/php
<?php

chdir(dirname(__FILE__));
$config = parse_ini_file('config.properties');
if(!$config) {
	# TODO
	die(3);
}

$mysqli = new mysqli($config['db_host'], $config['db_username'], $config['db_password'], $config['db_database']);
if($mysqli->connect_errno) {
	# TODO
	die(3);
}

$stmt = $mysqli->prepare('SELECT sensor, what, UNIX_TIMESTAMP(timestamp) timestamp, value FROM sensor_data WHERE timestamp > ? ORDER BY id ASC');
$start_timestamp = date('Y-m-d H:i', time()-86400);
$stmt->bind_param('s', $start_timestamp);
$stmt->execute();
$stmt->bind_result($sensor, $what, $timestamp, $value);
$first_values = array();
$max_values = array();
$min_values = array();
$current_values = array();
$keys = array();
while($stmt->fetch()) {
	$key = "$sensor-$what";
	if(!isset($keys[$key])) {
		$keys[$key] = array('sensor' => $sensor, 'what' => $what);
	}

	$current_values[$key] = array('timestamp' => $timestamp, 'value' => $value);
	if(!isset($max_values[$key])) {
		$min_values[$key] = array('timestamp' => $timestamp, 'value' => $value);
		$max_values[$key] = array('timestamp' => $timestamp, 'value' => $value);
	}
	else {
		if($value > $max_values[$key]['value']) {
			$max_values[$key] = array('timestamp' => $timestamp, 'value' => $value);
		}
		else if($value < $min_values[$key]['value']) {
			$min_values[$key] = array('timestamp' => $timestamp, 'value' => $value);
		}
	}
	# TODO configurable
	if($timestamp < time()-3600) {
		$first_values[$key] = array('timestamp' => $timestamp, 'value' => $value);
	}
}
$stmt->close();

$tendencies = array();
foreach($keys as $index => $key) {
	$old = $first_values[$index]['value'];
	$new = $current_values[$index]['value'];
	# TODO configurable
	if(abs(1-$old/$new) < .01) {
		$tendencies[$index] = 'stable';
	}
	else if($old > $new) {
		$tendencies[$index] = 'decreasing';
	}
	else {
		$tendencies[$index] = 'increasing';
	};
}

$stmt = $mysqli->prepare('SELECT id, name, format, decimals FROM sensor_values');
$stmt->execute();
$stmt->bind_result($id, $name, $format, $decimals);
$values = array();
while($stmt->fetch()) {
	$values[$id] = array('name' => $name, 'format' => $format, 'decimals' => $decimals);
}
$stmt->close();

$stmt = $mysqli->prepare('SELECT id, sensor, type, description FROM sensors');
$stmt->execute();
$stmt->bind_result($id, $sensor, $type, $description);
$sensors = array();
while($stmt->fetch()) {
	if($description == '') {
		$description = "Sensor $sensor";
	}
	$sensors[$id] = array('sensor' => $sensor, 'type' => $type, 'description' => $description);
}
$stmt->close();

$stmt = $mysqli->prepare('SELECT sensor, value, low_crit, low_warn, high_warn, high_crit FROM sensor_limits');
$stmt->execute();
$stmt->bind_result($sensor, $value, $low_crit, $low_warn, $high_warn, $high_crit);
$limits = array();
while($stmt->fetch()) {
	$limits["$sensor-$value"] = array('low_crit' => $low_crit, 'low_warn' => $low_warn, 'high_warn' => $high_warn, 'high_crit' => $high_crit);
}
$stmt->close();

$states = array();
foreach($keys as $index => $key) {
	if(isset($limits[$index])) {
		# TODO configurable
		if(time()-$current_values[$index]['timestamp'] > 15*60) {
			$states[$index] = 'UNKNOWN (most recent value is too old)';
		}
		else {
			$value = $current_values[$index]['value'];
			if($value < $limits[$index]['low_crit']) {
				$states[$index] = 'CRITICAL (below limit of ' . str_replace('%s', round($limits[$index]['low_crit'], $values[$key['what']]['decimals']), $values[$key]['what']['format']) . ')';
			}
			else if($value < $limits[$index]['low_warn']) {
				$states[$index] = 'WARNING (below limit of ' . str_replace('%s', round($limits[$index]['low_warn'], $values[$key['what']]['decimals']), $values[$key]['what']['format']) . ')';
			}
			else if($value > $limits[$index]['high_crit']) {
				$states[$index] = 'CRITICAL (above limit of ' . str_replace('%s', round($limits[$index]['high_crit'], $values[$key['what']]['decimals']), $values[$key]['what']['format']) . ')';
			}
			else if($value > $limits[$index]['high_warn']) {
				$states[$index] = 'WARNING (above limit of ' . str_replace('%s', round($limits[$index]['high_warn'], $values[$key['what']]['decimals']), $values[$key]['what']['format']) . ')';
			}
			else {
				$states[$index] = 'OK';
			}
		}
	}
	else {
		$states[$index] = 'UNKNOWN (no limits set)';
	}
}

foreach($keys as $index => $key) {
	$current_values[$index]['formatted_value'] = str_replace('%s', round($current_values[$index]['value'], $values[$what]['decimals']), $values[$what]['format']);
	$current_values[$index]['formatted_timestamp'] = date('Y-m-d H:i', $current_values[$index]['timestamp']);

	$min_values[$index]['formatted_value'] = str_replace('%s', round($min_values[$index]['value'], $values[$what]['decimals']), $values[$what]['format']);
	$min_values[$index]['formatted_timestamp'] = date('Y-m-d H:i', $min_values[$index]['timestamp']);

	$max_values[$index]['formatted_value'] = str_replace('%s', round($max_values[$index]['value'], $values[$what]['decimals']), $values[$what]['format']);
	$max_values[$index]['formatted_timestamp'] = date('Y-m-d H:i', $max_values[$index]['timestamp']);
}

if(php_sapi_name() == 'cli') {
	foreach($keys as $index => $key) {
		$sensor = $key['sensor'];
		$what = $key['what'];

		echo $sensors[$sensor]['description'] . " - " . $values[$what]['name'] . ":\n\n";
		echo "Current state: " . $states[$index] . "\n";
		echo "Current value: " . $current_values[$index]['formatted_value'] . " (" . $current_values[$index]['formatted_timestamp'] . ")\n";
		echo "Maximum value (24 hours): " . $min_values[$index]['formatted_value'] . " (" . $min_values[$index]['formatted_timestamp'] . ")\n";
		echo "Minimum value (24 hours): " . $max_values[$index]['formatted_value'] . " (" . $max_values[$index]['formatted_timestamp'] . ")\n";
		echo "Current tendency: " . $tendencies[$index] . "\n";
		echo "\n\n";
	}

	exit;
}
# TODO webpage

