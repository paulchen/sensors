#!/usr/bin/php
<?php

require_once(dirname(__FILE__) . '/common.php');

$last_run = get_last_cron_run();

if($last_run != '') {
	$text = 'last run: ' . date('Y-m-d H:i:s', $last_run);
}

if($last_run == '') {
	$status = 'UNKNOWN';
	$status_code = '3';
	$text = 'last_run: never';
}
else if(time()-$last_run > 30*60) {
	$status = 'CRITICAL';
	$status_code = 2;
}
else if(time()-$last_run > 10*60) {
	$status = 'WARNING';
	$status_code = 1;
}
else {
	$status = 'OK';
	$status_code = 0;
}

echo "$status - $text\n";
exit($status_code);

