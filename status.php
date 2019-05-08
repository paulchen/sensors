<?php

chdir(dirname(__FILE__));

$http_auth = true;
require_once('common.php');

$show_hidden = 0;
if(isset($_REQUEST['hidden']) && $_REQUEST['hidden'] == '1') {
	$show_hidden = 1;
}

$query = 'SELECT pos, id, hide FROM sensors';
$data = db_query($query);
$position = array();
$hidden_sensors = array();
foreach($data as $row) {
	$position[$row['id']] = $row['pos'];
	if($row['hide'] == 1 && !$show_hidden) {
		$hidden_sensors[] = $row['id'];
	}
}

$query = 'SELECT id, name, short AS short_name, format, decimals, hide FROM sensor_values';
$data = db_query($query);
$values = array();
foreach($data as $row) {
	$values[$row['id']] = $row;
}

$requested_locations = isset($_REQUEST['locations']) ? $_REQUEST['locations'] : array();
$requested_groups = isset($_REQUEST['groups']) ? $_REQUEST['groups'] : array();
$requested_sensors = isset($_REQUEST['sensors']) ? $_REQUEST['sensors'] : array();
$nothing_requested = (count($requested_locations) + count($requested_groups) + count($requested_sensors) == 0);

$query = 'SELECT s.id sensor_id, COALESCE(s.display_name, s.description) sensor_name, s.hide sensor_hide,
		g.id group_id, g.name group_name, g.visible group_visible,
		l.id location_id, l.name location_name, l.visible location_visible
	FROM sensors s
		JOIN sensor_group sg ON (s.id = sg.sensor)
		JOIN `group` g ON (sg.group = g.id)
		JOIN `location` l ON (g.location = l.id)
		JOIN account_location al ON (l.id = al.location)
	WHERE al.account = ?
	ORDER BY g.pos ASC, l.pos ASC, s.pos ASC';
$data = db_query($query, array($user_id));
$locations = array();
$selected_locations = array();
$selected_groups = array();
$selected_sensors = array();
$all_locations = array();
$all_groups = array();
$all_sensors = array();
foreach($data as $row) {
	if(!$show_hidden && $row['sensor_hide'] == 1) {
		continue;
	}

	$all_locations[] = $row['location_id'];
	$all_groups[] = $row['group_id'];
	$all_sensors[] = $row['sensor_id'];

	if(!isset($locations[$row['location_id']])) {
		$locations[$row['location_id']] = array();
	}
	$locations[$row['location_id']]['name'] = $row['location_name'];
	$locations[$row['location_id']]['visible'] = $row['location_visible'];

	if(!isset($locations[$row['location_id']]['groups'])) {
		$locations[$row['location_id']]['groups'] = array();
	}
	if(!isset($locations[$row['location_id']]['groups'][$row['group_id']])) {
		$locations[$row['location_id']]['groups'][$row['group_id']] = array();
	}
	$locations[$row['location_id']]['groups'][$row['group_id']]['name'] = $row['group_name'];
	$locations[$row['location_id']]['groups'][$row['group_id']]['visible'] = $row['group_visible'];

	if(!isset($locations[$row['location_id']]['groups'][$row['group_id']]['sensors'])) {
		$locations[$row['location_id']]['groups'][$row['group_id']]['sensors'] = array();
	}
	$locations[$row['location_id']]['groups'][$row['group_id']]['sensors'][$row['sensor_id']] = $row['sensor_name'];

	if($nothing_requested && $row['location_visible']) {
		$selected_locations[] = $row['location_id'];
	}
	else if(in_array($row['location_id'], $requested_locations)) {
		$selected_locations[] = $row['location_id'];
	}

	if($nothing_requested && $row['location_visible'] && $row['group_visible']) {
		$selected_groups[] = $row['group_id'];
		$selected_sensors[] = $row['sensor_id'];
	}
	else if(in_array($row['sensor_id'], $requested_sensors)) {
		$selected_groups[] = $row['group_id'];
		$selected_sensors[] = $row['sensor_id'];
		$selected_locations[] = $row['location_id'];
	}
	else if(in_array($row['group_id'], $requested_groups)) {
		$selected_groups[] = $row['group_id'];
		$selected_locations[] = $row['location_id'];
	}
}

if(count($selected_sensors) == 0) {
	$selected_locations = $all_locations;
	$selected_groups = $all_groups;
	$selected_sensors = $all_sensors;
}

$query = 'SELECT sensor, what, UNIX_TIMESTAMP(timestamp) timestamp, value FROM sensor_cache WHERE timestamp > ? ORDER BY timestamp ASC';
$start_timestamp = date('Y-m-d H:i', time()-86400);
$stmt = db_query_resultset($query, array($start_timestamp));

$first_values = array();
$max_values = array();
$min_values = array();
$avg_values = array();
$current_values = array();
$keys = array();

while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
	$sensor = $row['sensor'];
	if(in_array($sensor, $hidden_sensors)) {
		continue;
	}

	$what = $row['what'];
	if(!$show_hidden && $values[$what]['hide'] == '1') {
		continue;
	}

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
db_stmt_close($stmt);

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
		$tendencies[$index] = 'stabil';
	}
	else if($old > $new) {
		$tendencies[$index] = 'fallend';
	}
	else {
		$tendencies[$index] = 'steigend';
	}

	$avg_values[$index]['value'] /= $avg_values[$index]['count'];
}

$query = 'SELECT s.id id, s.sensor sensor, s.type type, COALESCE(s.display_name, s.description) description
       		FROM sensors s';
$data = db_query($query);
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

		$sensors[$id]['battery_date'] = date($config['date_pattern.php'], $timestamp);
		$battery_days = floor((time()-$timestamp)/86400);
		$sensors[$id]['battery_days'] = "$battery_days Tag(e)";
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
			$states[$index] = 'UNBEKANNT (letzter Wert ist zu alt)';
			$state_class[$index] = 'unknown';
		}
		else {
			$value = $current_values[$index]['value'];
			if($value < $limits[$index]['low_crit']) {
				$states[$index] = sprintf('KRITISCH (unter %s)', str_replace('%s', round_local($limits[$index]['low_crit'], $values[$key['what']]['decimals']), $values[$key['what']]['format']));
				$state_class[$index] = 'critical';
			}
			else if($value < $limits[$index]['low_warn']) {
				$states[$index] = sprintf('WARNUNG (unter %s)', str_replace('%s', round_local($limits[$index]['low_warn'], $values[$key['what']]['decimals']), $values[$key['what']]['format']));
				$state_class[$index] = 'warning';
			}
			else if($value > $limits[$index]['high_crit']) {
				$states[$index] = sprintf('KRITISCH (über %s)', str_replace('%s', round_local($limits[$index]['high_crit'], $values[$key['what']]['decimals']), $values[$key['what']]['format']));
				$state_class[$index] = 'critical';
			}
			else if($value > $limits[$index]['high_warn']) {
				$states[$index] = sprintf('WARNUNG (über %s)', str_replace('%s', round_local($limits[$index]['high_warn'], $values[$key['what']]['decimals']), $values[$key['what']]['format']));
				$state_class[$index] = 'warning';
			}
			else {
				$states[$index] = 'OK';
				$state_class[$index] = 'ok';
			}
		}
	}
	else {
		$states[$index] = 'UNBEKANNT (keine Limits gesetzt)';
		$state_class[$index] = 'unknown';
	}
}

$formatted_limits = array();
foreach($keys as $index => $key) {
	$what = $key['what'];

	$current_values[$index]['formatted_value'] = str_replace('%s', round_local($current_values[$index]['value'], $values[$what]['decimals']), $values[$what]['format']);
	$current_values[$index]['formatted_timestamp'] = date($config['date_pattern.php'], $current_values[$index]['timestamp']);

	$min_values[$index]['formatted_value'] = str_replace('%s', round_local($min_values[$index]['value'], $values[$what]['decimals']), $values[$what]['format']);
	$min_values[$index]['formatted_timestamp'] = date($config['date_pattern.php'], $min_values[$index]['timestamp']);

	$max_values[$index]['formatted_value'] = str_replace('%s', round_local($max_values[$index]['value'], $values[$what]['decimals']), $values[$what]['format']);
	$max_values[$index]['formatted_timestamp'] = date($config['date_pattern.php'], $max_values[$index]['timestamp']);

	$avg_values[$index]['formatted_value'] = str_replace('%s', round_local($avg_values[$index]['value'], $values[$what]['decimals']), $values[$what]['format']);

	if(isset($limits[$index])) {
		$formatted_limits[$index]['low_crit'] = str_replace('%s', round_local($limits[$index]['low_crit'], $values[$what]['decimals']), $values[$what]['format']);
		$formatted_limits[$index]['low_warn'] = str_replace('%s', round_local($limits[$index]['low_warn'], $values[$what]['decimals']), $values[$what]['format']);
		$formatted_limits[$index]['high_warn'] = str_replace('%s', round_local($limits[$index]['high_warn'], $values[$what]['decimals']), $values[$what]['format']);
		$formatted_limits[$index]['high_crit'] = str_replace('%s', round_local($limits[$index]['high_crit'], $values[$what]['decimals']), $values[$what]['format']);
	}
	else {
		$formatted_limits[$index]['low_crit'] = 'Kein Limit festgelegt';
		$formatted_limits[$index]['low_warn'] = 'Kein Limit festgelegt';
		$formatted_limits[$index]['high_warn'] = 'Kein Limit festgelegt';
		$formatted_limits[$index]['high_crit'] = 'Kein Limit festgelegt';
	}
}

$timestamp = get_last_cron_run();
if($timestamp == '') {
	$last_cron_run = 'nie';
}
else {
	$last_cron_run = date($config['date_pattern.php'], $timestamp);
}

$rain = get_rain();

if(is_cli()) {
	echo "Last cronjob run: $last_cron_run\n";
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

$query = 'SELECT g.id, url, row, height, width, gg.`group` FROM munin_graphs g JOIN graph_group gg ON (g.id = gg.graph) ORDER BY id ASC';
$data = db_query($query);
$graphs = array();
$last_row = -1;
foreach($data as $line) {
	if(!in_array($line['group'], $selected_groups)) {
		continue;
	}

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

function print_ice() {
	echo ' <span style="color: blue; font-family: emoji;">❄️</span>';
}

function print_value($item, $what) {
	echo "<strong>" . $item['formatted_value'] . "</strong>";
	if($item['value'] < 0 && $what['short_name'] == 'temp') {
		print_ice();
	}
	if(isset($item['formatted_timestamp'])) {
		echo " (" . $item['formatted_timestamp'] . ")";
	}
}

function print_location_ice($item, $what) {
	if($item['value'] < 0 && $what['short_name'] == 'temp') {
		print_ice();
	}
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<!-- memory footprint: <?php echo memory_get_peak_usage(); ?> -->
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Sensorstatus</title>
<style type="text/css">
body { font-family: Verdana,Arial,Helvetica,sans-serif; }
body > div { margin: auto; }
td, th { white-space: nowrap; text-align: left; background-color: #e0e0e0; width: 800px; }
tr.spacer td { height: 5px; background-color: transparent; }
td.state_ok, td.state_warning, td.state_critical, td.state_unknown { text-align: center; }
td.state_ok { background-color: #00cc33; }
td.state_warning { background-color: #ffa500; }
td.state_critical { background-color: #ff3300; }
td.state_unknown { background-color: #e066ff; }
td.odd { background-color: #f1f1f1; }
div#lastrun { padding-bottom: 1em; }
div#top_text { padding-bottom: 1em; }
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

function find_type(types, id) {
	var result = null;
	$.each(types['type'], function(index, item) {
		if(item['id'] == id) {
			result = item;
		}
	});

	return result;
}

function format_value(type, types, value) {
	var item = find_type(types, type);
	var format = item['format'];
	return format.replace(/%s/g, value);
}

function do_refresh() {
	$('#img_loading').css('visibility', 'visible');

	$.ajax('api/?action=status&format=json', {
			dataType: 'json',
			error: function(xhr, text_status, error_thrown) {
				start_refresh_timer();
			},
			success: function(data, text_status, xhr) {
				// 1. update status
				$.each(data['status']['value'], function(index, element) {
					$('#status_' + element['name']).html(new Date(element['value']).toString('<?php echo $config['date_pattern.javascript'] ?>'));
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
							if(measurement['value'] < 0 && find_type(data['types'], value['type'])['short_name'] == 'temp') {
								value_data += '<?php print_ice() ?>';
							}
							if('timestamp' in measurement) {
								value_data += ' (';
								value_data += new Date(measurement['timestamp']).toString('<?php echo $config['date_pattern.javascript'] ?>');
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

function enable_parents(node) {
	parent_input = node.parent().parent().children('input');
	
	if(parent_input.length) {
		parent_input.prop('checked', true);
		enable_parents(parent_input);
	}
}

$(document).ready(function() {
	start_refresh_timer();

	$('#filter_link').click(function() {
		$('#fieldset_filter').toggle('slow');
	});

	$('.checkbox_location').change(function() {
		div_id = $(this).attr('id').replace('_', '_div_');
		$('#' + div_id + ' input:checkbox').prop('checked', $(this).is(':checked'));
	});

	$('.checkbox_group').change(function() {
		div_id = $(this).attr('id').replace('_', '_div_');
		$('#' + div_id + ' input:checkbox').prop('checked', $(this).is(':checked'));
	});

	$('#fieldset_filter input:checkbox').change(function() {
		enable_parents($(this));
	});
});
// -->
</script>
</head>
<body>
<div>
	<h1>Aktueller Sensorstatus</h1>
	<div id="lastrun">
		Letzte Datenaktualisierung: <span id="status_last_cron_run"><?php echo $last_cron_run; ?></span><br />
		Letzte Seitenaktualisierung: <span id="status_last_page_load"><?php echo date($config['date_pattern.php']); ?></span><br />
		<img id="img_loading" src="ajax-loader.gif" alt="Lade..." title="Lade..." />
	</div>
	<?php if($config['top_text'] != ''): ?>
		<div id="top_text">
			<?php echo $config['top_text']; ?>
		</div>
	<?php endif; ?>
	<div>
	<div style="padding-bottom: 1em;">
	<a href="bestenliste.php">Ewige Bestenliste</a>
	</div>
	<?php if(!$nothing_requested or (isset($_REQUEST['filter']) && $_REQUEST['filter'] == '1')): ?>
	<div style="padding-bottom: 1em;">
	<a href="#" id="filter_link">Filter</a>
	</div>
	<?php endif; ?>
	<div id="fieldset_filter" style="display: none;">
	<fieldset style="margin-bottom: 1.5em;">
	<legend>Filter</legend>
	<form method="get">
	<?php foreach($locations as $location_id => $location): ?>
		<div style="padding-bottom: 1em;">
		<input class="checkbox_location" id="location_<?php echo $location_id; ?>" value="<?php echo $location_id ?>" type="checkbox" name="locations[]" <?php if(in_array($location_id, $selected_locations)): ?> checked="checked"<?php endif; ?> />
		<label for="location_<?php echo $location_id; ?>"><?php echo $location['name'] ?></label>

		<div style="padding-left: 10px;" id="location_div_<?php echo $location_id ?>">
			<?php foreach($location['groups'] as $group_id => $group): ?>
				<input class="checkbox_group" id="group_<?php echo $group_id; ?>" value="<?php echo $group_id ?>" type="checkbox" name="groups[]" <?php if(in_array($group_id, $selected_groups)): ?> checked="checked"<?php endif; ?> />
				<label for="group_<?php echo $group_id; ?>"><?php echo $group['name'] ?></label>
				
				<div style="padding-left: 10px;" id="group_div_<?php echo $group_id ?>">
					<?php foreach($group['sensors'] as $sensor_id => $sensor): ?>
						<input id="sensor_<?php echo $sensor_id; ?>" value="<?php echo $sensor_id ?>" type="checkbox" name="sensors[]" <?php if(in_array($sensor_id, $selected_sensors)): ?> checked="checked"<?php endif; ?> />
						<label for="sensor_<?php echo $sensor_id; ?>"><?php echo $sensor ?></label>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</div>
		</div>
	<?php endforeach; ?>
	<input type="submit" value="Filtern" />
	</form></fieldset>
	</div></div>
	<table>
		<thead>
			<tr>
				<th>Sensor</th>
				<th>Wert</th>
				<th>Aktueller Zustand</th>
				<th>Aktueller Wert</th>
				<th>Höchstwert (24 Stunden)</th>
				<th>Tiefstwert (24 Stunden)</th>
				<th>Mittelwert (24 Stunden)</th>
				<th>Aktuelle Tendenz</th>
			</tr>
		</thead>
		<tbody>
			<?php $odd = 0; foreach($keys as $index => $key): if(!in_array($key['sensor'], $selected_sensors)) continue; $sensor = $key['sensor']; $what = $key['what']; $odd = 1-$odd; $oddstring = $odd ? 'odd' : 'even'; ?>
				<?php if(!isset($previous_sensor) || $sensor != $previous_sensor): ?>
					<tr class="spacer">
						<td></td>
					</tr>
				<?php endif ?>
				<tr id="data_<?php echo $index ?>" >
					<td class="<?php echo $oddstring ?>"><?php echo $sensors[$sensor]['description']; print_location_ice($min_values[$index], $values[$what]) ?></td>
					<td class="<?php echo $oddstring ?>"><?php echo $values[$what]['name'] ?></td>
					<td class="state state_<?php echo $state_class[$index] ?>"><?php echo $states[$index] ?></td>
					<td class="current <?php echo $oddstring ?>"><?php print_value($current_values[$index], $values[$what]) ?></td>
					<td class="maximum <?php echo $oddstring ?>"><?php print_value($max_values[$index], $values[$what]) ?></td>
					<td class="minimum <?php echo $oddstring ?>"><?php print_value($min_values[$index], $values[$what]) ?></td>
					<td class="average <?php echo $oddstring ?>"><?php print_value($avg_values[$index], $values[$what]) ?></td>
					<td class="tendency <?php echo $oddstring ?>"><?php echo $tendencies[$index] ?></td>
				</tr>
			<?php $previous_sensor = $sensor; endforeach; ?>
		</tbody>
	</table>
	<p style="text-align: left;">
		Niederschlag (letzte 24 Stunden): 
		<span id="rain"><?php echo $rain; ?></span>
	</p>
	<p>
		<span style="white-space: nowrap;">
		<?php foreach($graphs as $graph): ?>
			<?php if($graph['new_row']): ?></span><br /><span style="white-space: nowrap;"><?php endif; ?>
			<img src="<?php echo htmlentities($graph['url'], ENT_QUOTES, 'UTF-8') ?>" alt="" id="image_<?php echo $graph['id'] ?>" style="height: <?php echo $graph['height'] ?>px; width: <?php echo $graph['width'] ?>px;" />
		<?php endforeach; ?>
		</span><br />
	</p>
	<h3>Sensorlimits</h3>
	<table>
		<thead>
			<tr>
				<th>Sensor</th>
				<th>Wert</th>
				<th>Kritisch</th>
				<th>Warnung</th>
				<th>Warnung</th>
				<th>Kritisch</th>
			</tr>
		</thead>
		<tbody>
			<?php $odd = 0; foreach($keys as $index => $key): $sensor = $key['sensor']; $what = $key['what']; $odd = 1-$odd; $oddstring = $odd ? ' class="odd"' : ''; ?>
				<tr>
					<td<?php echo $oddstring ?>><?php echo $sensors[$sensor]['description'] ?></td>
					<td<?php echo $oddstring ?>><?php echo $values[$what]['name'] ?></td>
					<td<?php echo $oddstring ?>><?php echo $formatted_limits[$index]['low_crit'] ?></td>
					<td<?php echo $oddstring ?>><?php echo $formatted_limits[$index]['low_warn'] ?></td>
					<td<?php echo $oddstring ?>><?php echo $formatted_limits[$index]['high_warn'] ?></td>
					<td<?php echo $oddstring ?>><?php echo $formatted_limits[$index]['high_crit'] ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<a id="battery"></a>
	<h3>Batteriewechsel</h3>
	<table>
		<thead>
			<tr>
				<th>Sensor</th>
				<th>Letzter Batteriewechsel</th>
				<th>Tage</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php $previous_id = null; $odd = 0; foreach($keys as $index => $key): $sensor = $key['sensor']; if($sensors[$sensor]['battery_days'] == null) continue; if($sensors[$sensor]['id'] == $previous_id) continue; $previous_id = $sensors[$sensor]['id']; $what = $key['what']; $odd = 1-$odd; $oddstring = $odd ? ' class="odd"' : ''; ?>
				<tr>
					<td<?php echo $oddstring ?>><?php echo $sensors[$sensor]['description'] ?></td>
					<td<?php echo $oddstring ?>><?php echo $sensors[$sensor]['battery_date'] ?></td>
					<td class="state_<?php echo $sensors[$sensor]['battery_state'] ?>"><?php echo $sensors[$sensor]['battery_days'] ?></td>
					<td<?php echo $oddstring ?>><a href="battery.php?id=<?php echo $sensor ?>">Batterie wechseln</a></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
</body>
</html>


