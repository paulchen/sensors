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


function dew_point($temp, $humid) {
	// https://www.wetterochs.de/wetter/feuchte.html

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

	return array('dewp' => $td, 'abshum' => $abshum);
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

	return ($hi_f - 32) / 1.8;
}


$sensor_ids = isset($_REQUEST['sensor']) ? array($_REQUEST['sensor']) : explode(';', $_REQUEST['sensors']);
$what_shorts = isset($_REQUEST['what']) ? array($_REQUEST['what']) : explode(';', $_REQUEST['whats']);
$values = isset($_REQUEST['value']) ? array($_REQUEST['value']) : explode(';', $_REQUEST['values']);
$timestamp = isset($_REQUEST['timestamp']) ? $_REQUEST['timestamp'] : time();

if(count($sensor_ids) != count($what_shorts) || count($sensor_ids) != count($values)) {
	// TODO
	die('5');
}

$inserts = array();

$query = 'SELECT s.id id
	FROM sensors s
		JOIN sensor_group sg ON (sg.sensor = s.id)
		JOIN `group` g ON (sg.group = g.id)
		JOIN account_location al ON (g.location = al.location)
	WHERE al.account = ?';
$db_sensor_ids = array_map(function($a) { return $a['id']; }, db_query($query, array($user_id)));

$data = db_query('SELECT id, short FROM sensor_values');
$sensor_values = array();
foreach($data as $row) {
	$sensor_values[$row['short']] = $row['id'];
}

$dewpoint_data = array();
$rain_sensors = array();
for($a=0; $a<count($sensor_ids); $a++) {
	$value = $values[$a];
	$sensor_id = $sensor_ids[$a];
	$what_short = $what_shorts[$a];

	if(!preg_match('/^\-?[0-9\.]+$/', $value)) {
		// TODO
		die('3');
	}

	if(!in_array($sensor_id, $db_sensor_ids) || !isset($sensor_values[$what_short])) {
		// TODO
		die('4');
	}

	$inserts[] = $timestamp;
	$inserts[] = $sensor_id;
	$inserts[] = $sensor_values[$what_short];
	$inserts[] = $value;

	if($what_short == 'rain_idx') {
		$rain_sensors[] = $sensor_id;
	}

	if($what_short == 'humid' || $what_short == 'temp' || $what_short == 'wind') {
		if(!isset($dewpoint_data[$sensor_id])) {
			$dewpoint_data[$sensor_id] = array();
		}
		$dewpoint_data[$sensor_id][$what_short] = $value;
	}
}

foreach($dewpoint_data as $sensor_id => $data) {
	if(isset($data['temp']) && isset($data['humid'])) {
		$result = dew_point($data['temp'], $data['humid']);

		foreach(array('dewp', 'abshum') as $sensor_value) {
			$inserts[] = $timestamp;
			$inserts[] = $sensor_id;
			$inserts[] = $sensor_values[$sensor_value];
			$inserts[] = $result[$sensor_value];
		}
	}

	if(isset($data['temp']) && isset($data['humid']) && isset($data['wind'])) {
		$result = apparent_temperature($data['temp'], $data['humid'], $data['wind']);

		$inserts[] = $timestamp;
		$inserts[] = $sensor_id;
		$inserts[] = $sensor_values['apparent'];
		$inserts[] = $result;
	}
}

$parameters = array();
for($a=0; $a<count($inserts)/4; $a++) {
	$parameters[] = '(FROM_UNIXTIME(?), ?, ?, ?)';
}
$parameter_string = implode(', ', $parameters);

db_query("INSERT INTO sensor_cache (timestamp, sensor, what, value) VALUES $parameter_string", $inserts);
db_query("INSERT INTO sensor_data (timestamp, sensor, what, value) VALUES $parameter_string", $inserts);

if(count($rain_sensors) > 0) {
	require_once(dirname(__FILE__) . '/rain.php');
}

die('ok');

