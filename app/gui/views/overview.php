<?php if(isset($keys)): ?>
	<h1>Keys Editieren</h1>
	<form action="" method="post" class="keyform">
	<div class="inputvalues">
		<?php foreach($keys as $key): ?>
			<input type="text" name="key[]" value="<?= $key['key']; ?>"> 
			<input type="text" name="value[]" value="<?= $key['value']; ?>" class="value">
			<input type="hidden" name="id[]" value="<?= $key['id']; ?>"> 
			<a href="/delete/<?= $key['id']; ?>/<?= $active ?>" onClick="return confirm('Wirklich löschen?')" tabindex="-1">
				<img src="/gui/images/delete.png" border="0">
			</a>
			<br>
		<?php endforeach; ?>
		<input type="text" name="key[]" value="" placeholder="Key" class="key"> 
		<input type="text" name="value[]" value="" placeholder="Wert" class="value">
		<input type="hidden" name="id[]" value="">
		<br>
	</div>
	<input type="submit" value="Speichern"> (CTRL + S) - Leere Einträge werden nicht gespeichert.
	</form>
<?php else: ?>
	Bitte wähle einen Key aus der linken Spalte.<br>
	<br>
	<form action="/add/folder/" method="post">
		<h1>Bundle erstellen</h1>
		<input type="text" name="foldername" placeholder="Name">
		<input type="hidden" name="parent_id" value="0">
		<input type="submit" value="Erstellen">
	</form>
<?php endif; ?>