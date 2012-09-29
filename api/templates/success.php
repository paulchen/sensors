<?php 
echo '<?xml version="1.0"?>';
?>
<sensors>
	<?php foreach($sensors as $sensor): ?>
		<sensor id="<?php echo $sensor['id'] ?>" name="<?php echo $sensor['name'] ?>">
			<!-- TODO -->
		</sensor>
	<?php endforeach; ?>
</sensors>

