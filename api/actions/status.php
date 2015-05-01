<?php
if(basename($_SERVER['SCRIPT_FILENAME']) != 'index.php') {
	// TODO
	die();
}

$sensors = get_sensors();
$sensor_ids = array_map(function($value) { return $value['id']; }, $sensors);
$sensor_data = get_sensors_state($sensor_ids);
$types = get_type_data($sensor_data);
$states = get_states();
$images = get_image_urls();
$status = get_status();
$rain = get_rain();

$success = true;

