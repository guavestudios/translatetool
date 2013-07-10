<?php

require 'php/flight/Flight.php';
require 'php/auth.class.php';
require 'php/translations.class.php';
require '../config.class.php';

date_default_timezone_set("Europe/Zurich");

Flight::before('route', function(&$params, &$output){
	if(!auth::ed() and $params[0] != '/login' and $params[0] != 'POST /login'){
		controller::redirect('/login');
	}
});

Flight::route('POST /login', array('controller','loginAuth'));
Flight::route('/login', array('controller','login'));

Flight::route('/overview', array('controller','overview'));

Flight::route('POST /key/@keyId', array('controller','saveKeys'));
Flight::route('/key/@keyId', array('controller','key'));

Flight::route('POST /add/folder', array('controller','addFolder'));

class controller{

	public static function addFolder(){
		translations::append(array(
			array(
				'key' => $_POST['foldername'],
				'parent_id' => $_POST['parent_id'],
				'value' => null
			)
		));
		self::redirect('/key/'.translations::insertId());
	}

	public static function key($keyId){
		$keys = translations::getValues($keyId);
		self::render('overview', array('keys' => $keys, 'active' => $keyId));
	}

	public static function saveKeys($keyId){
		$entries = array();
		foreach($_POST['key'] as $k => $key){
			$value = $_POST['value'][$k];
			$id = $_POST['id'][$k];
			if(empty($id) and empty($value) and empty($key)){
				continue;
			}
			if(empty($id) and !(empty($value) and empty($key))){
				$entries[] = array(
					'parent_id' => $keyId,
					'key' => $key,
					'value' => $value
				);
			}
			if(!empty($id) and !(empty($value) and empty($key))){
				translations::update($id, array(
					'key' => $key,
					'value' => $value
				));
			}
		}
		translations::append($entries);
		self::redirect('/key/'.$keyId);
	}
	
	public static function overview(){
		self::render('overview', array('active' => 0));
	}

	public static function loginAuth(){
		if(auth::login($_POST['username'], $_POST['passwd'])){
			self::redirect('/');
		}else{
			self::redirect('/login');
		}
	}
	
	public static function login(){
		self::render('login');
	}
	
	private static function redirect($url){
		header('Location: '.$url);
		exit;
	}
	
	private static function render($template, $vars = array()){
		Flight::render($template, $vars, 'body_content');
		Flight::render('layout', $vars);
	}
	
}

Flight::start();