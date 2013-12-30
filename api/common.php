<?php
if(basename($_SERVER['SCRIPT_FILENAME']) != 'index.php') {
	// TODO
	die();
}

chdir(dirname(__FILE__));
chdir('..');
require_once('common.php');
chdir('api');

function get_sensors() {
	$query = 'SELECT sensor FROM sensor_data WHERE timestamp > ? ORDER BY id DESC';
	$start_timestamp = date('Y-m-d H:i', time()-86400);
	$data = db_query($query, array($start_timestamp));

	$sensor_ids = array();
	$question_marks = array();
	foreach($data as $row) {
		if(!in_array($row['sensor'], $sensor_ids)) {
			$sensor_ids[] = $row['sensor'];
			$question_marks[] = '?';
		}
	}

	$sensors = array();
	if(count($question_marks) > 0) {
		$query = 'SELECT id, description AS name FROM sensors WHERE id IN (' . implode(', ', $question_marks) . ') ORDER BY pos ASC';
		$sensors = db_query($query, $sensor_ids);
	}

	return $sensors;
}

function get_type_data($sensor_data = array()) {
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

	$query = 'SELECT id, name, format, min, max, decimals FROM sensor_values ORDER BY id ASC';
	$data = db_query($query);
	$type_data = array();
	foreach($data as $row) {
		if(in_array($row['id'], $types)) {
			$type_data[] = $row;
		}
	}

	return $type_data;
}

function get_states() {
	return array(
			array('name' => 'ok', 'color' => '#00cc33', 'pos' => 1, 'ok' => 1),
			array('name' => 'warning', 'color' => '#ffa500', 'pos' => 2, 'ok' => 0),
			array('name' => 'critical', 'color' => '#ff3300', 'pos' => 3, 'ok' => 0),
			array('name' => 'unknown', 'color' => '#e066ff', 'pos' => 4, 'ok' => 0)
		);
}

function get_limits($sensors = array()) {
	if(!is_array($sensors)) {
		// TODO
		die();
	}

	$query = 'SELECT sensor, value, low_crit, low_warn, high_warn, high_crit FROM sensor_limits ORDER BY sensor ASC, value ASC';
	$data = db_query($query);
	$limits = array();
	foreach($data as $row) {
		$sensor = $row['sensor'];
		$value = $row['value'];

		unset($row['sensor']);
		unset($row['value']);
		if(in_array($sensor, $sensors)) {
			if(!isset($limits[$sensor])) {
				$limits[$sensor] = array();
			}
			$limits[$sensor][$value] = $row;
		}
	}
	
	return $limits;
}

function get_sensors_state($sensors = array()) {
	global $config;

	if(!is_array($sensors)) {
		// TODO
		die();
	}

	if(count($sensors) == 0) {
		return array();
	}

	$limits = get_limits($sensors);

	$query = 'SELECT id, decimals FROM sensor_values';
	$data = db_query($query);
	$type_decimals = array();
	foreach($data as $row) {
		$type_decimals[$row['id']] = $row['decimals'];
	}

	$question_marks = str_repeat('?, ', count($sensors)-1) . '?';
	$query = 'SELECT sensor, what, UNIX_TIMESTAMP(timestamp) timestamp, value FROM sensor_data WHERE timestamp > ? AND sensor IN (' . $question_marks . ') ORDER BY sensor ASC, what ASC';
	$params = $sensors;
	$start_timestamp = date('Y-m-d H:i', time()-86400);
	array_unshift($params, $start_timestamp);
	$data = db_query($query, $params);
	$sensor_data = array();
	foreach($data as $row) {
		$sensor_id = $row['sensor'];
		$what = $row['what'];
		$timestamp = $row['timestamp'];

		$value = round($row['value'], $type_decimals[$what]);
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
			$state = 'ok';
		}

		if(!isset($sensor_data[$sensor_id])) {
			$sensor_data[$sensor_id] = array('values' => array());
		}
		if(!isset($sensor_data[$sensor_id]['values'][$what])) {
			$sensor_data[$sensor_id]['values'][$what] = array('type' => $what, 'measurements' => array(array('timestamp' => $timestamp, 'value' => $value, 'state' => $state, 'type' => 'current')));
		}
		else {
			$old_timestamp = $sensor_data[$sensor_id]['values'][$what]['measurements'][0]['timestamp'];
			if($old_timestamp < $timestamp) {
				$sensor_data[$sensor_id]['values'][$what] = array('type' => $what, 'measurements' => array(array('timestamp' => $timestamp, 'value' => $value, 'state' => $state, 'type' => 'current')));
			}
		}
	}

	return $sensor_data;
}

function get_image_urls() {
	$query = 'SELECT id, url, row FROM munin_graphs ORDER BY id ASC';
	$data = db_query($query);
	foreach($data as &$row) {
		$row['url'] .= '?' . time();
	}

	unset($row);

	return $data;
}

function get_status() {
	$query = 'SELECT UNIX_TIMESTAMP(timestamp) timestamp FROM cronjob_executions ORDER BY id DESC LIMIT 0, 1';
	$data = db_query($query);
	if(count($data) == 0) {
		$last_cron_run = '';
	}
	else {
		$last_cron_run = $data[0]['timestamp'];
	}

	$query = 'SELECT UNIX_TIMESTAMP(timestamp) timestamp FROM raw_data ORDER BY id DESC LIMIT 0, 1';
	$data = db_query($query);
	if(count($data) == 0) {
		$last_successful_cron_run = '';
	}
	else {
		$last_successful_cron_run = $data[0]['timestamp'];
	}

	return array(
		'last_cron_run' => $last_cron_run,
		'last_successful_cron_run' => $last_successful_cron_run,
		'last_page_load' => time()
	);
}

