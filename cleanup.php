<?php

require_once(dirname(__FILE__) . '/common.php');

$newest_values = array();
print("Executing query...\n");
$stmt = db_query_resultset('SELECT id, UNIX_TIMESTAMP(timestamp) timestamp, sensor, what, value FROM sensor_data ORDER BY timestamp ASC');
print("Execution complete, scanning result\n");
$data = array();
$row_count = 0;
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

		if($timestamp - $newest_timestamp < 290 && $value == $newest_value) {
			print("Row to delete: $id, timestamp: $formatted_timestamp, value: $value -- existing row: $newest_id, timestamp: $formatted_newest_timestamp, value: $newest_value\n");
			// TODO delete row
		}
		else {
			$newest_values[$key] = array('timestamp' => $timestamp, 'value' => $value, 'id' => $id);
		}
	}
	else {
		$newest_values[$key] = array('timestamp' => $timestamp, 'value' => $value, 'id' => $id);
	}
}

db_close_stmt($stmt);

