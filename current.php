#!/usr/bin/php
<?php

$time = time();
$time -= $time%300;

chdir(dirname(__FILE__));
$config_file = 'config.properties';

require_once('common.php');

$query = 'SELECT id, short, decimals FROM sensor_values';
$values = db_query($query);

$query = 'SELECT id FROM sensors';
$sensors = db_query($query);

chdir($config['munin_data_directory']);

$query = 'SELECT UNIX_TIMESTAMP(timestamp) timestamp, value FROM sensor_data WHERE sensor = ? AND what = ? ORDER BY id DESC LIMIT 0, 1';
foreach($sensors as $sensor) {
	foreach($values as $value) {
		$data = db_query($query, array($sensor['id'], $value['id']));
		if(count($data) > 0) {
			$timestamp = intval($data[0]['timestamp']);
			$measurement = floatval($data[0]['value']);

			if(time()-$timestamp < $config['value_outdated_period']) {
				$measurement = round($measurement, $value['decimals']);
				$pattern = "{$config['munin_prefix']}-sensor*{$value['short']}*sensor{$sensor['id']}-g.rrd";
				foreach(glob($pattern) as $filename) {
					echo "rrdtool update $filename $time:$measurement\n";
					system("rrdtool update $filename $time:$measurement\n");
				}	
			}
		}
	}
}

