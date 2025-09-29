<!DOCTYPE html>
<html>
	<head>
		<title>Languave - Login</title>
		<base href="//<?= $_SERVER['HTTP_HOST'].config::get('base') ?>">
		<link href="gui/css/styles.css" rel="stylesheet" type="text/css">
	</head>
	<body>
		<div id="wrapper" class="centered">
			<div class="login-popup">
				<h1>Login</h1>
				<div class="text">
					<form action="" method="post">
						<input type="text" name="username" placeholder="Benutzername">
						<input type="password" name="passwd" placeholder="Passwort">
						<input type="submit" value="Anmelden">
					</form>
				</div>
			</div>
		</div>
	</body>
</html>
