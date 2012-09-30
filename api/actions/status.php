<?php
if(basename($_SERVER['SCRIPT_FILENAME']) != 'index.php') {
	// TODO
	die();
}

$sensors = get_sensors();
$sensor_ids = array_map(function($value) { return $value['id']; }, $sensors);
$sensor_data = get_sensors_state($sensor_ids);

$success = true;

