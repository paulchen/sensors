<?php

chdir(dirname(__FILE__));

$http_auth = true;
require_once('common.php');

$sql = 'SELECT s.id sensor_id, COALESCE(s.display_name, s.description) description, sv.name, UNIX_TIMESTAMP(alt.min_timestamp) min_timestamp, alt.min, UNIX_TIMESTAMP(alt.max_timestamp) max_timestamp, alt.max, sv.format, sv.decimals
		FROM all_time_list alt
			JOIN sensors s ON (alt.sensor = s.id)
			JOIN sensor_group sg ON (s.id = sg.sensor)
			JOIN `group` g ON (sg.group = g.id)
			JOIN account_location al ON (g.location = al.location)
			JOIN sensor_values sv ON (alt.what = sv.id)
		WHERE al.account = ?
			AND s.hide = 0
			AND sv.hide = 0
		ORDER BY s.pos ASC, sv.id ASC';
$data = db_query($sql, array($user_id));

foreach($data as &$row) {
	$row['formatted_min'] = str_replace('%s', round_local($row['min'], $row['decimals']), $row['format']);
	$row['formatted_max'] = str_replace('%s', round_local($row['max'], $row['decimals']), $row['format']);
	
	$row['formatted_min_timestamp'] = date('d.m.Y H:i', $row['min_timestamp']);
	$row['formatted_max_timestamp'] = date('d.m.Y H:i', $row['max_timestamp']);
}
unset($row);

$update_file = 'minmax.last';
if(file_exists($update_file)) {
	$stat_data = stat($update_file);
	$last_update = date('d.m.Y H:i', $stat_data['mtime']);
}
else {
	$last_update = 'nie';
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
</head>
<body>
<div>
	<h1>Ewige Bestenliste</h1>
	<div style="padding-bottom: 1em;">
		<a href=".">Status</a>
	</div>
	<div style="padding-bottom: 2em;">
		Zuletzt aktualisiert: <?php echo $last_update ?>
	</div>
	<table>
		<thead>
			<tr>
				<th>Sensor</th>
				<th>Wert</th>
				<th>Höchstwert</th>
				<th>Tiefstwert</th>
			</tr>
		</thead>
		<tbody>
			<?php $odd = 0; foreach($data as $row): $odd = 1-$odd; $oddstring = $odd ? 'odd' : 'even'; ?>
				<?php if(!isset($previous_sensor) || $row['sensor_id'] != $previous_sensor): ?>
					<tr class="spacer">
						<td></td>
					</tr>
				<?php endif ?>
				<tr id="data_<?php echo $index ?>" >
					<td class="<?php echo $oddstring ?>"><?php echo $row['description'] ?></td>
					<td class="<?php echo $oddstring ?>"><?php echo $row['name'] ?></td>
					<td class="maximum <?php echo $oddstring ?>"><?php echo "<strong>" . $row['formatted_max'] . "</strong> (" . $row['formatted_max_timestamp'] . ")" ?></td>
					<td class="minimum <?php echo $oddstring ?>"><?php echo "<strong>" . $row['formatted_min'] . "</strong> (" . $row['formatted_min_timestamp'] . ")" ?></td>
				</tr>
			<?php $previous_sensor = $row['sensor_id']; endforeach; ?>
		</tbody>
	</table>
</div>
</body>
</html>


