<?php
require_once(dirname(__FILE__) . '/api/common.php');

if(!isset($config['wunderground_station']) || !isset($config['wunderground_password']) || !isset($config['wunderground_status_directory'])) {
	die();
}

echo date('Y-m-d H:i:s') . "\n";

$sensor_data = get_sensors_state(array(9, 29));

$wunderground_data = array();
$rain = get_rain_raw();
if($rain !== false) {
	if(is_numeric($rain)) {
		$wunderground_data['dailyrainin'] = round($rain / 2.54, 2);
	}
}

foreach($sensor_data as $sensor_id => $sensor) {
	foreach($sensor['values'] as $key => $valuex) {
		$data = $valuex['measurements'][0];
		$timestamp = $data['timestamp'];
		$value = $data['value'];
		if($sensor_id == 9 && $key == 1) {
			$tempc = $value;
			$wunderground_data['tempf'] = round($value*1.8 + 32, 2);
		}
		else if($sensor_id == 9 && $key == 2) {
			$humidity = $value;
			$wunderground_data['humidity'] = $value;
		}
		else if($sensor_id == 9 && $key == 3) {
			$wunderground_data['windspeedmph'] = round($value / 1.60934, 2);
		}
		else if($sensor_id == 9 && $key == 4) {
			$wunderground_data['rainin'] = round($value / 2.54, 2);
		}
		else if($sensor_id == 29 && $key == 5) {
			$wunderground_data['baromin'] = round($value / 33.8639, 2);
		}
	}
}

if(isset($tempc) && isset($humidity)) {
	$dew_point = $tempc - ((100 - $humidity)/5);
	$wunderground_data['dewptf'] = round($dew_point*1.8 + 32, 2);
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


