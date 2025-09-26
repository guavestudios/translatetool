<?php if (isset($keys)): ?>
	<form action="" method="post" class="keyform">
		<div class="inputvalues">
			<?php foreach ($keys as $k => $key): ?>
				<div class="trans-item" id="k_<?= htmlspecialchars($key['keyName']) ?>">
					<div class="key-wrapper">
						<input type="text" name="keyname[]" value="<?= htmlspecialchars($key['keyName']) ?>" class="keyname-input-main" placeholder="Key Name">
						<a class="delete-key" data-active="<?= $active ?>">
							<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 32 32">
								<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m3.04 3.04 25.92 25.92m0-25.92L3.04 28.96" />
							</svg>
						</a>
					</div>
					<div class="values-container">
						<?php foreach (config::get('languages') as $langIdx => $language): ?>
							<?php if (isset($key[$language])): $krows = $key[$language]; ?>
								<?php foreach ($krows as $row): ?>
									<div id="row_<?= $row['id'] ?>" class="lang-row">
										<span name="row_<?= $row['id'] ?>" class="lang-row__name"><?= $language ?></span>
										<input type="hidden" name="language[]" value="<?= $row['language']; ?>">
										<?php if ($langIdx === 0): ?>
											<input type="text" name="key[]" hidden value="<?= $row['key']; ?>" class="key-input sync-key" placeholder="Key" id="key-main-<?= $row['id'] ?>">
										<?php else: ?>
											<input type="text" name="key[]" hidden value="<?= $row['key']; ?>" class="key-input sync-key" placeholder="Key" id="key-sync-<?= $row['id'] ?>-<?= $language ?>">
										<?php endif; ?>
										<input type="text" name="value[]" value="<?= htmlspecialchars($row['value']); ?>" class="value">
										<input type="hidden" name="id[]" value="<?= $row['id']; ?>">
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
			<h2>add key</h2>
			<div class="trans-item">
				<input type="text" name="keyname[]" value="" class="keyname-input-main" placeholder="Key Name">
				<div class="values-container">
					<?php foreach (config::get('languages') as $langIdx => $language): ?>
						<div class="lang-row">
							<span class="lang-row__name"><?= $language ?></span>
							<input type="hidden" name="language[]" value="<?= $language ?>">
							<?php if ($langIdx === 0): ?>
								<input type="text" name="key[]" value="" class="key-input sync-key" placeholder="Key" hidden>
							<?php else: ?>
								<input type="text" name="key[]" value="" class="key-input sync-key" placeholder="Key" hidden>
							<?php endif; ?>
							<input type="text" name="value[]" value="" class="value">
							<input type="hidden" name="id[]" value="">
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<script>
				document.addEventListener('DOMContentLoaded', function() {
					// For each trans-item, sync only its own key inputs
					document.querySelectorAll('.trans-item').forEach(function(container) {
						const mainKeyInput = container.querySelector('.keyname-input-main');
						if (mainKeyInput) {
							const syncInputs = container.querySelectorAll('.sync-key');
							mainKeyInput.addEventListener('input', function() {
								syncInputs.forEach(function(syncInput) {
									if (syncInput !== mainKeyInput) {
										syncInput.value = mainKeyInput.value;
									}
								});
							});
						}
						// JS delete link logic
						const deleteLink = container.querySelector('.delete-key');
						if (deleteLink) {
							const row = container.querySelector('.lang-row input[name="id[]"]');
							if (row && row.value) {
								const active = deleteLink.getAttribute('data-active');
								deleteLink.href = `delete/${row.value}/${active}`;
								deleteLink.onclick = function() {
									const keyName = mainKeyInput ? mainKeyInput.value : '';
									return confirm(`Wirklich löschen?\nKey: ${keyName}`);
								};
							} else {
								deleteLink.style.display = 'none';
							}
						}
					});
				});
			</script>
		</div>
		<input type="submit" value="Speichern">
		<p>
			<small>
				(CTRL + S) - Leere Einträge werden nicht gespeichert.
			</small>
		</p>
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