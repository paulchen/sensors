<?php

function perform_delete($ids) {
	$id_string = implode(', ', $ids);
	print("DELETE FROM sensor_data WHERE id IN ($id_string);\n");
}

$handle = fopen('cleanup.dat', 'r');
$ids = array();
while(!feof($handle)) {
	$row = trim(fgets($handle));
	if(strlen($row) == 0 || !preg_match('/^[0-9]+$/', $row)) {
		continue;
	}
	$ids[] = $row;
	if(count($ids) == 10000) {
		perform_delete($ids);
		$ids = array();
	}
}
if(count($ids) > 0) {
	perform_delete($ids);
}
fclose($handle);

