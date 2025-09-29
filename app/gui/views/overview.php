<?php if (isset($keys)): ?>
	<form action="" method="post" class="keyform">
		<div class="inputvalues">
			<div class="action-container">
				<input type="submit" value="Save (CMD + S)">
				<button type="button" id="multiDeleteModeBtn">Select multiple for deletion</button>
				<button type="button" id="deleteSelectedBtn" style="display:none">
					<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 32 32">
						<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m3.04 3.04 25.92 25.92m0-25.92L3.04 28.96" />
					</svg>
					Delete selected
				</button>
				<!-- <p>
						<small>
							Empty entries will not be saved.
						</small>
					</p> -->
			</div>
			<header class="add-key-section">
				<p>add new key</p>
				<div class="trans-item">
					<input type="text" name="keyname[]" value="" class="keyname-input-main" placeholder="Key Name" pattern="^\S+$" title="No spaces allowed">
					<div class="values-container">
						<?php foreach (config::get('languages') as $langIdx => $language): ?>
							<div class="lang-row">
								<span class="lang-row__name"><?= $language ?></span>
								<input type="hidden" name="language[]" value="<?= $language ?>">
								<input type="hidden" name="key[]" value="" class="key-input sync-key" placeholder="Key">
								<input type="text" name="value[]" value="" class="value">
								<input type="hidden" name="id[]" value=""> <!-- New key, no ID yet -->
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</header>
			<?php foreach ($keys as $k => $key): ?>
				<div class="trans-item translation-key-group" id="k_<?= htmlspecialchars($key['keyName']) ?>">
					<div class="key-wrapper">
						<input type="checkbox" class="multi-delete-checkbox">
						<input type="text" name="keyname[]" value="<?= htmlspecialchars($key['keyName']) ?>" class="keyname-input-main" placeholder="Key Name" pattern="^\S+$" title="No spaces allowed">
						<a class="delete-key" data-active="<?= $active ?>">
							<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 32 32">
								<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m3.04 3.04 25.92 25.92m0-25.92L3.04 28.96" />
							</svg>
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
								<input type="hidden" name="language[]" value="<?= $language ?>">
								<input type="hidden" name="key[]" value="<?= $row ? $row['key'] : '' ?>" class="key-input sync-key" placeholder="Key" <?= $row && isset($row['id']) ? ' id="key-sync-' . $row['id'] . '-' . $language . '"' : '' ?>>
								<input type="text" name="value[]" value="<?= $row ? htmlspecialchars($row['value']) : '' ?>" class="value">
								<input type="hidden" name="id[]" value="<?= $row && isset($row['id']) ? $row['id'] : '' ?>">
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</form>
<?php else: ?>
	Bitte wähle einen Key aus der linken Spalte.<br>
	<br>
	<form action="add/folder/" method="post">
		<h1>Bundle erstellen</h1>
		<input type="text" name="foldername" placeholder="Name">
		<input type="hidden" name="parent_id" value="0">
		<input type="submit" value="Erstellen">
	</form>
<?php endif; ?>