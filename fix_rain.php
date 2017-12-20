<?php
require_once(dirname(__FILE__) . '/api/common.php');
require_once(dirname(__FILE__) . '/api/actions/rain_common.php');

#$query = "SELECT id, UNIX_TIMESTAMP(`timestamp`) `timestamp`, value FROM sensor_data WHERE `timestamp` > '2016-07-20 00:00:00' AND sensor = 9 AND what = 4";
$query = "SELECT id, UNIX_TIMESTAMP(`timestamp`) `timestamp`, value FROM sensor_data WHERE sensor = 9 AND what = 4 AND value > 10 AND `timestamp` > '2017-10-29 00:00:00'";
$stmt = db_query_resultset($query);
$count = 0;
$handle = fopen('/tmp/fix_rain.csv', 'w');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
	fputs($handle, "{$row['id']};{$row['timestamp']};{$row['value']}\n");
}
fclose($handle);
db_stmt_close($stmt);

$handle = fopen('/tmp/fix_rain.csv', 'r');
$handle2 = fopen('/tmp/fix_rain2.csv', 'w');
while(($line = fgets($handle)) !== false) {
	$row = explode(';', trim($line));

	$row['id'] = $row[0];
	$row['timestamp'] = $row[1];
	$row['value'] = $row[2];

	$date = date('Y-m-d H:i:s', $row['timestamp']);
	print "ID: {$row['id']}, $date, aktuell gespeicherter Wert: {$row['value']}\n";
	$query2 = 'SELECT id FROM sensor_cache WHERE UNIX_TIMESTAMP(`timestamp`) >= ? AND UNIX_TIMESTAMP(`timestamp`) <= ? AND sensor = 9 AND what = 4';
	$cache_id = '';
	for($a=0; $a<5; $a++) {	
		$data = db_query($query2, array($row['timestamp'] - $a, $row['timestamp'] + $a));
		if(count($data) > 1) {
			print "\tMehrere Entsprechungen in sensor_cache\n";
			break;
		}
		else if(count($data) == 1) {
			$cache_row = $data[0];
			print "\tEntsprechung in sensor_cache: {$cache_row['id']}\n";
			$cache_id = $cache_row['id'];
			break;
		}
	}
	if($cache_id == '') {
		print "\tKeine Entsprechung in sensor_cache\n";
	}

	$new_rain = get_total_rain($row['timestamp'] - 3600, $row['timestamp'], 9, 6);
	print "\tNeu berechneter Wert: $new_rain ";
	if($row['value'] == $new_rain) {
		echo "(keine Aenderung)";
	}
	else {
		echo "(Aenderung!)";
		fputs($handle2, "{$row['id']};$cache_id;$new_rain\n");
	}
	print "\n";
	print "\n";

	$count++;
}
fclose($handle);
fclose($handle2);
unlink('/tmp/fix_rain.csv');


$handle = fopen('/tmp/fix_rain2.csv', 'r');
while(($line = fgets($handle)) !== false) {
	$row = explode(';', trim($line));

	$data_id = $row[0];
	$cache_id = $row[1];
	$value = $row[2];

	db_query('UPDATE sensor_data SET value = ? WHERE id = ?', array($value, $data_id));
	if($cache_id != '') {
		db_query('UPDATE sensor_cache SET value = ? WHERE id = ?', array($value, $cache_id));
	}
}
fclose($handle);

unlink('/tmp/fix_rain2.csv');

