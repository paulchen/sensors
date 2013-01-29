<?php

chdir(dirname(__FILE__));
if(!isset($config_file)) {
	$config = parse_ini_file('config.properties');
}
else {
	$config = parse_ini_file('config.properties');
}

if(!$config) {
	echo "Could not read configuration file.\n";
	die(3);
}

$mysqli = new mysqli($config['db_host'], $config['db_username'], $config['db_password'], $config['db_database']);
if($mysqli->connect_errno) {
	echo "Could not connect to database.\n";
	die(3);
}

function http_auth() {
	global $mysqli;

	/* HTTP basic authentication */
	if(!isset($_SERVER['PHP_AUTH_USER'])) {
		header('WWW-Authenticate: Basic realm="Sensors API"');
		header('HTTP/1.0 401 Unauthorized');
		// TODO XML reply
		die();
	}

	$username = $_SERVER['PHP_AUTH_USER'];
	$password = $_SERVER['PHP_AUTH_PW'];
	
	$stmt = $mysqli->prepare('SELECT hash FROM api_accounts WHERE username = ?');
	$stmt->bind_param('s', $username);
	$stmt->execute();
	$stmt->bind_result($db_hash);
	$found = false;
	while($stmt->fetch()) {
		$found = true;
	}
	$stmt->close();

	$hash = crypt($password, $db_hash);
	if($hash != $db_hash) {
		// TODO duplicate code
		header('WWW-Authenticate: Basic realm="Sensors API"');
		header('HTTP/1.0 401 Unauthorized');
		// TODO XML reply
		die();
	}
}


