<?php

require 'php/flight/Flight.php';
require 'php/auth.class.php';
require 'php/translations.class.php';
require '../config.class.php';

date_default_timezone_set("Europe/Zurich");

Flight::before('route', function(&$params, &$output){
	if(!auth::ed() and $params[0] != '/login' and $params[0] != 'POST /login'){
		header('Location: /login');
		exit;
	}
});

Flight::route('POST /login', array('controller','loginAuth'));
Flight::route('/login', array('controller','login'));

Flight::route('/overview', array('controller','overview'));

class controller{

	public static function overview(){
		$tree = translations::getTree();
		self::render('overview', array('tree' => $tree));
	}

	public static function loginAuth(){
		if(auth::login($_POST['username'], $_POST['passwd'])){
			header('Location: /');
			exit;
		}else{
			header('Location: /login');
			exit;		
		}
	}
	
	public static function login(){
		self::render('login');
	}
	
	private static function render($template, $vars = array()){
		Flight::render($template, $vars, 'body_content');
		Flight::render('layout', $vars);
	}
	
}

Flight::start();