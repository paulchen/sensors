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



$sensor_ids = isset($_REQUEST['sensor']) ? array($_REQUEST['sensor']) : explode(';', $_REQUEST['sensors']);
$what_shorts = isset($_REQUEST['what']) ? array($_REQUEST['what']) : explode(';', $_REQUEST['whats']);
$values = isset($_REQUEST['value']) ? array($_REQUEST['value']) : explode(';', $_REQUEST['values']);

if(count($sensor_ids) != count($what_shorts) || count($sensor_ids) != count($values)) {
	// TODO
	die('5');
}

$inserts = array();

$db_sensor_ids = array_map(function($a) { return $a['id']; }, db_query('SELECT id FROM sensors'));

$data = db_query('SELECT id, short FROM sensor_values');
$sensor_values = array();
foreach($data as $row) {
	$sensor_values[$row['short']] = $row['id'];
}

for($a=0; $a<count($sensor_ids); $a++) {
	$value = $values[$a];
	$sensor_id = $sensor_ids[$a];
	$what_short = $what_shorts[$a];

	print("$sensor_id $what_short $value\n");

	if(!preg_match('/^[0-9\.]+$/', $value)) {
		// TODO
		die('3');
	}

	if(!in_array($sensor_id, $db_sensor_ids) || !isset($sensor_values[$what_short])) {
		// TODO
		die('4');
	}

	$inserts[] = $sensor_id;
	$inserts[] = $sensor_values[$what_short];
	$inserts[] = $value;
}

$parameters = array();
for($a=0; $a<count($inserts)/3; $a++) {
	$parameters[] = '(NOW(), ?, ?, ?)';
}
$parameter_string = implode(', ', $parameters);

db_query("INSERT INTO sensor_cache (timestamp, sensor, what, value) VALUES $parameter_string", $inserts);
db_query("INSERT INTO sensor_data (timestamp, sensor, what, value) VALUES $parameter_string", $inserts);

die('ok');

