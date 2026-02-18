<form action="<?= (isset($folder) ? 'edit' : 'add') ?>/folder/" method="post">
	<h1>Subordner <?= (isset($folder) ? 'editieren' : 'erstellen') ?></h1>
	<div class="folder-edit-form">
		<div class="folder-edit-form-row">
			<label for="foldername">Name</label>
			<input type="text" name="foldername" id="foldername" value="<?= (isset($folder) ? $folder['key'] : '') ?>" placeholder="Name">
		</div>
		<div class="folder-edit-form-row">
			<label for="parent_id">Parent folder</label>
			<select name="parent_id" id="parent_id">
				<?php
			$selectedParentId = isset($selected_parent_id) ? (int) $selected_parent_id : 0;
			$options = isset($parent_options) ? $parent_options : array(array('id' => 0, 'label' => 'Root'));
			foreach ($options as $option):
				$optionId = (int) $option['id'];
				$selected = ($optionId === $selectedParentId) ? ' selected' : '';
				?>
				<option value="<?= $optionId ?>" <?= $selected ?>><?= htmlspecialchars($option['label']) ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
	<?php if (isset($folder)): ?>
		<input type="hidden" name="edit_id" value="<?= (int) $active ?>">
	<?php endif; ?>
	<input class="btn btn--primary" type="submit" value="Speichern">
</form>