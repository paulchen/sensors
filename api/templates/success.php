<?php 
echo '<?xml version="1.0"?>';
?>
<sensors>
	<?php foreach($sensors as $sensor): ?>
		<sensor id="<?php echo $sensor['id'] ?>" name="<?php echo htmlentities($sensor['name'], ENT_QUOTES, 'UTF-8') ?>">
			<?php if(isset($sensor_data) && isset($sensor_data[$sensor['id']])): ?>
				<values>
					<?php foreach($sensor_data[$sensor['id']]['values'] as $value): ?>
						<value type="<?php echo $value['type']; ?>">
							<?php foreach($value['measurements'] as $measurement): ?>
								<measurement value="<?php echo $measurement['value']; ?>" timestamp="<?php echo date(DateTime::W3C, $measurement['timestamp']) ?>" />
							<?php endforeach; ?>
						</value>
					<?php endforeach; ?>
				</values>
			<?php endif; ?>
		</sensor>
	<?php endforeach; ?>
</sensors>

