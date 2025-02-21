<!DOCTYPE html>
<html>
	<head>
		<title>Translate Tool</title>
		<base href="//<?= $_SERVER['HTTP_HOST'].config::get('base') ?>">
		<link href='//fonts.googleapis.com/css?family=PT+Sans:400,700' rel='stylesheet' type='text/css'>
		<link href="gui/css/styles.css" rel="stylesheet" type="text/css">
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
	</head>
	<body>
		<div id="wrapper">
			<div style="height: 200px"></div>
			<div class="box small">
				<div class="title">Login</div>
				<div class="text">
					<form action="" method="post">
						<input type="text" name="username" placeholder="Benutzername"><br>
						<input type="password" name="passwd" placeholder="Passwort"><br>
						<input type="submit" value="Anmelden">
					</form>
				</div>
			</div>
		</div>
	</body>
</html>
