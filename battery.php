<?php

chdir(dirname(__FILE__));
$config = parse_ini_file('config.properties');
if(!$config) {
	echo "Could not read configuration file.\n";
	die(3);
}

$mysqli = new mysqli($config['db_host'], $config['db_username'], $config['db_password'], $config['db_database']);
if($mysqli->connect_errno) {
	echo "Could not connect to database.\n";
	die(3);
}

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

