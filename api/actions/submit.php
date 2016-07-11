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

for($a=0; $a<count($sensor_ids); $a++) {
	$value = $values[$a];
	$sensor_id = $sensor_ids[$a];
	$what_short = $what_shorts[$a];

	if(!preg_match('/^[0-9\.]+$/', $value)) {
		// TODO
		die('3');
	}
	$sensor = db_query_single('SELECT id FROM sensors WHERE id = ?', array($sensor_id));
	$what = db_query_single('SELECT id FROM sensor_values WHERE short = ?', array($what_short));
	if($sensor == null || $what == null) {
		// TODO
		die('4');
	}

	$inserts[] = array($sensor['id'], $what['id'], $value);
}

foreach($inserts as $insert) {
	db_query('INSERT INTO sensor_cache (timestamp, sensor, what, value) VALUES (NOW(), ?, ?, ?)', $insert);
	db_query('INSERT INTO sensor_data (timestamp, sensor, what, value) VALUES (NOW(), ?, ?, ?)', $insert);
}

die('ok');

