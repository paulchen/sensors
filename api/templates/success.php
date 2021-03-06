<?php 
echo '<?xml version="1.0"?>';
?>
<sensors>
	<?php if(isset($states)): ?>
		<states>
			<?php foreach($states as $state): ?>
				<state name="<?php echo htmlspecialchars($state['name'], ENT_COMPAT, 'UTF-8') ?>" color="<?php echo htmlspecialchars($state['color'], ENT_QUOTES, 'UTF-8') ?>" pos="<?php echo $state['pos'] ?>" ok="<?php echo $state['ok'] ?>" />
			<?php endforeach; ?>
		</states>
	<?php endif; ?>
	<?php if(isset($status)): ?>
		<status>
			<?php foreach($status as $key => $value): ?>
				<value name="<?php echo htmlspecialchars($key, ENT_COMPAT, 'UTF-8') ?>" value="<?php echo date(DateTime::W3C, $value) ?>" />
			<?php endforeach; ?>
		</status>
	<?php endif; ?>
	<?php if(isset($types)): ?>
		<types>
			<?php foreach($types as $type): ?>
				<type
					id="<?php echo $type['id'] ?>"
					name="<?php echo htmlspecialchars($type['name'], ENT_COMPAT, 'UTF-8') ?>"
					short_name="<?php echo htmlspecialchars($type['short_name'], ENT_COMPAT, 'UTF-8') ?>"
					format="<?php echo htmlspecialchars($type['format'], ENT_QUOTES, 'UTF-8') ?>" 
					<?php if($type['min'] != ''): ?> min="<?php echo $type['min'] ?>" <?php endif; ?>
					<?php if($type['max'] != ''): ?> max="<?php echo $type['max'] ?>" <?php endif; ?>
					decimals="<?php echo $type['decimals'] ?>"
					hide="<?php echo $type['hide'] ?>"
				/>
			<?php endforeach; ?>
		</types>
	<?php endif; ?>
	<?php foreach($sensors as $sensor): ?>
		<sensor id="<?php echo $sensor['id'] ?>" name="<?php echo htmlspecialchars($sensor['name'], ENT_COMPAT, 'UTF-8') ?>" hide="<?php echo $sensor['hide'] ?>">
			<?php if(isset($sensor_data) && isset($sensor_data[$sensor['id']])): ?>
				<values>
					<?php foreach($sensor_data[$sensor['id']]['values'] as $value): ?>
						<value type="<?php echo $value['type']; ?>">
							<?php foreach($value['measurements'] as $measurement): ?>
								<measurement value="<?php echo $measurement['value']; ?>" <?php if(isset($measurement['localized_value'])): ?>localized_value="<?php echo $measurement['localized_value'] ?>"<?php endif; ?> <?php if(isset($measurement['timestamp'])): ?>timestamp="<?php echo date(DateTime::W3C, $measurement['timestamp']) ?>" <?php endif; if(isset($measurement['state'])): ?>state = "<?php echo htmlspecialchars($measurement['state'], ENT_COMPAT, 'UTF-8') ?>" <?php endif; ?> <?php if(isset($measurement['state_description'])): ?>state_description = "<?php echo htmlspecialchars($measurement['state_description'], ENT_QUOTES, 'UTF-8') ?>" <?php endif; ?>type="<?php echo $measurement['type'] ?>" <?php if(isset($measurement['tendency'])): ?>tendency = "<?php echo $measurement['tendency'] ?>" <?php endif; ?> <?php if(isset($measurement['localized_tendency'])): ?>localized_tendency = "<?php echo $measurement['localized_tendency'] ?>" <?php endif; ?>/>
							<?php endforeach; ?>
						</value>
					<?php endforeach; ?>
				</values>
			<?php endif; ?>
		</sensor>
	<?php endforeach; ?>
	<?php if(isset($images)): ?>
		<images>
			<?php foreach($images as $image): ?>
				<image id="<?php echo $image['id'] ?>" url="<?php echo htmlspecialchars($image['url'], ENT_COMPAT, 'UTF-8') ?>" height="<?php echo $image['height'] ?>" width="<?php echo $image['width'] ?>" />
			<?php endforeach; ?>
		</images>
	<?php endif; ?>
	<?php if(isset($rain)): ?>
		<rain><?php echo htmlspecialchars($rain, ENT_COMPAT, 'UTF-8'); ?></rain>
	<?php endif; ?>
</sensors>

