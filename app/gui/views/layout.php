<!DOCTYPE html>
<html>
	<head>
		<title>Translate Tool</title>
		<link href='http://fonts.googleapis.com/css?family=PT+Sans:400,700' rel='stylesheet' type='text/css'>
		<link href="/gui/css/styles.css" rel="stylesheet" type="text/css">
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
		<script>
			$(function(){
				$('.keyform').on('keypress', 'input', function (e) {
				  if (e.which == 13) {
					$(this).parent().append('<input type="text" name="key[]" value="" placeholder="Key" class="key"> '+
											'<input type="text" name="value[]" value="" placeholder="Wert" class="value">'+
											'<input type="hidden" name="id[]" value="">'+
											'<br>'
											);
					e.preventDefault();
					$(this).parent().find('.key').last().focus();
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
			<?= $body_content ?>
		</div>
	</body>
</html>