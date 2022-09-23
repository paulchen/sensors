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

$db_name = $config['db_database'];
$db_host = $config['db_host'];
$db = new PDO("mysql:dbname=$db_name;host=$db_host", $config['db_username'], $config['db_password']);
$db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
$db->setAttribute(PDO::ATTR_TIMEOUT, $config['db_timeout']);
db_query("SET SESSION MAX_STATEMENT_TIME = ${config['db_timeout']}");
db_query('SET NAMES utf8');

unset($db_name);
unset($db_host);

$memcached = new Memcached();
$memcached->addServer('127.0.0.1', '11211');
$memcached_prefix = 'ipwe';

if(isset($http_auth) && $http_auth && !is_cli()) {
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

function db_query_single($query, $parameters = array()) {
	$data = db_query($query, $parameters);
	if(count($data) == 0) {
		return null;
	}
	if(count($data) > 1) {
		// TODO
	}
	return $data[0];
}

function db_query($query, $parameters = array(), $cache_expiration = -1) {
	global $memcached, $memcached_prefix;

	if($cache_expiration > -1) {
		$cache_key = $memcached_prefix . '_query_' . sha1($query . serialize($parameters));
		if($data = $memcached->get($cache_key)) {
			return unserialize($data);
		}
	}

	$stmt = db_query_resultset($query, $parameters);
	$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
	db_stmt_close($stmt);

	if($cache_expiration > -1) {
		$memcached->set($cache_key, serialize($data), $cache_expiration);
	}

	return $data;
}

function db_stmt_close($stmt) {
	if(!$stmt->closeCursor()) {
		$error = $stmt->errorInfo();
		db_error($error[2], debug_backtrace(), $query, $parameters);
	}
}

function db_query_resultset($query, $parameters = array()) {
	global $db;

	$query_start = microtime(true);
	if(!($stmt = $db->prepare($query))) {
		$error = $db->errorInfo();
		db_error($error[2], debug_backtrace(), $query, $parameters);
	}
	foreach($parameters as $key => $value) {
		$stmt->bindValue($key+1, $value);
	}
	if(!$stmt->execute()) {
		$error = $stmt->errorInfo();
		db_error($error[2], debug_backtrace(), $query, $parameters);
	}

	return $stmt;
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
	header('WWW-Authenticate: Basic realm="Sensors"');
	header('HTTP/1.0 401 Unauthorized');
	// TODO XML reply
	die();
}

function http_auth() {
	global $mysqli, $user_id;

	/* HTTP basic authentication */
	if(!isset($_SERVER['PHP_AUTH_USER'])) {
		noauth();
	}

	$username = $_SERVER['PHP_AUTH_USER'];
	$password = $_SERVER['PHP_AUTH_PW'];
	
	$query = 'SELECT id, hash FROM api_accounts WHERE username = ?';
	$data = db_query($query, array($username), 3600);
	if(count($data) == 1) {
		$db_hash = $data[0]['hash'];
		$hash = crypt($password, $db_hash);
		if($hash == $db_hash) {
			$user_id = $data[0]['id'];
			return;
		}
	}

	noauth();
}

function round_local($value, $decimals) {
	global $config;

	$ret = round($value, $decimals);
	$ret = str_replace('.', $config['decimal_mark'], $ret);

	return $ret;
}

function is_cli() {
	return (php_sapi_name() == 'cli');
}

function get_rain_raw() {
	global $memcached, $memcached_prefix;

	$memcached_key = "${memcached_prefix}_daily_rain";
	return $memcached->get($memcached_key);
}

function get_rain() {
	$memcached_data = get_rain_raw();
	if($memcached_data === null) {
		return 'unbekannt';
	}
	$rain = $memcached_data;

	// TODO hard-coded constants
	// TODO number formatting
	$rain = round($rain, 2);
	if($rain <= '0.1') {
		$rain = 0;
	}
	$rain .= ' mm';
	return $rain;
}

function get_last_cron_run() {
	$query = 'SELECT UNIX_TIMESTAMP(MAX(timestamp)) timestamp FROM sensor_cache';
	$data = db_query($query);
	if(count($data) == 0) {
		return '';
	}
	return $data[0]['timestamp'];
}


