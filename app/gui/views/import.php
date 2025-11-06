<div class="import-container">
	<h1>Import CSV</h1>

	<?php if (!$uploaded): ?>
		<div class="upload-file-section">
			<!-- Step 1: File Upload Form -->
			<form method="post" enctype="multipart/form-data">
				<label for="file-upload" class="custom-file-upload btn btn--primary">Choose CSV File</label>
				<input id="file-upload" type="file" name="csv" accept=".csv">
				<div id="file-name-display" class="selected-file"></div>
				<input class="btn" id="upload-button" disabled type="submit" value="Upload & Validate">
			</form>
		</div>

		<!-- Format Documentation -->

		<section class="card">
			<h2>Format</h2>
			<p>The CSV file should be formatted as follows:</p>
			<h3>Table</h3>
			<table width="50%" border="1">
				<tr>
					<td>key</td>
					<?php foreach (config::get('languages') as $language): ?>
						<td><?= htmlspecialchars($language) ?> (optional)</td>
					<?php endforeach; ?>
				</tr>
				<tr>
					<td>dot.delimited.key</td>
					<?php foreach (config::get('languages') as $language): ?>
						<td><?= htmlspecialchars(strtoupper($language)) ?> string</td>
					<?php endforeach; ?>
				</tr>
			</table>

			<h3>Plain Text</h3>
			<p style="font-family: monospace;">"key"<?php foreach (config::get('languages') as $language): ?>;"<?= htmlspecialchars($language) ?> (optional)"<?php endforeach; ?> <br>

				"dot.delimited.key"<?php foreach (config::get('languages') as $language): ?>;"<?= htmlspecialchars(strtoupper($language)) ?> string"<?php endforeach; ?></p>
		</section>

	<?php else: ?>
		<!-- Step 2: Show validation results and confirmation -->
		<?php if (!empty($errors)): ?>
			<h2>Validation Errors</h2>
			<ul style="color: red;">
				<?php foreach ($errors as $error): ?>
					<li><?= htmlspecialchars($error) ?></li>
				<?php endforeach; ?>
			</ul>
			<p><a href="<?= config::get('base') ?>import">Please fix the errors and upload the file again, you can do it!</a></p>

		<?php else: ?>
			<div>
				<!-- <h3>Value Comparison Results</h3> -->
				<?php if (!empty($csvData)): ?>
					<!-- Summary -->
					<?php if (isset($csvData['summary'])): ?>
						<div style="margin-bottom: 20px;">
							<strong>Summary:</strong>
							New: <?= $csvData['summary']['new_count'] ?? 0 ?> |
							Changed: <?= $csvData['summary']['changed_count'] ?? 0 ?> |
							Unchanged: <?= $csvData['summary']['unchanged_count'] ?? 0 ?>
						</div>
					<?php endif; ?>

					<?php
					// Group all entries by folder
					$folderGroups = [];
					$allEntries = [];

					if (!empty($csvData['new'])) {
						foreach ($csvData['new'] as $entry) {
							$entry['status'] = 'new';
							$allEntries[] = $entry;
						}
					}
					if (!empty($csvData['changed'])) {
						foreach ($csvData['changed'] as $entry) {
							$entry['status'] = 'changed';
							$allEntries[] = $entry;
						}
					}

					foreach ($allEntries as $entry) {
						$fullKey = $entry['key'] ?? '';
						$keyParts = explode('.', $fullKey);
						$simpleKey = array_pop($keyParts);
						$folderPath = implode('/', $keyParts);
						$entry['simple_key'] = $simpleKey;

						if (!isset($folderGroups[$folderPath])) {
							$folderGroups[$folderPath] = [];
						}
						if (!isset($folderGroups[$folderPath][$simpleKey])) {
							$folderGroups[$folderPath][$simpleKey] = [];
						}
						$folderGroups[$folderPath][$simpleKey][] = $entry;
					}
					?>

					<div class="import-preview-grid">
						<div class="grid__header grid__row">
							<div><strong>Key</strong></div>
							<div><strong>Lang</strong></div>
							<div><strong>Status</strong></div>
							<div><strong>New Value</strong></div>
							<div><strong>Old Value</strong></div>
						</div>

						<?php foreach ($folderGroups as $folder => $keyGroups): ?>
							<div class="grid__row grid__row--folder">
								<h4><?= htmlspecialchars($folder ?: 'Root') ?></h4>
							</div>
							<?php
							$keyIndex = 0;
							foreach ($keyGroups as $key => $entries):
								$keyIndex++;
							?>
								<?php foreach ($entries as $index => $entry): ?>
									<div class="grid__row grid__row--entry <?= $keyIndex % 2 === 0 ? 'grid__row--even' : 'grid__row--odd' ?>">
										<div><?= $index === 0 ? htmlspecialchars($key) : '' ?></div>
										<div><?= htmlspecialchars($entry['language'] ?? '') ?></div>
										<div class="entry__status entry__status--<?= htmlspecialchars($entry['status']) ?>"><?= htmlspecialchars($entry['status']) ?></div>
										<div><?= htmlspecialchars($entry['csv_value'] ?? '') ?></div>
										<div><?= htmlspecialchars($entry['existing_value'] ?? ($entry['status'] === 'new' ? 'N/A' : '')) ?></div>
									</div>
								<?php endforeach; ?>
							<?php endforeach; ?>
						<?php endforeach; ?>
					</div>
				<?php else: ?>
					<p>No value comparison data available.</p>
				<?php endif; ?>
			</div>

			<!-- Confirmation Form -->
			<form class="confirmation-form" method="post" action="<?= config::get('base') ?>import/confirm">
				<label for="importValuesInCsvNotInDb">
					<input id="importValuesInCsvNotInDb" type="checkbox" class="custom-checkbox" name="importValuesInCsvNotInDb" value="true">
					Import values that exist in CSV but not in database</label>
				<div>
					<input class="btn btn--primary" type="submit" id="confirmImportBtn" value="Confirm Import" disabled>
					<a class="btn" href="<?= config::get('base') ?>import" style="margin-left: 10px;">Cancel</a>
				</div>
			</form>
		<?php endif; ?>
	<?php endif; ?>
</div>

<script>
	document.addEventListener('DOMContentLoaded', function() {

		const checkbox = document.getElementById('importValuesInCsvNotInDb');
		const submitBtn = document.getElementById('confirmImportBtn');

		const hasChangedEntries = <?= !empty($csvData['changed']) ? 'true' : 'false' ?>;
		const hasNewEntries = <?= !empty($csvData['new']) ? 'true' : 'false' ?>;

		function updateSubmitButton() {
			const shouldEnable = hasChangedEntries || (hasNewEntries && checkbox.checked);
			submitBtn.disabled = !shouldEnable;
		}

		checkbox.addEventListener('change', updateSubmitButton);
		updateSubmitButton(); // Initial check

		const fileInput = document.getElementById('file-upload');
		const fileNameDisplay = document.getElementById('file-name-display');
		const uploadButton = document.getElementById('upload-button');

		if (fileInput && fileNameDisplay) {
			fileInput.addEventListener('change', function() {
				if (this.files && this.files.length > 0) {
					fileNameDisplay.textContent = this.files[0].name;
					uploadButton.disabled = false;
				} else {
					fileNameDisplay.textContent = '';
					uploadButton.disabled = true;
				}
			});
		}
	});
</script>