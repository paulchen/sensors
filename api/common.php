<?php
if(basename($_SERVER['SCRIPT_FILENAME']) != 'index.php') {
	// TODO
	die();
}

function get_sensors() {
	global $mysqli;

	$stmt = $mysqli->prepare('SELECT sensor FROM sensor_data WHERE timestamp > ? ORDER BY id DESC');
	$start_timestamp = date('Y-m-d H:i', time()-86400);
	$stmt->bind_param('s', $start_timestamp);
	$stmt->execute();
	$stmt->bind_result($sensor_id);
	$sensor_ids = array();
	$question_marks = array();
	while($stmt->fetch()) {
		if(!in_array($sensor_id, $sensor_ids)) {
			$sensor_ids[] = $sensor_id;
			$question_marks[] = '?';
		}
	}
	$stmt->close();

	$sensors = array();
	if(count($question_marks) > 0) {
		$query = 'SELECT id, description AS name FROM sensors WHERE id IN (' . implode(', ', $question_marks) . ') ORDER BY pos ASC';
		$stmt = $mysqli->prepare($query);
		$types = str_repeat('s', count($question_marks));
		array_unshift($sensor_ids, $types);
		$ref = new ReflectionClass('mysqli_stmt');
		$method = $ref->getMethod('bind_param');
		$method->invokeArgs($stmt, $sensor_ids);
		$stmt->execute();
		$stmt->bind_result($id, $name);
		while($stmt->fetch()) {
			$sensors[] = array('id' => $id, 'name' => $name);
		}
		$stmt->close();
	}

	return $sensors;
}

