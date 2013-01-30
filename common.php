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

$db_name = $config['db_database'];
$db_host = $config['db_host'];
$db = new PDO("mysql:dbname=$db_name;host=$db_host", $config['db_username'], $config['db_password']);
db_query('SET NAMES utf8');

unset($db_name);
unset($db_host);

if(isset($http_auth) && $http_auth) {
	if($config['api_authentication'] == 0) {
		/* do nothing */
	}
	else if($config['api_authentication'] == 1) {
		http_auth();
	}
	else {
		echo "Wrong value for configuration setting 'api_authentication'.\n";
		die(3);
	}
}
	
function db_query($query, $parameters = array()) {
	global $db;

	if(!($stmt = $db->prepare($query))) {
		$error = $db->errorInfo();
		db_error($error[2], debug_backtrace(), $query, $parameters);
	}
	// see https://bugs.php.net/bug.php?id=40740 and https://bugs.php.net/bug.php?id=44639
	foreach($parameters as $key => $value) {
		$stmt->bindValue($key+1, $value, is_numeric($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
	}
	if(!$stmt->execute()) {
		$error = $stmt->errorInfo();
		db_error($error[2], debug_backtrace(), $query, $parameters);
	}
	$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
	if(!$stmt->closeCursor()) {
		$error = $stmt->errorInfo();
		db_error($error[2], debug_backtrace(), $query, $parameters);
	}
	return $data;
}

function db_error($error, $stacktrace, $query, $parameters) {
	global $config;

	$report_email = $config['error_mails_rcpt'];
	$email_from = $config['error_mails_from'];

	ob_start();
	require(dirname(__FILE__) . '/mail_db_error.php');
	$message = ob_get_contents();
	ob_end_clean();

	$headers = "From: $email_from\n";
	$headers .= "Content-Type: text/plain; charset = \"UTF-8\";\n";
	$headers .= "Content-Transfer-Encoding: 8bit\n";

	$subject = 'Database error';

	mail($report_email, $subject, $message, $headers);

	header('HTTP/1.1 500 Internal Server Error');
	echo "A database error has just occurred. Please don't freak out, the administrator has already been notified.";
	die();
}

function noauth() {
	header('WWW-Authenticate: Basic realm="Sensors API"');
	header('HTTP/1.0 401 Unauthorized');
	// TODO XML reply
	die();
}

function http_auth() {
	global $mysqli;

	/* HTTP basic authentication */
	if(!isset($_SERVER['PHP_AUTH_USER'])) {
		noauth();
	}

	$username = $_SERVER['PHP_AUTH_USER'];
	$password = $_SERVER['PHP_AUTH_PW'];
	
	$query = 'SELECT hash FROM api_accounts WHERE username = ?';
	$data = db_query($query, array($username));
	if(count($data) == 1) {
		$db_hash = $data[0]['hash'];
		$hash = crypt($password, $db_hash);
		if($hash == $db_hash) {
			return;
		}
	}

	noauth();
}


