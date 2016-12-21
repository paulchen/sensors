<?php

function calculate_total_rain($rain_index) {
	if(count($rain_index) < 2) {
		return 0;
	}

	$overflows = 0;
	for($i=1; $i<count($rain_index); $i++) {
		if($rain_index[$i-1] - $rain_index[$i] > 1) {
			$overflows++;
		}
	}

	$first = $rain_index[0];
	$last = $rain_index[count($rain_index) - 1];

	if($last < $first) {
		$last = $first;
	}
	return ($last - $first + $overflows * 4096) * 0.295;
}

function get_total_rain($since, $sensor, $what) {
	$data = db_query('SELECT value FROM sensor_cache WHERE UNIX_TIMESTAMP(timestamp) > ? AND sensor = ? AND what = ?', array($since, $sensor, $what));
	$values = array_map(function($a) { return $a['value']; }, $data);

	return calculate_total_rain($values);
}

if(!isset($rain_sensors) || count($rain_sensors) == 0) {
	return;
}

$daily_rain_calculated = false;
foreach($rain_sensors as $sensor) {
	$one_hour_ago = time() - 3600;

	$total_rain = get_total_rain($one_hour_ago, $sensor, $sensor_values['rain_idx']);

	$what = $sensor_values['rain'];
	db_query('INSERT INTO sensor_data (timestamp, sensor, what, value) VALUES (NOW(), ?, ?, ?)', array($sensor, $what, $total_rain));
	db_query('INSERT INTO sensor_cache (timestamp, sensor, what, value) VALUES (NOW(), ?, ?, ?)', array($sensor, $what, $total_rain));

	if(!$daily_rain_calculated) {
		$one_day_ago = time() - 86400;

		$daily_rain = get_total_rain($one_day_ago, $sensor, $sensor_values['rain_idx']);
		$memcached->set('ipwe_daily_rain', $daily_rain, 86400);
		$daily_rain_calculated = true;
	}
}

