<!DOCTYPE html>
<html>

<head>
	<title>Guave Lingua</title>
	<meta charset="utf-8">
	<base href="//<?= $_SERVER['HTTP_HOST'] . config::get('base') ?>">
	<link href="gui/css/styles.css" rel="stylesheet" type="text/css">
	<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
	<script type="text/javascript" src="//code.jquery.com/color/jquery.color-2.1.2.min.js"></script>
	<script type="text/javascript" src="gui/js/main.js"></script>
	<script>
		<?php $l = config::get('languages'); ?>
		$(function() {
			$('.keyform').on('keypress', 'input', function(e) {
				if (e.which == 13) {
					$('.inputvalues').append('<div class="keyContainer"><a><?= reset($l); ?></a><input type="text" name="key[]" value="" placeholder="Key" class="key"> ' +
						'<input type="text" name="value[]" value="" placeholder="Wert" class="value">' +
						'<input type="hidden" name="id[]" value="">' +
						'<input type="hidden" name="language[]" value="<?= reset($l); ?>"></div>'
					);
					e.preventDefault();
					$('.inputvalues').find('.key').last().focus();
					return false;
				}
			});
			$(document).keydown(function(evt) {
				if (evt.keyCode == 83 && (evt.ctrlKey)) {
					evt.preventDefault();
					$('.keyform').submit();
				}
			});
		})
	</script>
</head>

<body>
	<div id="wrapper">
		<header class="page-header">
			<h1>Guave Lingua</h1>
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
	<!--
        <script type='text/javascript'>
            (function (d, t) {
                var bh = d.createElement(t), s = d.getElementsByTagName(t)[0];
                bh.async=true;
                bh.type = 'text/javascript';
                bh.src = '//translatetool.local/gui/js/widget.js';
                s.parentNode.insertBefore(bh, s);
            })(document, 'script');
        </script>
        -->
	<script type='text/javascript'>
		(function(d, t) {
			var bh = d.createElement(t),
				s = d.getElementsByTagName(t)[0];
			bh.type = 'text/javascript';
			bh.src = '//www.bugherd.com/sidebarv2.js?apikey=f0gxzm2anyfnnm45tjsonq';
			s.parentNode.insertBefore(bh, s);
		})(document, 'script');
	</script>
</body>

</html>