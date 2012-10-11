<?php 
echo '<?xml version="1.0"?>';
?>
<sensors>
	<?php if(isset($types)): ?>
		<types>
			<?php foreach($types as $type): ?>
				<type id="<?php echo $type['id'] ?>" name="<?php echo htmlentities($type['name'], ENT_QUOTES, 'UTF-8') ?>" unit="<?php echo htmlentities($type['format'], ENT_QUOTES, 'UTF-8') ?>" 
					min="<?php echo $type['min'] ?>" max="<?php echo $type['max'] ?>" decimals="<?php echo $type['decimals'] ?>" />
			<?php endforeach; ?>
		</types>
	<?php endif; ?>
	<?php foreach($sensors as $sensor): ?>
		<sensor id="<?php echo $sensor['id'] ?>" name="<?php echo htmlentities($sensor['name'], ENT_QUOTES, 'UTF-8') ?>">
			<?php if(isset($sensor_data) && isset($sensor_data[$sensor['id']])): ?>
				<values>
					<?php foreach($sensor_data[$sensor['id']]['values'] as $value): ?>
						<value type="<?php echo $value['type']; ?>" format="<?php echo htmlentities($value['format'], ENT_QUOTES, 'UTF-8') ?>" description="<?php echo htmlentities($value['description'], ENT_QUOTES, 'UTF-8') ?>">
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

