<h1>Nicht übersetzte Phrasen</h1>

<table id="notTranslatedTable" class="keytable">
	<tr>
		<td>Key</td>
		<td>Language</td>
		<td>Folder</td>
	</tr>
<?php if(empty($keys)): ?>
	<tr>
		<td colspan="3" align="center">Alle Keys sind übersetzt</td>
	</tr>
<?php else: ?>
	<?php foreach($keys as $result): ?>
		<tr>
			<td><?= htmlspecialchars($result['key']) ?></td>
			<td><?= htmlspecialchars(implode(", ", $result['languages'])) ?></td>
			<td><a href="key/<?= $result['folder_id'] ?>#row_<?= $result['folder_id'] ?>"><?= $result['folder_name'] ?></a></td>
		</tr>
	<?php endforeach ?>
<?php endif; ?>
</table>
