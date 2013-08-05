<?php if(isset($keys)): ?>
	<h1>Keys Editieren</h1>
	<form action="" method="post" class="keyform">
	<div class="inputvalues">
		<?php foreach($keys as $k => $key): ?>
			<div class="keyContainer">
			<?php foreach(config::get('languages') as $language): ?>
			<?php if(isset($key[$language])): $k = $key[$language]; ?>
				<?= $language ?>
				<input type="hidden" name="language[]" value="<?= $k['language']; ?>">
				<input type="text" name="key[]" value="<?= $k['key']; ?>"> 
				<input type="text" name="value[]" value="<?= $k['value']; ?>" class="value">
				<input type="hidden" name="id[]" value="<?= $k['id']; ?>"> 
				<a href="delete/<?= $k['id']; ?>/<?= $active ?>" onClick="return confirm('Wirklich löschen?')" tabindex="-1">
					<img src="gui/images/delete.png" border="0">
				</a>
			<?php else: ?>
				<?= $language ?>
				<input type="hidden" name="language[]" value="<?= $language; ?>">
				<input type="text" name="key[]" value="<?= $key['keyName']; ?>" placeholder="Key" class="key"> 
				<input type="text" name="value[]" value="" placeholder="Wert" class="value">
				<input type="hidden" name="language[]" value="<?= $language; ?>" placeholder="Wert" class="value">
				<input type="hidden" name="id[]" value="">
			<?php endif; ?>
			<br>
			<?php endforeach; ?>
			</div>
		<?php endforeach; ?>
		<div class="keyContainer">
		<?php $l = config::get('languages'); ?>
		<?= reset($l); ?>
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