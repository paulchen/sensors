<?php

chdir(dirname(__FILE__));

$http_auth = true;
require_once('common.php');

if(!isset($_GET['id'])) {
	echo "Missing GET parameter 'id'.\n";
	die(3);
}

$query = 'INSERT INTO battery_changes (sensor) VALUES (?)';
db_query($query, array($_GET['id']));

header('Location: ' . dirname($_SERVER['REQUEST_URI']) . '/#battery');

