<?php

if($argc != 2) {
	die(1);
}
switch($argv[1]) {
	case 'sensor_data':
	case 'sensor_cache':
	case 'sensor_test':
		$table = $argv[1];
		break;

	default:
		die(2);
}

require_once(dirname(__FILE__) . '/../common.php');

function perform_delete($ids) {
	global $table;

	$id_string = implode(', ', $ids);
	$first_id = $ids[0];
	print("$first_id\n");
	db_query("DELETE FROM $table WHERE id IN ($id_string)");
}

$handle = fopen('cleanup.dat', 'r');
$ids = array();
while(!feof($handle)) {
	$row = trim(fgets($handle));
	if(strlen($row) == 0 || !preg_match('/^[0-9]+,[0-9]+,[0-9]+$/', $row)) {
		continue;
	}
	$items = explode(',', $row);
	$ids[] = $items[0];
	if(count($ids) == 10000) {
		perform_delete($ids);
		$ids = array();
	}
}
if(count($ids) > 0) {
	perform_delete($ids);
}
fclose($handle);

