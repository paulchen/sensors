<?php

require_once(dirname(__FILE__) . '/rain_common.php');

if(!isset($rain_sensors) || count($rain_sensors) == 0) {
	return;
}

$daily_rain_calculated = false;
foreach($rain_sensors as $sensor) {
	$one_hour_ago = time() - 3600;

	$total_rain = get_total_rain($one_hour_ago, time(), $sensor, $sensor_values['rain_idx']);

	$what = $sensor_values['rain'];
	db_query('INSERT INTO sensor_data (timestamp, sensor, what, value) VALUES (NOW(), ?, ?, ?)', array($sensor, $what, $total_rain));
	db_query('INSERT INTO sensor_cache (timestamp, sensor, what, value) VALUES (NOW(), ?, ?, ?)', array($sensor, $what, $total_rain));

	if(!$daily_rain_calculated) {
		$one_day_ago = time() - 86400;

		$daily_rain = get_total_rain($one_day_ago, time(), $sensor, $sensor_values['rain_idx']);
		$memcached->set('ipwe_daily_rain', $daily_rain, 86400);
		$daily_rain_calculated = true;
	}
}

