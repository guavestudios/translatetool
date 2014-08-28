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
						<span style="float:right">
							<?php
								$bounceback = substr($_SERVER['REQUEST_URI'], strlen(config::get('base')));
							?>
                            <?php if(config::get('export_download')): ?>
                                <a href="download">Download</a> |
                            <?php endif ?>
                            <a href="import">Import</a>
                            <!-- <a href="update?bounceback=<?= $bounceback ?>">Update</a> |-->
							<!-- <a href="logout">Logout</a> -->
						</span>
						<form action="search" method="post">
							<input type="text" name="search" value="<?= isset($_POST['search']) ? $_POST['search'] : '' ?>" placeholder="Search" id="searchField">
						</form>
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
            (function (d, t) {
                var bh = d.createElement(t), s = d.getElementsByTagName(t)[0];
                bh.type = 'text/javascript';
                bh.src = '//www.bugherd.com/sidebarv2.js?apikey=f0gxzm2anyfnnm45tjsonq';
                s.parentNode.insertBefore(bh, s);
            })(document, 'script');
        </script>
	</body>
</html>