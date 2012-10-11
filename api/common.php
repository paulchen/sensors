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

function get_type_data($sensor_data = array()) {
	global $mysqli;

	if(!is_array($sensor_data)) {
		// TODO
		die();
	}

	$types = array();
	foreach($sensor_data as $sensor) {
		foreach($sensor['values'] as $key => $value) {
			if(!in_array($key, $types)) {
				$types[] = $key;
			}
		}
	}

	$stmt = $mysqli->prepare('SELECT id, name, format, min, max, decimals FROM sensor_values ORDER BY id ASC');
	$stmt->execute();
	$stmt->bind_result($id, $name, $format, $min, $max, $decimals);
	$type_data = array();
	while($stmt->fetch()) {
		if(in_array($id, $types)) {
			$type_data[] = array('id' => $id, 'name' => $name, 'format' => $format, 'min' => $min, 'max' => $max, 'decimals' => $decimals);
		}
	}
	$stmt->close();

	return $type_data;
}

function get_limits($sensors = array()) {
	global $mysqli;

	if(!is_array($sensors)) {
		// TODO
		die();
	}

	$stmt = $mysqli->prepare('SELECT sensor, value, low_crit, low_warn, high_warn, high_crit FROM sensor_limits ORDER BY sensor ASC, value ASC');
	$stmt->execute();
	$stmt->bind_result($sensor, $value, $low_crit, $low_warn, $high_warn, $high_crit);
	$limits = array();
	while($stmt->fetch()) {
		if(in_array($sensor, $sensors)) {
			if(!isset($limits[$sensor])) {
				$limits[$sensor] = array();
			}
			$limits[$sensor][$value] = array('low_crit' => $low_crit, 'low_warn' => $low_warn, 'high_warn' => $high_warn, 'high_crit' => $high_crit);
		}
	}
	$stmt->close();
	
	return $limits;
}

function get_sensors_state($sensors = array()) {
	global $mysqli, $config;

	if(!is_array($sensors)) {
		// TODO
		die();
	}

	if(count($sensors) == 0) {
		return array();
	}

	$limits = get_limits($sensors);

	$stmt = $mysqli->prepare('SELECT id, format, name FROM sensor_values');
	$stmt->execute();
	$stmt->bind_result($id, $format, $name);
	$formats = array();
	while($stmt->fetch()) {
		$formats[$id] = array('format' => $format, 'name' => $name);
	}
	$stmt->close();

	$question_marks = str_repeat('?, ', count($sensors)-1) . '?';
	$query = 'SELECT sensor, what, UNIX_TIMESTAMP(timestamp) timestamp, value FROM sensor_data WHERE timestamp > ? AND sensor IN (' . $question_marks . ') ORDER BY sensor ASC, what ASC';
	$stmt = $mysqli->prepare($query);
	$args = array(str_repeat('s', count($sensors)+1), &$start_timestamp);
	foreach($sensors as $sensor) {
		$var = $sensor;
		$args[] = &$var;
		unset($var);
	}
	$start_timestamp = date('Y-m-d H:i', time()-86400);
	$ref = new ReflectionClass('mysqli_stmt');
	$method = $ref->getMethod('bind_param');
	$method->invokeArgs($stmt, $args);
	$stmt->execute();
	$stmt->bind_result($sensor_id, $what, $timestamp, $value);
	$sensor_data = array();
	while($stmt->fetch()) {
		// TODO use data from table sensor_values
		$value = round($value, 2);
		if(time()-$timestamp > $config['value_outdated_period']) {
			$state = 'unknown';
		}
		else if($value <= $limits[$sensor_id][$what]['low_crit']) {
			$state = 'critical';
		}
		else if($value <= $limits[$sensor_id][$what]['low_warn']) {
			$state = 'warning';
		}
		else if($value >= $limits[$sensor_id][$what]['high_crit']) {
			$state = 'critical';
		}
		else if($value >= $limits[$sensor_id][$what]['high_warn']) {
			$state = 'warning';
		}
		else {
			// TODO state unknown?
			$state = 'ok';
		}

		if(!isset($sensor_data[$sensor_id])) {
			$sensor_data[$sensor_id] = array('values' => array());
		}
		if(!isset($sensor_data[$sensor_id]['values'][$what])) {
			$sensor_data[$sensor_id]['values'][$what] = array('type' => $what, 'description' => $formats[$what]['name'], 'format' => $formats[$what]['format'], 'measurements' => array(array('timestamp' => $timestamp, 'value' => $value, 'state' => $state)));
		}
		else {
			$old_timestamp = $sensor_data[$sensor_id]['values'][$what]['measurements'][0]['timestamp'];
			if($old_timestamp < $timestamp) {
				$sensor_data[$sensor_id]['values'][$what] = array('type' => $what, 'description' => $formats[$what]['name'], 'format' => $formats[$what]['format'], 'measurements' => array(array('timestamp' => $timestamp, 'value' => $value, 'state' => $state)));
			}
		}
	}
	$stmt->close();

	return $sensor_data;
}

