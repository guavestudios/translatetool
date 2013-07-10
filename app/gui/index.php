<?php

require 'php/flight/Flight.php';
require 'php/auth.class.php';
require 'php/translations.class.php';
require 'php/curl.class.php';
require '../config.class.php';
require '../converter/convert.class.php';

use Guave\translatetool\converter;

date_default_timezone_set("Europe/Zurich");

Flight::before('route', function(&$params, &$output){
	if(!auth::ed() and $_SERVER['REQUEST_URI'] != '/login' and !isset($_GET['apicall'])){
		controller::redirect('/login');
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
		self::mirror();
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
		self::mirror();
		translations::deleteRow($keyId);
		self::redirect('/key/'.$active);
	}

	public static function key($keyId){
		$keys = translations::getValues($keyId);
		self::render('overview', array('keys' => $keys, 'active' => $keyId));
	}

	public static function saveKeys($keyId){
		self::mirror();
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
	
	private static function mirror(){
		$masters = config::get('masters');
		if($masters){
			foreach($masters as $masters){
				if(strstr($masters, $_SERVER['HTTP_HOST'])){
					throw new Exception("You might be trying to use your own server as master. That would probably not be a good idea. ({$masters})");
				}
				$response = self::post($masters.$_SERVER['REQUEST_URI'].'?apicall=true', $_POST);
				$result = explode("\r\n", $response);
				die($response);
			}
		}
	}
	
	private static function post($url, $post = array()){
		if(substr($url, 0, 7)!="http://"){
			$url = "http://".$url;
		}
		$curl = curl_init($url);
		if(count($post)>0){
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, self::asPostString($_POST));
		}
		curl_setopt($curl, CURLOPT_HEADER, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$return = curl_exec($curl);
		curl_close($curl);
		return $return;
	}
	
	private static function asPostString($theData, $theName = null){
		$thePostString = '';
		$thePrefix = $theName;

		if (is_array($theData)){
			foreach ($theData as $theKey => $theValue){
				if ($thePrefix === NULL){
					$thePostString .= '&' . self::asPostString($theValue, $theKey);
				}else{
					$thePostString .= '&' . self::asPostString($theValue, $thePrefix . '[' . $theKey . ']');
				}
			}
		}else{
			$thePostString .= '&' . urlencode((string)$thePrefix) . '=' . urlencode($theData);
		}

		$xxx = substr($thePostString, 1);

		return $xxx;
	}
	
}

Flight::start();