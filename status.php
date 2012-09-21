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

$stmt = $mysqli->prepare('SELECT sensor, what, UNIX_TIMESTAMP(timestamp) timestamp, value FROM sensor_data WHERE timestamp > ? ORDER BY id ASC');
$start_timestamp = date('Y-m-d H:i', time()-86400);
$stmt->bind_param('s', $start_timestamp);
$stmt->execute();
$stmt->bind_result($sensor, $what, $timestamp, $value);
$first_values = array();
$max_values = array();
$min_values = array();
$current_values = array();
$keys = array();
while($stmt->fetch()) {
	$key = "$sensor-$what";
	if(!isset($keys[$key])) {
		$keys[$key] = array('sensor' => $sensor, 'what' => $what);
	}

	$current_values[$key] = array('timestamp' => $timestamp, 'value' => $value);
	if(!isset($max_values[$key])) {
		$min_values[$key] = array('timestamp' => $timestamp, 'value' => $value);
		$max_values[$key] = array('timestamp' => $timestamp, 'value' => $value);
	}
	else {
		if($value > $max_values[$key]['value']) {
			$max_values[$key] = array('timestamp' => $timestamp, 'value' => $value);
		}
		else if($value < $min_values[$key]['value']) {
			$min_values[$key] = array('timestamp' => $timestamp, 'value' => $value);
		}
	}
	
	if($timestamp < time()-$config['tendency_period'] || !isset($first_values[$key])) {
		$first_values[$key] = array('timestamp' => $timestamp, 'value' => $value);
	}
}
$stmt->close();

$stmt = $mysqli->prepare('SELECT pos, id FROM sensors');
$stmt->execute();
$stmt->bind_result($pos, $id);
$position = array();
while($stmt->fetch()) {
	$position[$id] = $pos;
}
$stmt->close();

$keys2 = $keys;
uksort($keys, function($a, $b) {
	global $position, $keys2;

	if($position[$keys2[$a]['sensor']] < $position[$keys2[$b]['sensor']]) {
		return -1;
	}
	if($position[$keys2[$a]['sensor']] > $position[$keys2[$b]['sensor']]) {
		return 1;
	}
	if($keys2[$a]['what'] < $keys2[$b]['what']) {
		return -1;
	}
	if($keys2[$a]['what'] > $keys2[$b]['what']) {
		return 1;
	}
	return 0;
});

$tendencies = array();
foreach($keys as $index => $key) {
	$old = $first_values[$index]['value'];
	$new = $current_values[$index]['value'];

	if(abs(1-$old/$new) < $config['stable_margin']) {
		$tendencies[$index] = 'stable';
	}
	else if($old > $new) {
		$tendencies[$index] = 'decreasing';
	}
	else {
		$tendencies[$index] = 'increasing';
	};
}

$stmt = $mysqli->prepare('SELECT id, name, format, decimals FROM sensor_values');
$stmt->execute();
$stmt->bind_result($id, $name, $format, $decimals);
$values = array();
while($stmt->fetch()) {
	$values[$id] = array('name' => $name, 'format' => $format, 'decimals' => $decimals);
}
$stmt->close();

$stmt = $mysqli->prepare('SELECT id, sensor, type, description FROM sensors');
$stmt->execute();
$stmt->bind_result($id, $sensor, $type, $description);
$sensors = array();
while($stmt->fetch()) {
	if($description == '') {
		$description = "Sensor $sensor";
	}
	$sensors[$id] = array('sensor' => $sensor, 'type' => $type, 'description' => $description, 'battery_date' => 'never', 'battery_days' => '', 'battery_state' => 'unknown');
}
$stmt->close();

foreach($sensors as $id => $sensor) {
	$stmt = $mysqli->prepare('SELECT UNIX_TIMESTAMP(timestamp) timestamp FROM battery_changes WHERE sensor = ? ORDER BY id DESC LIMIT 0, 1');
	$stmt->bind_param('i', $id);
	$stmt->execute();
	$stmt->bind_result($timestamp);
	while($stmt->fetch()) {
		$sensors[$id]['battery_date'] = date('Y-m-d H:i', $timestamp);
		$battery_days = floor((time()-$timestamp)/86400);
		$sensors[$id]['battery_days'] = "$battery_days day(s)";
		if($battery_days <= $config['battery_warning']) {
			$sensors[$id]['battery_state'] = 'ok';
		}
		else if($battery_days <= $config['battery_critical']) {
			$sensors[$id]['battery_state'] = 'warning';
		}
		else {
			$sensors[$id]['battery_state'] = 'critical';
		}
	}
	$stmt->close();
}

$stmt = $mysqli->prepare('SELECT sensor, value, low_crit, low_warn, high_warn, high_crit FROM sensor_limits');
$stmt->execute();
$stmt->bind_result($sensor, $value, $low_crit, $low_warn, $high_warn, $high_crit);
$limits = array();
while($stmt->fetch()) {
	$limits["$sensor-$value"] = array('low_crit' => $low_crit, 'low_warn' => $low_warn, 'high_warn' => $high_warn, 'high_crit' => $high_crit);
}
$stmt->close();

$states = array();
$state_class = array();
foreach($keys as $index => $key) {
	if(isset($limits[$index])) {
		if(time()-$current_values[$index]['timestamp'] > $config['value_outdated_period']) {
			$states[$index] = 'UNKNOWN (most recent value is too old)';
			$state_class[$index] = 'unknown';
		}
		else {
			$value = $current_values[$index]['value'];
			if($value <= $limits[$index]['low_crit']) {
				$states[$index] = 'CRITICAL (below limit of ' . str_replace('%s', round($limits[$index]['low_crit'], $values[$key['what']]['decimals']), $values[$key['what']]['format']) . ')';
				$state_class[$index] = 'critical';
			}
			else if($value <= $limits[$index]['low_warn']) {
				$states[$index] = 'WARNING (below limit of ' . str_replace('%s', round($limits[$index]['low_warn'], $values[$key['what']]['decimals']), $values[$key['what']]['format']) . ')';
				$state_class[$index] = 'warning';
			}
			else if($value >= $limits[$index]['high_crit']) {
				$states[$index] = 'CRITICAL (above limit of ' . str_replace('%s', round($limits[$index]['high_crit'], $values[$key['what']]['decimals']), $values[$key['what']]['format']) . ')';
				$state_class[$index] = 'critical';
			}
			else if($value >= $limits[$index]['high_warn']) {
				$states[$index] = 'WARNING (above limit of ' . str_replace('%s', round($limits[$index]['high_warn'], $values[$key['what']]['decimals']), $values[$key['what']]['format']) . ')';
				$state_class[$index] = 'warning';
			}
			else {
				$states[$index] = 'OK';
				$state_class[$index] = 'ok';
			}
		}
	}
	else {
		$states[$index] = 'UNKNOWN (no limits set)';
		$state_class[$index] = 'unknown';
	}
}

foreach($keys as $index => $key) {
	$what = $key['what'];

	$current_values[$index]['formatted_value'] = str_replace('%s', round($current_values[$index]['value'], $values[$what]['decimals']), $values[$what]['format']);
	$current_values[$index]['formatted_timestamp'] = date('Y-m-d H:i', $current_values[$index]['timestamp']);

	$min_values[$index]['formatted_value'] = str_replace('%s', round($min_values[$index]['value'], $values[$what]['decimals']), $values[$what]['format']);
	$min_values[$index]['formatted_timestamp'] = date('Y-m-d H:i', $min_values[$index]['timestamp']);

	$max_values[$index]['formatted_value'] = str_replace('%s', round($max_values[$index]['value'], $values[$what]['decimals']), $values[$what]['format']);
	$max_values[$index]['formatted_timestamp'] = date('Y-m-d H:i', $max_values[$index]['timestamp']);
}

$stmt = $mysqli->prepare('SELECT UNIX_TIMESTAMP(timestamp) FROM cronjob_executions ORDER BY id DESC LIMIT 0, 1');
$stmt->execute();
$stmt->bind_result($timestamp);
$last_cron_run = 'never';
if($stmt->fetch()) {
	$last_cron_run = date('Y-m-d H:i', $timestamp);
}
$stmt->close();

$stmt = $mysqli->prepare('SELECT UNIX_TIMESTAMP(timestamp) FROM raw_data ORDER BY id DESC LIMIT 0, 1');
$stmt->execute();
$stmt->bind_result($timestamp);
$last_successful_cron_run = 'never';
if($stmt->fetch()) {
	$last_successful_cron_run = date('Y-m-d H:i', $timestamp);
}
$stmt->close();

if(php_sapi_name() == 'cli') {
	echo "Last cronjob run: $last_cron_run\n";
	echo "Last successful cronjob run: $last_successful_cron_run\n\n\n";
	foreach($keys as $index => $key) {
		$sensor = $key['sensor'];
		$what = $key['what'];

		echo $sensors[$sensor]['description'] . " - " . $values[$what]['name'] . ":\n\n";
		echo "Current state: " . $states[$index] . "\n";
		echo "Current value: " . $current_values[$index]['formatted_value'] . " (" . $current_values[$index]['formatted_timestamp'] . ")\n";
		echo "Maximum value (24 hours): " . $max_values[$index]['formatted_value'] . " (" . $max_values[$index]['formatted_timestamp'] . ")\n";
		echo "Minimum value (24 hours): " . $min_values[$index]['formatted_value'] . " (" . $min_values[$index]['formatted_timestamp'] . ")\n";
		echo "Current tendency: " . $tendencies[$index] . "\n";
		echo "\n\n";
	}

	exit;
}

$stmt = $mysqli->prepare('SELECT url, row FROM munin_graphs ORDER BY id ASC');
$stmt->execute();
$stmt->bind_result($url, $row);
$graphs = array();
$last_row = -1;
while($stmt->fetch()) {
	$new_row = 0;
	if($last_row != $row) {
		$new_row = 1;
	}
	$graphs[] = array('url' => $url, 'new_row' => $new_row);
	$last_row = $row;
}
$stmt->close();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="refresh" content="60; URL=." />
<title>Sensor status</title>
<style type="text/css">
body { font-family: Verdana,Arial,Helvetica,sans-serif;; }
body > div { margin: auto; }
td, th { white-space: nowrap; text-align: left; background-color: #e0e0e0; width: 800px; }
td.state_ok, td.state_warning, td.state_critical, td.state_unknown { text-align: center; }
td.state_ok { background-color: #00cc33; }
td.state_warning { background-color: #ffa500; }
td.state_critical { background-color: #ff3300; }
td.state_unknown { background-color: #e066ff; }
td.odd { background-color: #f1f1f1; }
div#lastrun { padding-bottom: 2em; }
body > div > p { text-align: center; }
a { text-decoration: none; }
</style>
</head>
<body>
<div>
<h1>Current sensor state</h1>
<div id="lastrun">
Last cronjob run: <?php echo $last_cron_run; ?><br />
Last successful cronjob run: <?php echo $last_successful_cron_run; ?><br />
Last page load: <?php echo date('Y-m-d H:i'); ?>
</div>
<table>
<thead>
<tr><th>Sensor</th><th>Value</th><th>Current state</th><th>Current value</th><th>Maximum value (24 hours)</th><th>Minimum value (24 hours)</th><th>Current tendency</th></tr>
</thead>
<tbody>
<?php $odd = 0; foreach($keys as $index => $key): $sensor = $key['sensor']; $what = $key['what']; $odd = 1-$odd; $oddstring = $odd ? ' class="odd"' : ''; ?>
<tr>
<td<?php echo $oddstring ?>><?php echo $sensors[$sensor]['description'] ?></td>
<td<?php echo $oddstring ?>><?php echo $values[$what]['name'] ?></td>
<td class="state_<?php echo $state_class[$index] ?>"><?php echo $states[$index] ?></td>
<td<?php echo $oddstring ?>><?php echo "<strong>" . $current_values[$index]['formatted_value'] . "</strong> (" . $current_values[$index]['formatted_timestamp'] . ")" ?></td>
<td<?php echo $oddstring ?>><?php echo "<strong>" . $max_values[$index]['formatted_value'] . "</strong> (" . $max_values[$index]['formatted_timestamp'] . ")" ?></td>
<td<?php echo $oddstring ?>><?php echo "<strong>" . $min_values[$index]['formatted_value'] . "</strong> (" . $min_values[$index]['formatted_timestamp'] . ")" ?></td>
<td<?php echo $oddstring ?>><?php echo $tendencies[$index] ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<p>
<?php foreach($graphs as $graph): ?>
<?php if($graph['new_row']): ?><br /><?php endif; ?>
<img src="<?php echo htmlentities($graph['url'], ENT_QUOTES, 'UTF-8') ?>" alt="" />
<?php endforeach; ?>
<br />
</p>
</div>
<a id="battery"></a>
<table>
<thead>
<tr><th>Sensor</th><th>Last battery change</th><th>Days</th><th></th></tr>
</thead>
<tbody>
<?php $odd = 0; foreach($keys as $index => $key): $sensor = $key['sensor']; $what = $key['what']; $odd = 1-$odd; $oddstring = $odd ? ' class="odd"' : ''; ?>
<tr>
<td<?php echo $oddstring ?>><?php echo $sensors[$sensor]['description'] ?></td>
<td<?php echo $oddstring ?>><?php echo $sensors[$sensor]['battery_date'] ?></td>
<td class="state_<?php echo $sensors[$sensor]['battery_state'] ?>"><?php echo $sensors[$sensor]['battery_days'] ?></td>
<td<?php echo $oddstring ?>><a href="battery.php?id=<?php echo $sensor ?>">Change battery</a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</body>
</html>

