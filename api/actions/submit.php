<?php
if(basename($_SERVER['SCRIPT_FILENAME']) != 'index.php') {
	// TODO
	die('1');
}

if(!isset($_REQUEST['sensor']) || !isset($_REQUEST['what']) || !isset($_REQUEST['value'])) {
	// TODO
	die('2');
}

$sensor_id = $_REQUEST['sensor'];
$what_short = $_REQUEST['what'];
$value = $_REQUEST['value'];

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

db_query('INSERT INTO sensor_cache (timestamp, sensor, what, value) VALUES (NOW(), ?, ?, ?)', array($sensor['id'], $what['id'], $value));
db_query('INSERT INTO sensor_data (timestamp, sensor, what, value) VALUES (NOW(), ?, ?, ?)', array($sensor['id'], $what['id'], $value));

die('ok');

