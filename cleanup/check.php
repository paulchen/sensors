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

function fetch_row_by_id($id) {
	global $table;

	$data = db_query("SELECT id, sensor, what, value, UNIX_TIMESTAMP(`timestamp`) `timestamp` FROM $table WHERE id=?", array($id));
	if(count($data) == 0) {
		throw new Exception("Row with id $id not found");
	}
	return $data[0];
}

function compare_rows($rows, $field) {
	if(count($rows) == 0) {
		return;
	}

	$value = $rows[0][$field];
	foreach($rows as $row) {
		if($row[$field] != $value) {
			throw new Exception("In row {$row['id']} the field $field has the value {$row[$field]}, but $value was expected");
		}
	}
}

$counter=0;

function check_row($candidate_id, $previous_id, $next_id) {
	global $table, $counter;

	$counter++;

	print("Checking row with ids $candidate_id, $previous_id, $next_id ($counter)\n");

	$candidate_row = fetch_row_by_id($candidate_id);
	$previous_row = fetch_row_by_id($previous_id);
	$next_row = fetch_row_by_id($next_id);

	compare_rows(array($candidate_row, $previous_row, $next_row), 'sensor');
	compare_rows(array($candidate_row, $previous_row, $next_row), 'what');
	compare_rows(array($candidate_row, $previous_row, $next_row), 'value');

	$previous_timestamp = $previous_row['timestamp'];
	$candidate_timestamp = $candidate_row['timestamp'];
	$next_timestamp = $next_row['timestamp'];

	if($previous_timestamp >= $candidate_timestamp || $candidate_timestamp >= $next_timestamp) {
		throw new Exception("Timestamps for rows $candidate_id, $previous_id, and $next_id not in order: $previous_timestamp, $candidate_timestamp, $next_timestamp");
	}

	$rows = db_query("SELECT id, UNIX_TIMESTAMP(`timestamp`) `timestamp`, sensor, what, value FROM $table WHERE sensor = ? AND what = ? AND `timestamp` BETWEEN FROM_UNIXTIME(?) AND FROM_UNIXTIME(?)", array($candidate_row['sensor'], $candidate_row['what'], $previous_timestamp, $next_timestamp));
	
	compare_rows($rows, 'value');
}

$handle = fopen('cleanup.dat', 'r');
$ids = array();
while(!feof($handle)) {
	$row = trim(fgets($handle));
	if(strlen($row) == 0 || !preg_match('/^[0-9]+,[0-9]+,[0-9]+$/', $row)) {
		continue;
	}
	$items = explode(',', $row);
	check_row($items[0], $items[1], $items[2]);
}
fclose($handle);

