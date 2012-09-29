<?php
if(basename($_SERVER['SCRIPT_FILENAME']) != 'index.php') {
	// TODO
	die();
}

$sensors = get_sensors();

$success = true;

