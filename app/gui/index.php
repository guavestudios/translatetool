<?php

require 'php/flight/Flight.php';
require 'php/auth.class.php';
require 'php/translations.class.php';
require 'php/curl.class.php';
require '../config.class.php';
require '../converter/convert.class.php';

use Guave\translatetool\converter;

date_default_timezone_set("Europe/Zurich");

Flight::set('mastersedRoutes', array(
	'POST /add/folder',
	'/delete/@keyId/@active',
	'POST /key/@keyId'
));

Flight::before('route', function(&$params, &$output){
	if(!auth::ed() and $params[0] != '/login' and $params[0] != 'POST /login'){
		controller::redirect('/login');
	}
});

Flight::before('route', function(&$params, &$output){
	if(in_array($params[0], Flight::get('mastersedRoutes')) and (strstr($params[0], 'POST') and !empty($_POST))){
		$c = new Curl();
		$masters = config::get('masters');
		if($masters){
			foreach($masters as $masters){
				if(strstr($masters, $_SERVER['HTTP_HOST'])){
					throw new Exception("You might be trying to use your own server as master. That would probably not be a good idea. ({$masters})");
				}
				$c->header(true);
				$response = $c->post($masters.$_SERVER['REQUEST_URI'], $_POST);
				die(reset(explode("\r\n", $response)));
			}
		}
	}
});

Flight::route('POST /login', array('controller','loginAuth'));
Flight::route('/login', array('controller','login'));

Flight::route('/overview', array('controller','overview'));

Flight::route('POST /key/@keyId', array('controller','saveKeys'));
Flight::route('/key/@keyId', array('controller','key'));

Flight::route('POST /add/folder', array('controller','addFolder'));

Flight::route('/export/@format', array('controller','export'));
Flight::route('/delete/@keyId/@active', array('controller','deleteKey'));

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
	
	public static function deleteKey($keyId, $active){
		translations::deleteRow($keyId);
		self::redirect('/key/'.$active);
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
	
	public static function export($format){
		$converter = new converter();
		$output = $converter->save($format, translations::getTree(true));
		header("Content-Type: {$output['meta']['mime']}");
		echo $output['file'];
		exit;
	}
	
	public static function login(){
		self::render('login');
	}
	
	public static function redirect($url){
		header('Location: '.$url);
		exit;
	}
	
	private static function render($template, $vars = array()){
		Flight::render($template, $vars, 'body_content');
		Flight::render('layout', $vars);
	}
	
}

Flight::start();