<h1>Nicht übersetzte Phrasen</h1>

<?php if(empty($keys)): ?>
	<p>Alle Keys sind übersetzt</p>
<?php else: ?>
	<?php 
	// Group keys by folder
	$groupedByFolder = [];
	foreach($keys as $result) {
		$folderId = $result['folder_id'];
		if (!isset($groupedByFolder[$folderId])) {
			$groupedByFolder[$folderId] = [
				'folder_name' => $result['folder_name'],
				'folder_id' => $folderId,
				'keys' => []
			];
		}
		$groupedByFolder[$folderId]['keys'][] = $result;
	}
	?>
	
	<div class="keys-grid">
		<div class="keys-grid-header">
			<div>Key</div>
			<div>Fehlende Sprachen</div>
		</div>
		
		<?php foreach($groupedByFolder as $folder): ?>
			<h2 class="folder-header">
				<?= htmlspecialchars($folder['folder_name']) ?>
			</h2>
			
			<?php foreach($folder['keys'] as $result): ?>
				<a href="key/<?= $result['folder_id'] ?>#row_<?= $result['row_id'] ?>" class="keys-grid-row">
					<div class="key-name"><?= htmlspecialchars($result['key']) ?></div>
					<div><?= htmlspecialchars(implode(", ", $result['languages'])) ?></div>
				</a>
			<?php endforeach; ?>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
