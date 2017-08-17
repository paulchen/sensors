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

function get_total_rain($from, $to, $sensor, $what) {
	if(time() - $to > 100) {
		$data = db_query('SELECT value FROM sensor_data WHERE UNIX_TIMESTAMP(timestamp) > ? AND UNIX_TIMESTAMP(timestamp) <= ? AND sensor = ? AND what = ? ORDER BY timestamp ASC', array($from, $to, $sensor, $what));
	}
	else {
		$data = db_query('SELECT value FROM sensor_cache WHERE UNIX_TIMESTAMP(timestamp) > ? AND UNIX_TIMESTAMP(timestamp) <= ? AND sensor = ? AND what = ? ORDER BY timestamp ASC', array($from, $to, $sensor, $what));
	}
	$values = array_map(function($a) { return $a['value']; }, $data);

	return calculate_total_rain($values);
}


