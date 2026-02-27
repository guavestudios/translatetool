<?php

class AuthController extends BaseController
{
	public static function loginAuth()
	{
		if (auth::login($_POST['username'], $_POST['passwd'])) {
			self::redirect('');
		} else {
			self::redirect('login');
		}
	}

	public static function logout()
	{
		auth::logout();
		self::redirect('');
	}

	public static function login()
	{
		self::render('login');
	}
}
