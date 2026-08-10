<!DOCTYPE html>
<html>

<head>
	<title>LanGuave Tool</title>
	<meta charset="utf-8">
	<base href="//<?= $_SERVER['HTTP_HOST'] . config::get('base') ?>">
	<link href="gui/css/styles.css" rel="stylesheet" type="text/css">
	<link
		rel="stylesheet"
		type="text/css"
		href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css" />
	<script type="text/javascript" src="gui/js/main.js"></script>
</head>

<body>
	<div id="wrapper">
		<header class="page-header">
			<h1>LanGuave Tool</h1>			
			<div class="page-header__actions">
				<a href="nottranslated">pending translations</a>
				<?php if (config::get('export_download')): ?>
					<a href="download">export</a>
				<?php endif ?>
				<a href="import">import</a>
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
				<a href="overview" class="btn btn--secondary btn--block"><i class="ph ph-plus"></i>Bundle erstellen</a>
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