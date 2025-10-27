<h1>Suchergebnisse</h1>

<?php if(empty($results)): ?>
	<p>Keine Ergebnisse gefunden</p>
<?php else: ?>
	<?php 
	// Group results by folder
	$groupedByFolder = [];
	foreach($results as $result) {
		$folderId = $result['folder_id'];
		if (!isset($groupedByFolder[$folderId])) {
			$groupedByFolder[$folderId] = [
				'folder_name' => $result['folder_name'],
				'folder_id' => $folderId,
				'results' => []
			];
		}
		$groupedByFolder[$folderId]['results'][] = $result;
	}
	?>
	
	<div class="keys-grid keys-grid--search">
		<div class="keys-grid-header">
			<div>Key</div>
			<div>Language</div>
			<div>Value</div>
		</div>
		
		<?php foreach($groupedByFolder as $folder): ?>
			<h2 class="folder-header">
				<?= htmlspecialchars($folder['folder_name']) ?>
			</h2>
			
			<?php foreach($folder['results'] as $result): ?>
				<a href="key/<?= $result['folder_id'] ?>#row_<?= $result['id'] ?>" class="keys-grid-row" title="Click to edit this key">
					<div class="key-name"><?= htmlspecialchars($result['key']) ?></div>
					<div><?= htmlspecialchars($result['language']) ?></div>
					<div><?= htmlspecialchars($result['value']) ?></div>
				</a>
			<?php endforeach; ?>
		<?php endforeach; ?>
	</div>
<?php endif; ?>