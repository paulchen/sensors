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

function handle_candidate($handle, $candidates, $key, $newest_value) {
	if(isset($candidates[$key]) && $candidates[$key]['value'] == $newest_value['value'] && $newest_value['timestamp'] - $candidates[$key]['timestamp'] < 1800) {
		$candidate_id = $candidates[$key]['id'];
		$formatted_candidate_timestamp = date('Y-m-d H:i:s', $candidates[$key]['timestamp']);
		$formatted_previous_timestamp = date('Y-m-d H:i:s', $candidates[$key]['previous']['timestamp']);
		$formatted_timestamp = date('Y-m-d H:i:s', $newest_value['timestamp']);

		print("Row to delete: $candidate_id, timestamp: $formatted_candidate_timestamp, value: {$candidates[$key]['value']} -- ");
		print("existing previous row: {$candidates[$key]['previous']['id']}, timestamp: $formatted_previous_timestamp, value: {$candidates[$key]['previous']['value']} -- ");
		print("existing next row: {$newest_value['id']}, timestamp: $formatted_timestamp, value: {$newest_value['value']}\n");

		fprintf($handle, "$candidate_id,{$candidates[$key]['previous']['id']},{$newest_value['id']}\n");
	}	
}

require_once(dirname(__FILE__) . '/../common.php');

$newest_values = array();
$candidates = array();
print("Executing query...\n");
$stmt = db_query_resultset("SELECT id, UNIX_TIMESTAMP(timestamp) timestamp, sensor, what, value FROM $table ORDER BY timestamp ASC");
print("Execution complete, scanning result\n");
$data = array();
$row_count = 0;
$handle = fopen('cleanup.dat', 'w');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
	$sensor = $row['sensor'];
	$what = $row['what'];
	$timestamp = $row['timestamp'];
	$value = $row['value'];
	$id = $row['id'];

	$formatted_timestamp = date('Y-m-d H:i:s', $timestamp);

	$row_count++;
	if($row_count % 10000 == 0) {
		print("$row_count rows scanned, timestamp: $formatted_timestamp\n");
	}

	$key = "${sensor}_${what}";
	if(isset($newest_values[$key])) {
		$newest_timestamp = $newest_values[$key]['timestamp'];
		$newest_value = $newest_values[$key]['value'];
		$newest_id = $newest_values[$key]['id'];

		$formatted_newest_timestamp = date('Y-m-d H:i:s', $timestamp);

		if(time() - $timestamp > 3600 && $timestamp - $newest_timestamp < 1800 && $value == $newest_value) {
			handle_candidate($handle, $candidates, $key, array('timestamp' => $timestamp, 'value' => $value, 'id' => $id));

			// add candidate
			$candidates[$key] = array('timestamp' => $timestamp, 'value' => $value, 'id' => $id, 'previous' => $newest_values[$key]);
		}
		else {
			$newest_values[$key] = array('timestamp' => $timestamp, 'value' => $value, 'id' => $id);

			// check and output candidate
			handle_candidate($handle, $candidates, $key, $newest_values[$key]); 

			if(isset($candidates[$key])) {
				unset($candidates[$key]);
			}
		}
	}
	else {
		$newest_values[$key] = array('timestamp' => $timestamp, 'value' => $value, 'id' => $id);
	}
}

db_stmt_close($stmt);
fclose($handle);

