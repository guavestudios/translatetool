<?php if(isset($keys)): ?>
	<h1>Keys Editieren</h1>
	<form action="" method="post" class="keyform">
	<div class="inputvalues">
		<?php foreach($keys as $k => $key): ?>
			<div class="keyContainer">
			<?php foreach(config::get('languages') as $language): ?>
			<?php if(isset($key[$language])): $k = $key[$language]; ?>
				<?php foreach($k as $row): ?>
				<div id="row_<?= $row['id'] ?>">
                    <a name="row_<?= $row['id'] ?>"><?= $language ?></a>
					<input type="hidden" name="language[]" value="<?= $row['language']; ?>">
					<input type="text" name="key[]" value="<?= $row['key']; ?>"> 
					<input type="text" name="value[]" value="<?= htmlspecialchars($row['value']); ?>" class="value">
					<input type="hidden" name="id[]" value="<?= $row['id']; ?>"> 
					<a href="delete/<?= $row['id']; ?>/<?= $active ?>" onClick="return confirm('Wirklich löschen?')" tabindex="-1">
						<img src="gui/images/delete.png" border="0">
					</a>
				</div>
				<?php endforeach; ?>
			<?php else: ?>
                <div>
                    <a><?= $language ?></a>
                    <input type="text" name="key[]" value="<?= $key['keyName']; ?>" placeholder="Key" class="key">
                    <input type="text" name="value[]" value="" placeholder="Wert" class="value">
                    <input type="hidden" name="language[]" value="<?= $language; ?>">
                    <input type="hidden" name="id[]" value="">
                </div>
			<?php endif; ?>
			<?php endforeach; ?>
			</div>
		<?php endforeach; ?>
		<div class="keyContainer">
		<?php $l = config::get('languages'); ?>
		<a><?= reset($l); ?></a>
		<input type="text" name="key[]" value="" placeholder="Key" class="key"> 
		<input type="text" name="value[]" value="" placeholder="Wert" class="value">
		<input type="hidden" name="language[]" value="<?= reset($l); ?>">
		<input type="hidden" name="id[]" value="">
		</div>
	</div>
	<input type="submit" value="Speichern"> (CTRL + S) - Leere Einträge werden nicht gespeichert.
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