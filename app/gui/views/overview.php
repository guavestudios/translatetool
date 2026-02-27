<?php if (isset($keys)): ?>
	<form action="" method="post" class="keyform">
		<div class="inputvalues">
			<?php $languages = config::get('languages'); ?>
			<header class="add-key-section collapsible">
				<div class="collapsible-header">
					<i class="ph ph-caret-down"></i>add new key
				</div>
				<div class="collapsible-content">
					<div class="trans-item trans-item--add-key" data-group-key="new_0">
						<div class="key-wrapper">
							<input type="text" name="groups[new_0][key]" value="" class="keyname-input-main" placeholder="Key Name" pattern="^\S+$" title="No spaces allowed">
						</div>
						<div class="values-container">
							<?php foreach ($languages as $langIdx => $language): ?>
								<div class="lang-row">
									<span class="lang-row__name"><?= $language ?></span>
									<input type="hidden" aria-hidden="true" name="groups[new_0][rows][<?= htmlspecialchars($language) ?>][id]" value="" class="row-id-input">
									<textarea name="groups[new_0][rows][<?= htmlspecialchars($language) ?>][value]" class="value"></textarea>
								</div>
							<?php endforeach; ?>
						</div>
						<div class="add-key-actions">
							<input type="submit" value="Add Key" class="btn btn--primary">
						</div>
					</div>
				</div>
			</header>
			<?php $groupIndexCounter = 0; ?>
			<?php foreach ($keys as $groupKey => $key): ?>
				<?php $groupIndex = $groupIndexCounter++; ?>
				<?php
				$firstRowId = '';
				foreach ($languages as $language) {
					if (isset($key[$language][0]['id'])) {
						$firstRowId = (string) $key[$language][0]['id'];
						break;
					}
				}
				?>
				<div class="trans-item translation-key-group" id="k_<?= htmlspecialchars($key['keyName']) ?>" data-row-id="<?= htmlspecialchars($firstRowId) ?>" data-group-key="<?= $groupIndex ?>">
					<div class="key-wrapper">
						<div class="checkbox-container">
							<input type="checkbox" class="custom-checkbox">
						</div>
						<input type="text" name="groups[<?= $groupIndex ?>][key]" value="<?= htmlspecialchars($key['keyName']) ?>" class="keyname-input-main" placeholder="Key Name" pattern="^\S+$" title="No spaces allowed">
						<a class="delete-key" data-active="<?= $active ?>">
							<i class="delete-key ph ph-trash"></i>
						</a>
					</div>
					<div class="values-container">
						<?php foreach ($languages as $langIdx => $language): ?>
							<?php
							// Find the first row for this language, if any
							$row = null;
							if (isset($key[$language]) && !empty($key[$language])) {
								$row = $key[$language][0];
							}
							?>
							<div class="lang-row" <?= $row && isset($row['id']) ? ' id="row_' . $row['id'] . '"' : '' ?>>
								<span class="lang-row__name"><?= $language ?></span>
								<input type="hidden" aria-hidden="true" name="groups[<?= $groupIndex ?>][rows][<?= htmlspecialchars($language) ?>][id]" value="<?= $row && isset($row['id']) ? $row['id'] : '' ?>" class="row-id-input">
								<textarea name="groups[<?= $groupIndex ?>][rows][<?= htmlspecialchars($language) ?>][value]" class="value"><?= $row ? htmlspecialchars($row['value']) : '' ?></textarea>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
			<div class="form-actions">
				<input type="submit" value="Save Changes (Cmd/Ctrl + S)" class="save-changes-btn btn btn--primary">
				<button class="btn" type="button" id="bulkActionsModeBtn">Bulk actions</button>
				<div class="bulk-actions-toolbar" id="bulkActionsToolbar">
					<button class="btn btn--error" type="button" id="deleteSelectedBtn">
						<i class="ph ph-trash"></i>
						Delete selected
					</button>
					<div class="bulk-move-dropdown" id="bulkMoveDropdown">
						<button class="btn btn--primary" type="button" id="moveSelectedBtn" aria-expanded="false" aria-controls="bulkMovePanel">
							<i class="ph ph-arrow-right"></i>
							Move selected
						</button>
						<div class="bulk-move-panel" id="bulkMovePanel" hidden>
							<div class="folder-edit-form">
								<div class="folder-edit-form-row">
									<label for="bulkMoveTarget">Parent folder</label>
									<select id="bulkMoveTarget" name="bulk_move_target">
										<?php
										$folderOptions = isset($folder_options) ? $folder_options : array(array('id' => 0, 'label' => 'Root'));
										foreach ($folderOptions as $option):
											?>
											<option value="<?= (int) $option['id'] ?>"><?= htmlspecialchars($option['label']) ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>
							<button class="btn btn--primary" type="button" id="confirmMoveBtn">Move</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>
<?php else: ?>
	<form action="add/folder/" method="post">
		<h1>Bundle erstellen</h1>
		<input type="text" name="foldername" placeholder="Name">
		<input type="hidden" aria-hidden="true" name="parent_id" value="0">
		<input class="btn btn--primary" type="submit" value="Erstellen">
	</form>
<?php endif; ?>

<script>
	// Auto-expanding textareas with line break prevention
	document.querySelectorAll('textarea.value').forEach(textarea => {
		// Prevent Enter key (no line breaks)
		textarea.addEventListener('keydown', function(e) {
			if (e.key === 'Enter') {
				e.preventDefault();
			}
		});

		// Strip line breaks on paste
		textarea.addEventListener('paste', function(e) {
			e.preventDefault();
			const text = (e.clipboardData || window.clipboardData).getData('text');
			const cleaned = text.replace(/[\r\n]+/g, ' ').trim();
			document.execCommand('insertText', false, cleaned);
			autoResize.call(this);
		});
	});

	// Strip line breaks before form submission
	const keyform = document.querySelector('.keyform');
	if (keyform) {
		keyform.addEventListener('submit', function(e) {
			document.querySelectorAll('textarea.value').forEach(function(textarea) {
				textarea.value = textarea.value.replace(/[\r\n]+/g, ' ').trim();
			});
		});
	}
</script>