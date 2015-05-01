<?php

chdir(dirname(__FILE__));

$http_auth = true;
require_once('common.php');

$query = 'SELECT sensor, what, UNIX_TIMESTAMP(timestamp) timestamp, value FROM sensor_data WHERE timestamp > ? ORDER BY id ASC';
$start_timestamp = date('Y-m-d H:i', time()-86400);
$data = db_query($query, array($start_timestamp));

$first_values = array();
$max_values = array();
$min_values = array();
$avg_values = array();
$current_values = array();
$keys = array();

foreach($data as $row) {
	$sensor = $row['sensor'];
	$what = $row['what'];
	$timestamp = $row['timestamp'];
	$value = $row['value'];

	$key = "$sensor-$what";
	if(!isset($keys[$key])) {
		$keys[$key] = array('sensor' => $sensor, 'what' => $what);
	}

	$current_values[$key] = array('timestamp' => $timestamp, 'value' => $value);
	if(!isset($max_values[$key])) {
		$min_values[$key] = array('timestamp' => $timestamp, 'value' => $value);
		$max_values[$key] = array('timestamp' => $timestamp, 'value' => $value);
		$avg_values[$key] = array('value' => $value, 'count' => 1);
	}
	else {
		if($value > $max_values[$key]['value']) {
			$max_values[$key] = array('timestamp' => $timestamp, 'value' => $value);
		}
		else if($value < $min_values[$key]['value']) {
			$min_values[$key] = array('timestamp' => $timestamp, 'value' => $value);
		}
		$avg_values[$key]['value'] += $value;
		$avg_values[$key]['count']++;
	}
	
	if($timestamp < time()-$config['tendency_period'] || !isset($first_values[$key])) {
		$first_values[$key] = array('timestamp' => $timestamp, 'value' => $value);
	}
}

$query = 'SELECT pos, id FROM sensors';
$data = db_query($query);
$position = array();
foreach($data as $row) {
	$position[$row['id']] = $row['pos'];
}

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

	if($new != 0 && abs(1-$old/$new) < $config['stable_margin']) {
		$tendencies[$index] = 'stable';
	}
	else if($old > $new) {
		$tendencies[$index] = 'decreasing';
	}
	else {
		$tendencies[$index] = 'increasing';
	}

	$avg_values[$index]['value'] /= $avg_values[$index]['count'];
}

$query = 'SELECT id, name, format, decimals FROM sensor_values';
$data = db_query($query);
$values = array();
foreach($data as $row) {
	$values[$row['id']] = $row;
}

$query = 'SELECT s.id id, s.sensor sensor, s.type type, COALESCE(sdn.name, s.description) description
       		FROM sensors s
			LEFT JOIN sensor_display_names sdn ON (s.id = sdn.sensor AND sdn.language = ?)';
$data = db_query($query, array($lang_id));
$sensors = array();
foreach($data as $row) {
	if($row['description'] == '') {
		$row['description'] = "Sensor $sensor";
	}
	$row['battery_date'] = 'never';
	$row['battery_days'] = '';
	$row['battery_state'] = 'unknown';

	$sensors[$row['id']] = $row;
}

foreach($sensors as $id => $sensor) {
	$query = 'SELECT UNIX_TIMESTAMP(timestamp) timestamp FROM battery_changes WHERE sensor = ? ORDER BY id DESC LIMIT 0, 1';
	$data = db_query($query, array($id));
	foreach($data as $row) {
		$timestamp = $row['timestamp'];

		$sensors[$id]['battery_date'] = date($config["date_pattern.php.$lang"], $timestamp);
		$battery_days = floor((time()-$timestamp)/86400);
		$sensors[$id]['battery_days'] = t('%s day(s)', array($battery_days));
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
}

$query = 'SELECT sensor, value, low_crit, low_warn, high_warn, high_crit FROM sensor_limits';
$data = db_query($query);
$limits = array();
foreach($data as $row) {
	$sensor = $row['sensor'];
	$value = $row['value'];
	$limits["$sensor-$value"] = $row;
}

$states = array();
$state_class = array();
foreach($keys as $index => $key) {
	if(isset($limits[$index])) {
		if(time()-$current_values[$index]['timestamp'] > $config['value_outdated_period']) {
			$states[$index] = t('UNKNOWN (most recent value is too old)');
			$state_class[$index] = 'unknown';
		}
		else {
			$value = $current_values[$index]['value'];
			if($value <= $limits[$index]['low_crit']) {
				$states[$index] = t('CRITICAL (below limit of %s)', array(str_replace('%s', round_local($limits[$index]['low_crit'], $values[$key['what']]['decimals']), $values[$key['what']]['format'])));
				$state_class[$index] = 'critical';
			}
			else if($value <= $limits[$index]['low_warn']) {
				$states[$index] = t('WARNING (below limit of %s)', array(str_replace('%s', round_local($limits[$index]['low_warn'], $values[$key['what']]['decimals']), $values[$key['what']]['format'])));
				$state_class[$index] = 'warning';
			}
			else if($value >= $limits[$index]['high_crit']) {
				$states[$index] = t('CRITICAL (above limit of %s)', array(str_replace('%s', round_local($limits[$index]['high_crit'], $values[$key['what']]['decimals']), $values[$key['what']]['format'])));
				$state_class[$index] = 'critical';
			}
			else if($value >= $limits[$index]['high_warn']) {
				$states[$index] = t('WARNING (above limit of %s)', array(str_replace('%s', round_local($limits[$index]['high_warn'], $values[$key['what']]['decimals']), $values[$key['what']]['format'])));
				$state_class[$index] = 'warning';
			}
			else {
				$states[$index] = t('OK');
				$state_class[$index] = 'ok';
			}
		}
	}
	else {
		$states[$index] = 'UNKNOWN (no limits set)';
		$state_class[$index] = 'unknown';
	}
}

$formatted_limits = array();
foreach($keys as $index => $key) {
	$what = $key['what'];

	$current_values[$index]['formatted_value'] = str_replace('%s', round_local($current_values[$index]['value'], $values[$what]['decimals']), $values[$what]['format']);
	$current_values[$index]['formatted_timestamp'] = date($config["date_pattern.php.$lang"], $current_values[$index]['timestamp']);

	$min_values[$index]['formatted_value'] = str_replace('%s', round_local($min_values[$index]['value'], $values[$what]['decimals']), $values[$what]['format']);
	$min_values[$index]['formatted_timestamp'] = date($config["date_pattern.php.$lang"], $min_values[$index]['timestamp']);

	$max_values[$index]['formatted_value'] = str_replace('%s', round_local($max_values[$index]['value'], $values[$what]['decimals']), $values[$what]['format']);
	$max_values[$index]['formatted_timestamp'] = date($config["date_pattern.php.$lang"], $max_values[$index]['timestamp']);

	$avg_values[$index]['formatted_value'] = str_replace('%s', round_local($avg_values[$index]['value'], $values[$what]['decimals']), $values[$what]['format']);

	$formatted_limits[$index]['low_crit'] = str_replace('%s', round_local($limits[$index]['low_crit'], $values[$what]['decimals']), $values[$what]['format']);
	$formatted_limits[$index]['low_warn'] = str_replace('%s', round_local($limits[$index]['low_warn'], $values[$what]['decimals']), $values[$what]['format']);
	$formatted_limits[$index]['high_warn'] = str_replace('%s', round_local($limits[$index]['high_warn'], $values[$what]['decimals']), $values[$what]['format']);
	$formatted_limits[$index]['high_crit'] = str_replace('%s', round_local($limits[$index]['high_crit'], $values[$what]['decimals']), $values[$what]['format']);
}

$query = 'SELECT UNIX_TIMESTAMP(timestamp) timestamp FROM cronjob_executions ORDER BY id DESC LIMIT 0, 1';
$data = db_query($query);
if(count($data) == 0) {
	$last_cron_run = 'never';
}
else {
	$last_cron_run = date($config["date_pattern.php.$lang"], $data[0]['timestamp']);
}

$query = 'SELECT UNIX_TIMESTAMP(timestamp) timestamp FROM raw_data ORDER BY id DESC LIMIT 0, 1';
$data = db_query($query);
if(count($data) == 0) {
	$last_successful_cron_run = 'never';
}
else {
	$last_successful_cron_run = date($config["date_pattern.php.$lang"], $data[0]['timestamp']);
}

$rain = get_rain();

if(is_cli()) {
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
		echo "Average value (24 hours): " . $avg_values[$index]['formatted_value'] . "\n";
		echo "Current tendency: " . $tendencies[$index] . "\n";
		echo "\n\n";
	}

	echo "Precipitation (last 24 hours): $rain\n";

	// TODO battery status

	exit;
}

$query = 'SELECT id, url, row, height, width FROM munin_graphs ORDER BY id ASC';
$data = db_query($query);
$graphs = array();
$last_row = -1;
foreach($data as $line) {
	$url = $line['url'];
	$row = $line['row'];
	$id = $line['id'];

	$new_row = 0;
	if($last_row != $row) {
		$new_row = 1;
	}
	$graphs[] = array('url' => $url, 'new_row' => $new_row, 'id' => $id, 'height' => $line['height'], 'width' => $line['width']);
	$last_row = $row;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo t('Sensor status') ?></title>
<style type="text/css">
body { font-family: Verdana,Arial,Helvetica,sans-serif; }
body > div { margin: auto; }
td, th { white-space: nowrap; text-align: left; background-color: #e0e0e0; width: 800px; }
td.state_ok, td.state_warning, td.state_critical, td.state_unknown { text-align: center; }
td.state_ok { background-color: #00cc33; }
td.state_warning { background-color: #ffa500; }
td.state_critical { background-color: #ff3300; }
td.state_unknown { background-color: #e066ff; }
td.odd { background-color: #f1f1f1; }
div#lastrun { padding-bottom: 1em; }
div#top_text { padding-bottom: 2em; }
body > div > p { text-align: center; }
img#img_loading { visibility: hidden; }
a { text-decoration: none; }
</style>
<script type="text/javascript" src="jquery.min.js"></script>
<script type="text/javascript" src="date.js"></script>
<script type="text/javascript">
<!--
function start_refresh_timer() {
	window.setTimeout("do_refresh()", 30000);
	$('#img_loading').css('visibility', 'hidden');
}

function format_value(type, types, value) {
	var result = value;
	$.each(types['type'], function(index, item) {
		if(item['id'] == type) {
			var format = item['format'];
			result = format.replace(/%s/g, value);
		}
	});

	return result;
}

function do_refresh() {
	$('#img_loading').css('visibility', 'visible');

	$.ajax('api/?action=status&format=json&lang=<?php echo $lang ?>', {
			dataType: 'json',
			error: function(xhr, text_status, error_thrown) {
				start_refresh_timer();
			},
			success: function(data, text_status, xhr) {
				// 1. update status
				$.each(data['status']['value'], function(index, element) {
					$('#status_' + element['name']).html(new Date(element['value']).toString('<?php echo $config["date_pattern.javascript.$lang"] ?>'));
				});

				// 2. update values and states
				$.each(data['sensor'], function(index1, sensor) {
					$.each(sensor['values']['value'], function(index2, value) {
						var sensor_id = sensor['id'];
						var value_id = value['type'];
						var tr_id = '#data_' + sensor_id + '-' + value_id;

						var td_state = $(tr_id + ' td.state');
						$.each(value['measurement'], function(index3, measurement) {
							if(measurement['type'] == 'current') {
								td_state.html(measurement['state_description']);
								td_state.removeClass().addClass('state').addClass('state_' + measurement['state']);

								$(tr_id + ' td.tendency').html(measurement['localized_tendency']);
							}

							var value_data = '<strong>';
							value_data += format_value(value['type'], data['types'], measurement['localized_value']);
							value_data += '</strong>';
							if('timestamp' in measurement) {
								value_data += ' (';
								value_data += new Date(measurement['timestamp']).toString('<?php echo $config["date_pattern.javascript.$lang"] ?>');
								value_data += ')';
							}

							$(tr_id + ' td.' + measurement['type']).html(value_data);
						});
					});
				});

				// 3. update images
				$.each(data['images']['image'], function(index, element) {
					$('#image_' + element['id']).attr('src', element['url']);
					$('#image_' + element['id']).css('height', element['height'] + 'px');
					$('#image_' + element['id']).css('width', element['width'] + 'px');
				});				

				// 4. update rain
				$('#rain').html(data['rain']['value']);

				// 5. set timer
				start_refresh_timer();
			}
		});
}

$(document).ready(function() {
	start_refresh_timer();
});
// -->
</script>
</head>
<body>
<div>
	<h1><?php echo t('Current sensor state') ?></h1>
	<div id="lastrun">
		<?php echo t('Last cronjob run: ') ?><span id="status_last_cron_run"><?php echo $last_cron_run; ?></span><br />
		<?php echo t('Last successful cronjob run: ') ?><span id="status_last_successful_cron_run"><?php echo $last_successful_cron_run; ?></span><br />
		<?php echo t('Last page load: ') ?><span id="status_last_page_load"><?php echo date($config["date_pattern.php.$lang"]); ?></span><br />
		<img id="img_loading" src="ajax-loader.gif" alt="<?php echo t('Loading...') ?>" title="<?php echo t('Loading...') ?>" />
	</div>
	<?php if($config["top_text.$lang"] != ''): ?>
		<div id="top_text">
			<?php echo $config["top_text.$lang"]; ?>
		</div>
	<?php endif; ?>
	<table>
		<thead>
			<tr>
				<th><?php echo t('Sensor') ?></th>
				<th><?php echo t('Value') ?></th>
				<th><?php echo t('Current state') ?></th>
				<th><?php echo t('Current value') ?></th>
				<th><?php echo t('Maximum value (24 hours)') ?></th>
				<th><?php echo t('Minimum value (24 hours)') ?></th>
				<th><?php echo t('Average value (24 hours)') ?></th>
				<th><?php echo t('Current tendency') ?></th>
			</tr>
		</thead>
		<tbody>
			<?php $odd = 0; foreach($keys as $index => $key): $sensor = $key['sensor']; $what = $key['what']; $odd = 1-$odd; $oddstring = $odd ? 'odd' : 'even'; ?>
				<tr id="data_<?php echo $index ?>">
					<td class="<?php echo $oddstring ?>"><?php echo $sensors[$sensor]['description'] ?></td>
					<td class="<?php echo $oddstring ?>"><?php echo t($values[$what]['name']) ?></td>
					<td class="state state_<?php echo $state_class[$index] ?>"><?php echo $states[$index] ?></td>
					<td class="current <?php echo $oddstring ?>"><?php echo "<strong>" . $current_values[$index]['formatted_value'] . "</strong> (" . $current_values[$index]['formatted_timestamp'] . ")" ?></td>
					<td class="maximum <?php echo $oddstring ?>"><?php echo "<strong>" . $max_values[$index]['formatted_value'] . "</strong> (" . $max_values[$index]['formatted_timestamp'] . ")" ?></td>
					<td class="minimum <?php echo $oddstring ?>"><?php echo "<strong>" . $min_values[$index]['formatted_value'] . "</strong> (" . $min_values[$index]['formatted_timestamp'] . ")" ?></td>
					<td class="average <?php echo $oddstring ?>"><?php echo "<strong>" . $avg_values[$index]['formatted_value'] . "</strong>" ?></td>
					<td class="tendency <?php echo $oddstring ?>"><?php echo t($tendencies[$index]) ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<p style="text-align: left;">
		<?php echo t('Precipitation (last 24 hours): %s', array('')); ?> 
		<span id="rain"><?php echo $rain; ?></span>
	</p>
	<p>
		<?php foreach($graphs as $graph): ?>
			<?php if($graph['new_row']): ?><br /><?php endif; ?>
			<img src="<?php echo htmlentities($graph['url'], ENT_QUOTES, 'UTF-8') ?>" alt="" id="image_<?php echo $graph['id'] ?>" style="height: <?php echo $graph['height'] ?>px; width: <?php echo $graph['width'] ?>px;" />
		<?php endforeach; ?>
		<br />
	</p>
	<h3><?php echo t('Sensor limits') ?></h3>
	<table>
		<thead>
			<tr>
				<th><?php echo t('Sensor') ?></th>
				<th><?php echo t('Value') ?></th>
				<th><?php echo t('Critical') ?></th>
				<th><?php echo t('Warning') ?></th>
				<th><?php echo t('Warning') ?></th>
				<th><?php echo t('Critical') ?></th>
			</tr>
		</thead>
		<tbody>
			<?php $odd = 0; foreach($keys as $index => $key): $sensor = $key['sensor']; $what = $key['what']; $odd = 1-$odd; $oddstring = $odd ? ' class="odd"' : ''; ?>
				<tr>
					<td<?php echo $oddstring ?>><?php echo $sensors[$sensor]['description'] ?></td>
					<td<?php echo $oddstring ?>><?php echo t($values[$what]['name']) ?></td>
					<td<?php echo $oddstring ?>><?php echo $formatted_limits[$index]['low_crit'] ?></td>
					<td<?php echo $oddstring ?>><?php echo $formatted_limits[$index]['low_warn'] ?></td>
					<td<?php echo $oddstring ?>><?php echo $formatted_limits[$index]['high_warn'] ?></td>
					<td<?php echo $oddstring ?>><?php echo $formatted_limits[$index]['high_crit'] ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<a id="battery"></a>
	<h3><?php echo t('Battery changes') ?></h3>
	<table>
		<thead>
			<tr>
				<th><?php echo t('Sensor') ?></th>
				<th><?php echo t('Last battery change') ?></th>
				<th><?php echo t('Days') ?></th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php $odd = 0; foreach($keys as $index => $key): $sensor = $key['sensor']; $what = $key['what']; $odd = 1-$odd; $oddstring = $odd ? ' class="odd"' : ''; ?>
				<tr>
					<td<?php echo $oddstring ?>><?php echo $sensors[$sensor]['description'] ?></td>
					<td<?php echo $oddstring ?>><?php echo $sensors[$sensor]['battery_date'] ?></td>
					<td class="state_<?php echo $sensors[$sensor]['battery_state'] ?>"><?php echo $sensors[$sensor]['battery_days'] ?></td>
					<td<?php echo $oddstring ?>><a href="battery.php?id=<?php echo $sensor ?>"><?php echo t('Change battery') ?></a></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
</body>
</html>


