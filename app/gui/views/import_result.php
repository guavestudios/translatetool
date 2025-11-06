<h1>
	Import Results
</h1>

<?php if ($success): ?>
	<p>The import was successful!</p>
<?php else: ?>
	<p>There were some issues with the import:</p>
	
	<?php if (isset($conflicts['criticalErrors']) && !empty($conflicts['criticalErrors'])): ?>
		<?php foreach ($conflicts['criticalErrors'] as $errorType => $errors): ?>
			<?php if (!empty($errors)): ?>
				<h3><?php echo htmlspecialchars(ucfirst(str_replace('Errors', ' Errors', $errorType))); ?></h3>
				<ul>
					<?php foreach ($errors as $error): ?>
						<li><?php echo htmlspecialchars(is_array($error) ? json_encode($error) : $error); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		<?php endforeach; ?>
	<?php endif; ?>
	
	<?php if (isset($conflicts['warnings']) && !empty($conflicts['warnings'])): ?>
		<h3>Warnings</h3>
		<?php foreach ($conflicts['warnings'] as $warningType => $warnings): ?>
			<?php if (!empty($warnings)): ?>
				<h4><?php echo htmlspecialchars(ucfirst(str_replace('Warning', ' Warning', $warningType))); ?></h4>
				<ul>
					<?php foreach ($warnings as $warning): ?>
						<li><?php echo htmlspecialchars(is_array($warning) ? json_encode($warning) : $warning); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		<?php endforeach; ?>
	<?php endif; ?>
<?php endif; ?>