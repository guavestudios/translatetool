<!DOCTYPE html>
<html>

<head>
	<title>Languave</title>
	<meta charset="utf-8">
	<base href="//<?= $_SERVER['HTTP_HOST'] . config::get('base') ?>">
	<link href="gui/css/styles.css" rel="stylesheet" type="text/css">
	<script type="text/javascript" src="gui/js/main.js"></script>
</head>

<body>
	<div id="wrapper">
		<header class="page-header">
			<h1>Languave</h1>
			<div class="page-header__actions">
				<a href="nottranslated">zu übersetzen</a>
				<?php if (config::get('export_download')): ?>
					<a href="download">Export</a>
				<?php endif ?>
				<a href="import">Import</a>
				<!-- 
					<span>
						 <?php
							$bounceback = substr($_SERVER['REQUEST_URI'], strlen(config::get('base')));
							?>
					</span>
					-->
				<form action="search" method="post">
					<input type="text" name="search" value="<?= isset($_POST['search']) ? $_POST['search'] : '' ?>" placeholder="Search" id="searchField">
				</form>
			</div>
		</header>
		<main class="main-container">
			<aside class="tree">
				<a href="overview">Bundle erstellen</a>
				<div class="treeInner">
					<?php echo translations::getTreeHtml(@$active); ?>
				</div>
			</aside>
			<div class="list-container">
				<?= $body_content ?>
			</div>
		</main>
	</div>
</body>

</html>