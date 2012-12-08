<?php 
echo '<?xml version="1.0"?>';
?>
<sensors>
	<?php if(isset($states)): ?>
		<states>
			<?php foreach($states as $state): ?>
			<state name="<?php echo htmlentities($state['name'], ENT_QUOTES, 'UTF-8') ?>" color="<?php echo htmlentities($state['color'], ENT_QUOTES, 'UTF-8') ?>" pos="<?php echo $state['pos'] ?>" ok="<?php echo $state['ok'] ?>" />
			<?php endforeach; ?>
		</states>
	<?php endif; ?>
	<?php if(isset($types)): ?>
		<types>
			<?php foreach($types as $type): ?>
				<type id="<?php echo $type['id'] ?>" name="<?php echo htmlentities($type['name'], ENT_QUOTES, 'UTF-8') ?>" format="<?php echo htmlentities($type['format'], ENT_QUOTES, 'UTF-8') ?>" 
					<?php if($type['min'] != ''): ?> min="<?php echo $type['min'] ?>" <?php endif; ?>
					<?php if($type['max'] != ''): ?> max="<?php echo $type['max'] ?>" <?php endif; ?>
					decimals="<?php echo $type['decimals'] ?>" />
			<?php endforeach; ?>
		</types>
	<?php endif; ?>
	<?php foreach($sensors as $sensor): ?>
		<sensor id="<?php echo $sensor['id'] ?>" name="<?php echo htmlentities($sensor['name'], ENT_QUOTES, 'UTF-8') ?>">
			<?php if(isset($sensor_data) && isset($sensor_data[$sensor['id']])): ?>
				<values>
					<?php foreach($sensor_data[$sensor['id']]['values'] as $value): ?>
						<value type="<?php echo $value['type']; ?>" description="<?php echo htmlentities($value['description'], ENT_QUOTES, 'UTF-8') ?>">
							<?php foreach($value['measurements'] as $measurement): ?>
								<measurement value="<?php echo $measurement['value']; ?>" timestamp="<?php echo date(DateTime::W3C, $measurement['timestamp']) ?>" state = "<?php echo htmlentities($measurement['state'], ENT_QUOTES, 'UTF-8') ?>"/>
							<?php endforeach; ?>
						</value>
					<?php endforeach; ?>
				</values>
			<?php endif; ?>
		</sensor>
	<?php endforeach; ?>
</sensors>

