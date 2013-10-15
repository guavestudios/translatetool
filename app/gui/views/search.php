<table id="searchTable">
	<tr>
		<td>Key</td>
		<td>Value</td>
		<td>Folder</td>
	</tr>
<?php if(empty($results)): ?>
	<tr>
		<td colspan="3" align="center">Keine Resultate</td>
	</tr>
<?php else: ?>
	<?php foreach($results as $result): ?>
		<tr>
			<td><?= htmlspecialchars($result['key']) ?></td>
			<td><?= htmlspecialchars($result['value']) ?></td>
			<td><a href="key/<?= $result['folder_id'] ?>"><?= $result['folder_name'] ?></a></td>
		</tr>
	<?php endforeach ?>
<?php endif; ?>
</table>