<?php if (isset($keys)): ?>
	<form action="" method="post" class="keyform">
		<div class="inputvalues">
			<header class="add-key-section collapsible">
				<div class="collapsible-header">
					<i class="ph ph-caret-down"></i>add new key
				</div>
				<div class="trans-item collapsible-content">
					<input type="text" name="keyname[]" value="" class="keyname-input-main" placeholder="Key Name" pattern="^\S+$" title="No spaces allowed">
					<div class="values-container">
						<?php foreach (config::get('languages') as $langIdx => $language): ?>
							<div class="lang-row">
								<span class="lang-row__name"><?= $language ?></span>
								<input type="hidden" aria-hidden="true" name="language[]" value="<?= $language ?>">
								<input type="hidden" aria-hidden="true" name="key[]" value="" class="key-input sync-key" placeholder="Key">
								<input type="text" name="value[]" value="" class="value">
								<input type="hidden" aria-hidden="true" name="id[]" value=""> <!-- New key, no ID yet -->
							</div>
						<?php endforeach; ?>
					</div>
					<div class="add-key-actions">
						<input type="submit" value="Add Key" class="add-key-btn btn btn--primary">
					</div>
				</div>
			</header>
			<?php foreach ($keys as $k => $key): ?>
				<div class="trans-item translation-key-group" id="k_<?= htmlspecialchars($key['keyName']) ?>">
					<div class="key-wrapper">
						<div class="checkbox-container">
							<input type="checkbox" class="custom-checkbox">
						</div>
						<input type="text" name="keyname[]" value="<?= htmlspecialchars($key['keyName']) ?>" class="keyname-input-main" placeholder="Key Name" pattern="^\S+$" title="No spaces allowed">
						<a class="delete-key" data-active="<?= $active ?>">
							<i class="delete-key ph ph-trash"></i>
						</a>
					</div>
					<div class="values-container">
						<?php foreach (config::get('languages') as $langIdx => $language): ?>
							<?php
							// Find the first row for this language, if any
							$row = null;
							if (isset($key[$language]) && !empty($key[$language])) {
								$row = $key[$language][0];
							}
							?>
							<div class="lang-row" <?= $row && isset($row['id']) ? ' id="row_' . $row['id'] . '"' : '' ?>>
								<span class="lang-row__name"><?= $language ?></span>
								<input type="hidden" aria-hidden="true" name="language[]" value="<?= $language ?>">
								<input type="hidden" aria-hidden="true" name="key[]" value="<?= $row ? $row['key'] : '' ?>" class="key-input sync-key" placeholder="Key" <?= $row && isset($row['id']) ? ' id="key-sync-' . $row['id'] . '-' . $language . '"' : '' ?>>
								<input type="text" name="value[]" value="<?= $row ? htmlspecialchars($row['value']) : '' ?>" class="value">
								<input type="hidden" aria-hidden="true" name="id[]" value="<?= $row && isset($row['id']) ? $row['id'] : '' ?>">
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
			<div class="form-actions">
				<input type="submit" value="Save Changes (CMD + S)" class="save-changes-btn btn btn--primary">
				<button class="btn" type="button" id="multiDeleteModeBtn">Bulk Delete</button>
				<button class="btn btn--error" type="button" id="deleteSelectedBtn" style="display:none">
					<i class="ph ph-trash"></i>
					Delete selected
				</button>
			</div>
		</div>
	</form>
<?php else: ?>
	Bitte wähle einen Key aus der linken Spalte.<br>
	<br>
	<form action="add/folder/" method="post">
		<h1>Bundle erstellen</h1>
		<input type="text" name="foldername" placeholder="Name">
		<input type="hidden" aria-hidden="true" name="parent_id" value="0">
		<input class="btn btn--primary" type="submit" value="Erstellen">
	</form>
<?php endif; ?>