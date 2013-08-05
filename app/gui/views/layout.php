<!DOCTYPE html>
<html>
	<head>
		<title>Translate Tool</title>
		<meta charset="utf-8">
		<base href="http://<?= $_SERVER['HTTP_HOST'].config::get('base') ?>">
		<link href='http://fonts.googleapis.com/css?family=PT+Sans:400,700' rel='stylesheet' type='text/css'>
		<link href="gui/css/styles.css" rel="stylesheet" type="text/css">
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
		<script type="text/javascript" src="gui/js/main.js"></script>
		<script>
			<?php $l = config::get('languages'); ?>
			$(function(){
				$('.keyform').on('keypress', 'input', function (e) {
				  if (e.which == 13) {
					$('.inputvalues').append('<div class="keyContainer"><?= reset($l); ?><input type="text" name="key[]" value="" placeholder="Key" class="key"> '+
											'<input type="text" name="value[]" value="" placeholder="Wert" class="value">'+
											'<input type="hidden" name="id[]" value="">'+
											'<input type="hidden" name="language[]" value="<?= reset($l); ?>"></div>'
											);
					e.preventDefault();
					$('.inputvalues').find('.key').last().focus();
					return false;
				  }
				});
				$(document).keydown(function(evt){
					if (evt.keyCode==83 && (evt.ctrlKey)){
						evt.preventDefault();
						$('.keyform').submit();
					}
				});
			})
		</script>
	</head>
	<body>
		<div id="wrapper">
			<div class="box clearfix">
				<div class="title clearfix">
					<span class="text">GUAVE Übersetzungen</span>
					<span class="buttons">
						<?php
							$bounceback = substr($_SERVER['REQUEST_URI'], strlen(config::get('base')));
						?>
						<a href="update?bounceback=<?= $bounceback ?>">Update</a> | 
						<a href="logout">Logout</a>
					</span>
				</div>
				<div class="tree">
					<div class="treeInner">
						<ul>
							<li>
								<a href="overview">Home</a>
								<?php echo translations::getTreeHtml($active); ?>
							</li>
						</ul>
					</div>
				</div>
				<div class="treeText">
					<?= $body_content ?>
				</div>
			</div>
		</div>
	</body>
</html>