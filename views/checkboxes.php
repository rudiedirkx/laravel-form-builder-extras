<?php if ($showLabel && $showField): ?>
	<?php if ($options['wrapper'] !== false): ?>
		<div <?php echo $options['wrapperAttrs'] ?> >
	<?php endif; ?>
<?php endif; ?>

<?php if ($showLabel && $options['label'] !== false && $options['label_show']): ?>
	<?php echo Form::customLabel($name, $options['label'], $options['label_attr']) ?>
<?php endif; ?>

<?php if ($showField): ?>
	<div class="options-wrapper">
		<?php foreach ($options['choices'] as $value => $label):
			$id = "for-$name-$value";
			?>
			<div class="form-option">
				<?php echo Form::checkbox($name . '[]', $value, in_array($value, (array) $options['selected']), ['id' => $id]); ?>
				<?php echo Form::label($id, $label); ?>
			</div>
		<?php endforeach; ?>
	</div>

	<?php include helpBlockPath(); ?>
<?php endif; ?>

<?php include errorBlockPath(); ?>

<?php if ($showLabel && $showField): ?>
	<?php if ($options['wrapper'] !== false): ?>
		</div>
	<?php endif; ?>
<?php endif; ?>
