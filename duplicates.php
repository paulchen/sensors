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

require_once(dirname(__FILE__) . '/common.php');

$rows = db_query("select min(id) id from $table group by timestamp, sensor, what, value having count(*)>1");
foreach($rows as $row) {
	db_query("delete from $table where id=?", array($row['id']));
}

