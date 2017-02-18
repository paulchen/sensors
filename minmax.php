<?php

require_once(dirname(__FILE__) . '/common.php');

$sql = 'SELECT sensor, what, value, UNIX_TIMESTAMP(`timestamp`) `timestamp` FROM sensor_data ORDER BY `timestamp` ASC';
$stmt = db_query_resultset($sql);
$data = array();
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
	$sensor = $row['sensor'];
	$what = $row['what'];
	$value = $row['value'];
	$timestamp = $row['timestamp'];

	if(!isset($data[$sensor])) {
		$data[$sensor] = array();
	}
	if(!isset($data[$sensor][$what])) {
		$data[$sensor][$what] = array();
	}

	if(!isset($data[$sensor][$what]['min']) || $value < $data[$sensor][$what]['min']['value']) {
		$data[$sensor][$what]['min'] = array('timestamp' => $timestamp, 'value' => $value);
	}

	if(!isset($data[$sensor][$what]['max']) || $value > $data[$sensor][$what]['max']['value']) {
		$data[$sensor][$what]['max'] = array('timestamp' => $timestamp, 'value' => $value);
	}
}
db_stmt_close($stmt);

db_query('TRUNCATE TABLE all_time_list');

$sql = 'INSERT INTO all_time_list (sensor, what, min_timestamp, min, max_timestamp, max) VALUES (?, ?, FROM_UNIXTIME(?), ?, FROM_UNIXTIME(?), ?)';

foreach($data as $sensor => $item) {
	foreach($item as $what => $subitem) {
		db_query($sql, array($sensor, $what, $subitem['min']['timestamp'], $subitem['min']['value'], $subitem['max']['timestamp'], $subitem['max']['value']));
	}
}


