<?php

chdir(dirname(__FILE__));

$http_auth = true;
require_once('common.php');

if(!isset($_GET['id'])) {
	echo "Missing GET parameter 'id'.\n";
	die(3);
}

$stmt = $mysqli->prepare('INSERT INTO battery_changes (sensor) VALUES (?)');
$start_timestamp = date('Y-m-d H:i', time()-86400);
$stmt->bind_param('i', $_GET['id']);
$stmt->execute();
$stmt->close();

header('Location: ' . dirname($_SERVER['REQUEST_URI']) . '/#battery');

