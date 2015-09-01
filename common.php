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
db_query('SET NAMES utf8');

unset($db_name);
unset($db_host);

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
	header('WWW-Authenticate: Basic realm="Sensors"');
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

function t($input = '', $values = array()) {
	global $translations, $config, $lang_id, $lang;

	if(!isset($lang_id)) {
		$lang = $config['default_language'];
		if(isset($_REQUEST['lang'])) {
			$lang = $_REQUEST['lang'];
		}
		$data = db_query('SELECT id FROM languages WHERE language = ?', array($lang));
		if(count($data) == 0) {
			$lang = $config['default_language'];
			$data = db_query('SELECT id FROM languages WHERE language = ?', array($lang));
		}
		$lang_id = $data[0]['id'];
	}
	if(!isset($translations)) {
		$data = db_query('SELECT source, translation FROM translations WHERE language = ?', array($lang_id));
		$translations = array();
		foreach($data as $row) {
			$translations[$row['source']] = $row['translation'];
		}
	}
	if($input == '') {
		return '';
	}
	if($lang == 'en') {
		$output = $input;
	}
	else {
		if(!isset($translations[$input])) {
			// TODO locking

			$data = db_query('SELECT id FROM translations WHERE source = ? AND language = ?', array($input, $lang_id));
			if(count($data) < 1) {
				db_query('INSERT INTO translations (source, language, translation) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE language = ?', array($input, $lang_id, $input, $lang_id));
			}
			$output = $input;
		}
		else {
			$output = $translations[$input];
		}
	}
	foreach($values as $value) {
		$output = preg_replace('/%s/', $value, $output, 1);
	}

	return $output;
}

function round_local($value, $decimals) {
	global $config, $lang;

	$ret = round($value, $decimals);
	$ret = str_replace('.', $config["decimal_mark.$lang"], $ret);

	return $ret;
}

function is_cli() {
	return (php_sapi_name() == 'cli');
}

function get_rain() {
	// TODO hard-coded constants
	// TODO number formatting
	$query = 'SELECT SUM(value) value FROM (SELECT value FROM `sensor_data` WHERE sensor = ? AND what = ? AND timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY HOUR(timestamp) ORDER BY id DESC) a';
	$data = db_query($query, array(9, 4));
	if(count($data) == 0) {
		return 'unknown';
	}
	else {
		$rain = round($data[0]['value'], 2);
		if($rain <= '0.1') {
			$rain = 0;
		}
		$rain .= ' mm';
		return $rain;
	}
}

function get_last_cron_run() {
	$query = 'SELECT UNIX_TIMESTAMP(timestamp) timestamp FROM cronjob_executions ORDER BY id DESC LIMIT 0, 1';
	$data = db_query($query);
	if(count($data) == 0) {
		return '';
	}
	return $data[0]['timestamp'];
}

function get_last_successful_cron_run() {
	$query = 'SELECT UNIX_TIMESTAMP(timestamp) timestamp FROM raw_data ORDER BY id DESC LIMIT 0, 1';
	$data = db_query($query);
	if(count($data) == 0) {
		return '';
	}
	return $data[0]['timestamp'];
}

// initialize translation data
t();

