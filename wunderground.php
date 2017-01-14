<?php
require_once(dirname(__FILE__) . '/api/common.php');

function mm_to_in($mm) { return round($mm / 25.4, 2); }
function kmh_to_mph($kmh) { return round($kmh / 1.60934, 2); }
function hpa_to_inhg($hpa) { return round($hpa / 33.8639, 2); }
function c_to_f($c) { return round($c * 1.8 + 32, 2); }

function is_sensor($value, $sensor, $measurement) {
	global $config, $sensor_values;

	if($measurement != $sensor_values[$value]) {
		return false;
	}
	if($sensor == $config["wunderground_sensor_$value"]) {
		return true;
	}
	return false;
}

if(!isset($config['wunderground_station']) ||
		!isset($config['wunderground_password']) ||
		!isset($config['wunderground_status_directory']) ||
		!isset($config['wunderground_timeout'])) {
	die();
}

echo date('Y-m-d H:i:s') . "\n";

$sensor_data = get_sensors_state(array(9, 29));

$wunderground_data = array();
$rain = get_rain_raw();
if($rain !== false) {
	if(is_numeric($rain)) {
		$wunderground_data['dailyrainin'] = mm_to_in($rain);
	}
}

$data = db_query('SELECT id, short FROM sensor_values');
$sensor_values = array();
foreach($data as $row) {
	$sensor_values[$row['short']] = $row['id'];
}

foreach($sensor_data as $sensor_id => $sensor) {
	foreach($sensor['values'] as $key => $valuex) {
		$data = $valuex['measurements'][0];
		$timestamp = $data['timestamp'];
		if(time() - $timestamp > $config['wunderground_timeout']) {
			continue;
		}
		$value = $data['value'];
		if(is_sensor('temp', $sensor_id, $key)) {
			$wunderground_data['tempf'] = c_to_f($value);
		}
		else if(is_sensor('humid', $sensor_id, $key)) {
			$wunderground_data['humidity'] = $value;
		}
		else if(is_sensor('wind', $sensor_id, $key)) {
			$wunderground_data['windspeedmph'] = kmh_to_mph($value);
		}
		else if(is_sensor('rain', $sensor_id, $key)) {
			$wunderground_data['rainin'] = mm_to_in($value);
		}
		else if(is_sensor('pressure', $sensor_id, $key)) {
			$wunderground_data['baromin'] = hpa_to_inhg($value);
		}
		else if(is_sensor('dewp', $sensor_id, $key)) {
			$wunderground_data['dewptf'] = c_to_f($value);
		}
	}
}

if(count($wunderground_data) == 0) {
	die('No data for Weather Underground');
}
$updated_values = array_keys($wunderground_data);

$wunderground_url = 'https://weatherstation.wunderground.com/weatherstation/updateweatherstation.php?action=updateraw';

$wunderground_data['ID'] = $config['wunderground_station'];
$wunderground_data['PASSWORD'] = $config['wunderground_password'];
$wunderground_data['dateutc'] = gmdate('Y-m-d H:i:s', $timestamp);
foreach($wunderground_data as $key => $value) {
	$wunderground_url .= '&' . urlencode($key) . '=' . urlencode($value);
}

echo "Calling URL: $wunderground_url\n";
$reply = file_get_contents($wunderground_url);
echo "Reply: $reply\n";

if(trim($reply) != 'success') {
	# TODO react appropriately
}
else {
	foreach($updated_values as $value) {
		touch($config['wunderground_status_directory'] . "/$value");
	}
}

echo date('Y-m-d H:i:s') . "\n";


