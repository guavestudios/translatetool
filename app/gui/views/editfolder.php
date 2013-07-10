<form action="/<?= (isset($folder) ? 'edit' : 'add') ?>/folder/" method="post">
	<h1>Subordner <?= (isset($folder) ? 'editieren' : 'erstellen') ?></h1>
	<input type="text" name="foldername" value="<?= (isset($folder) ? $folder['key'] : '') ?>" placeholder="Name">
	<input type="hidden" name="parent_id" value="<?= $active ?>">
	<input type="submit" value="Speichern">
</form>