<?php
if(basename($_SERVER['SCRIPT_FILENAME']) != 'index.php') {
	// TODO
	die('1');
}

if(!(isset($_REQUEST['sensor']) xor isset($_REQUEST['sensors']))) {
	// TODO
	die('2');
}
if(!(isset($_REQUEST['what']) xor isset($_REQUEST['whats']))) {
	// TODO
	die('2');
}
if(!(isset($_REQUEST['value']) xor isset($_REQUEST['values']))) {
	// TODO
	die('2');
}

require_once(dirname(__FILE__) . '/rain_common.php');

function dew_point($temp, $humid) {
	// https://www.wetterochs.de/wetter/feuchte.html

	if($humid == 0) {
		return array('dewp' => $temp, 'abshum' => 0);
	}

	if($temp >= 0) {
		$a = 7.5;
		$b = 237.3;
	}
	else {
		$a = 7.6;
		$b = 240.7;
	}

	$sdd = 6.1078 * pow(10, $a * $temp / ($b + $temp));

	$dd = $humid/100*$sdd;

	$v = log10($dd / 6.1078);

	$td = $b * $v / ($a - $v);

	$tk = $temp + 273.15;

	$abshum = 100000 * 18.016 / 8314.3 * $dd / $tk;

	return array('dewp' => round($td, 1), 'abshum' => round($abshum, 1));
}

function apparent_temperature($temp, $humid, $wind) {
	if($temp < 10) {
		if($wind < 5) {
			return $temp;
		}
		return 13.12 + 0.6215 * $temp - 11.37 * pow($wind, 0.16) + 0.3965 * $temp * pow($wind, 0.16);
	}
	if($temp < 27) {
		return $temp;
	}

	$c1 = -42.379;
	$c2 = 2.04901523;
	$c3 = 10.14333127;
	$c4 = -.22475541;
	$c5 = -6.83783 / 1000;
	$c6 = -5.481717 / 100;
	$c7 = 1.22874 / 1000;
	$c8 = 8.5282 / 10000;
	$c9 = -1.99 / 1000000;

	$temp_f = $temp * 1.8 + 32;

	$hi_f = $c1 +
		$c2 * $temp_f +
		$c3 * $humid +
		$c4 * $temp_f * $humid +
		$c5 * $temp_f * $temp_f +
		$c6 * $humid * $humid +
		$c7 * $temp_f * $temp_f * $humid +
		$c8 * $temp_f * $humid * $humid +
		$c9 * $temp_f * $temp_f * $humid * $humid;

	return round(($hi_f - 32) / 1.8, 1);
}

function get_cached_value($sensor, $what) {
	global $memcached, $memcached_prefix;

	$memcached_key = "${memcached_prefix}_value_${sensor}_${what}";
	$value = $memcached->get($memcached_key);
	if($value == null) {
		return null;
	}
	return substr($value, 3);
}

function set_cached_value($sensor, $what, $value) {
	global $memcached, $memcached_prefix;

	$memcached_key = "${memcached_prefix}_value_${sensor}_${what}";
	$memcached->set($memcached_key, "V: $value", 290);
}

function add_inserts($cache_only, ...$values) {
	global $inserts, $cache_inserts;

	foreach($values as $value) {
		if(!$cache_only) {
			$inserts[] = $value;
		}
		$cache_inserts[] = $value;
	}
}

function do_inserts($table, $inserts) {
	if(count($inserts) == 0) {
		return;
	}

	$parameters = array();
	for($a=0; $a<count($inserts)/4; $a++) {
		$parameters[] = '(FROM_UNIXTIME(?), ?, ?, ?)';
	}
	$parameter_string = implode(', ', $parameters);

	db_query("INSERT INTO $table (timestamp, sensor, what, value) VALUES $parameter_string", $inserts);
}

function process_values($sensor_id, $what_short, $value) {
	global $timestamp, $memcached_updates, $sensor_values;

	$cache_only = false;
	$cached_value = get_cached_value($sensor_id, $what_short);
	if(time() - $timestamp < 290 && $cached_value != null && $cached_value == $value) {
		$cache_only = true;
/*
		// $handle = fopen('/tmp/ipwe-wtf', 'a');
		print("time(): " . time() . "\n");
		print("timestamp: $timestamp\n");
		print("sensor_id: $sensor_id\n");
		print("what_short: $what_short\n");
		print("value: $value\n");
		print("cached_value: " . get_cached_value($sensor_id, $what_short) . "\n");
		// fclose($handle);
 */
	}
	else {
		$memcached_updates[] = array('sensor' => $sensor_id, 'what' => $what_short, 'value' => $value);
	}

	add_inserts($cache_only, $timestamp, $sensor_id, $sensor_values[$what_short]['id'], $value);
}


$sensor_ids = isset($_REQUEST['sensor']) ? array($_REQUEST['sensor']) : explode(';', $_REQUEST['sensors']);
$what_shorts = isset($_REQUEST['what']) ? array($_REQUEST['what']) : explode(';', $_REQUEST['whats']);
$values = isset($_REQUEST['value']) ? array($_REQUEST['value']) : explode(';', $_REQUEST['values']);
$timestamp = isset($_REQUEST['timestamp']) ? $_REQUEST['timestamp'] : time();

if(count($sensor_ids) != count($what_shorts) || count($sensor_ids) != count($values)) {
	// TODO
	die('5');
}

if(count($values) > 1 && $values[0] == 0 && count(array_unique($values)) == 1) {
	// TODO
	die('6');
}

$inserts = array();
$cache_inserts = array();

$query = 'SELECT s.id id
	FROM sensors s
		JOIN sensor_group sg ON (sg.sensor = s.id)
		JOIN `group` g ON (sg.group = g.id)
		JOIN account_location al ON (g.location = al.location)
	WHERE al.account = ?';
$db_sensor_ids = array_map(function($a) { return $a['id']; }, db_query($query, array($user_id), 86400));

$data = db_query('SELECT id, short, min, max FROM sensor_values', array(), 86400);
$sensor_values = array();
foreach($data as $row) {
	$sensor_values[$row['short']] = $row;
}

$dewpoint_data = array();
$rain_sensors = array();

$memcached_updates = array();

for($a=0; $a<count($sensor_ids); $a++) {
	$value = $values[$a];
	$sensor_id = $sensor_ids[$a];
	$what_short = $what_shorts[$a];

	if(!preg_match('/^\-?[0-9\.]+$/', $value)) {
		// TODO
		die('3');
	}

	if(!in_array($sensor_id, $db_sensor_ids)) {
		// TODO
		die('4b');
	}

	if(!isset($sensor_values[$what_short])) {
		// TODO
		die('4a');
	}

	if($sensor_values[$what_short]['min'] && $value < $sensor_values[$what_short]['min']) {
		// ignore value that is too low; ignore the whole measurement (the other values may come from the same sensor!)
		http_response_code(422);
		die('Value too low');
	}
	if($sensor_values[$what_short]['max'] && $value > $sensor_values[$what_short]['max']) {
		// ignore value that is too high; ignore the whole measurement (the other values may come from the same sensor!)
		http_response_code(422);
		die('Value too high');
	}

	if($what_short == 'humid' || $what_short == 'temp' || $what_short == 'wind') {
		if(!isset($dewpoint_data[$sensor_id])) {
			$dewpoint_data[$sensor_id] = array();
		}
		$dewpoint_data[$sensor_id][$what_short] = $value;
	}

	process_values($sensor_id, $what_short, $value);
	if($what_short == 'rain_idx') {
		$rain_sensors[] = $sensor_id;
	}
}

foreach($dewpoint_data as $sensor_id => $data) {
	if(isset($data['temp']) && isset($data['humid'])) {
		$result = dew_point($data['temp'], $data['humid']);

		foreach(array('dewp', 'abshum') as $sensor_value) {
			process_values($sensor_id, $sensor_value, $result[$sensor_value]);
		}
	}

	if(isset($data['temp']) && isset($data['humid']) && isset($data['wind'])) {
		$result = apparent_temperature($data['temp'], $data['humid'], $data['wind']);

		process_values($sensor_id, 'apparent', $result);
	}
}

if(time() - $timestamp < 100) {
	// TODO this needs to be fixed for time() - $timestamp >= 100
	$daily_rain_calculated = false;
	foreach($rain_sensors as $sensor) {
		$one_hour_ago = $timestamp - 3600;

		$total_rain = get_total_rain($one_hour_ago, $timestamp, $sensor, $sensor_values['rain_idx']['id']);
		
		if($total_rain < 100) {
			process_values($sensor, 'rain', $total_rain);
		}

		if(!$daily_rain_calculated && time() - $timestamp < 290) {
			$one_day_ago = time() - 86400;

			$daily_rain = get_total_rain($one_day_ago, time(), $sensor, $sensor_values['rain_idx']['id']);
			if($daily_rain < 1000) {
				$memcached->set("${memcached_prefix}_daily_rain", $daily_rain, 86400);
				$daily_rain_calculated = true;
			}
		}
	}
}

do_inserts('sensor_cache', $cache_inserts);
do_inserts('sensor_data', $inserts);

if(time() - $timestamp < 290) {
	foreach($memcached_updates as $value) {
		set_cached_value($value['sensor'], $value['what'], $value['value']);
	}
}

die('ok');

