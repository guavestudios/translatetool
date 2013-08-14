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
	if(!auth::ed() and $_SERVER['REQUEST_URI'] != config::get('base').'login' and !isset($_GET['apicall'])){
		controller::redirect('login');
	}
});

Flight::route('/', function(){
	controller::redirect('overview');
});

Flight::route('POST /login', array('controller','loginAuth'));
Flight::route('/login', array('controller','login'));
Flight::route('/logout', array('controller','logout'));

Flight::route('/overview', array('controller','overview'));

Flight::route('POST /key/@keyId', array('controller','saveKeys'));
Flight::route('/key/@keyId', array('controller','key'));

Flight::route('POST /add/folder', array('controller','addFolder'));
Flight::route('/add/folder/@parentId', array('controller','showAddFolder'));
Flight::route('POST /edit/folder', array('controller','editFolder'));
Flight::route('/edit/folder/@editId', array('controller','showEditFolder'));
Flight::route('/del/folder/@delId', array('controller','delFolder'));

Flight::route('/export', array('controller','export'));
Flight::route('/delete/@keyId/@active', array('controller','deleteKey'));

Flight::route('/poll', array('controller', 'poll'));
Flight::route('/dump', array('controller', 'dump'));
Flight::route('/update', array('controller', 'updateDb'));

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
		self::redirect('key/'.translations::insertId());
	}
	
	public static function editFolder(){
		self::mirror();
		translations::update($_POST['parent_id'],
			array(
				'key' => $_POST['foldername']
			)
		);
		self::redirect('key/'.$_POST['parent_id']);
	}
	
	public static function delFolder($delId){
		self::recursiveDelete($delId);
		self::redirect('overview');
	}
	
	private static function recursiveDelete($delId){
		$items = translations::get(array(),array(),"parent_id = {$delId}");
		if(!empty($items)){
			foreach($items as $item){
				self::recursiveDelete($item['id']);
			}
		}
		translations::deleteRow($delId);
	}
	
	public static function showAddFolder($parentId){
		self::render('editfolder', array('active' => $parentId));
	}
	
	public static function showEditFolder($editId){
		$folder = translations::getOne($editId);
		self::render('editfolder', array('active' => $editId, 'folder' => $folder));
	}
	
	public static function deleteKey($keyId, $active){
		self::mirror();
		translations::deleteRow($keyId);
		self::redirect('key/'.$active);
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
			$language = $_POST['language'][$k];
			if(empty($id) and empty($value) and empty($key)){
				continue;
			}
			if(empty($id) and !empty($value) and !empty($key)){
				$entries[] = array(
					'parent_id' => $keyId,
					'key' => $key,
					'value' => $value,
					'language' => $language
				);
			}
			if(!empty($id) and !empty($value) and !empty($key)){
				translations::update($id, array(
					'key' => $key,
					'value' => $value
				));
			}
		}
		translations::append($entries);
		self::export();
		self::redirect('key/'.$keyId);
	}
	
	public static function overview(){
		self::render('overview', array('active' => 0));
	}

	public static function loginAuth(){
		if(auth::login($_POST['username'], $_POST['passwd'])){
			self::redirect('');
		}else{
			self::redirect('login');
		}
	}
	
	public static function logout(){
		auth::logout();
		self::redirect('');
	}
	
	public static function export(){
		$converter = new converter();
		foreach(config::get('exports') as $export){
			foreach(config::get('languages') as $lang){
				$output = $converter->save($export['adapter'], translations::getTree(true, 0, "language = '{$lang}' OR language IS NULL"));
				$savePath = str_replace("{doc_root}", $_SERVER['DOCUMENT_ROOT'], $export['path']);
				$savePath = str_replace("{lang}", $lang, $savePath);
				file_put_contents($savePath, $output['file']);
			}
		}
		echo 'done';
	}
	
	public static function dump(){
		$converter = new converter();
		$output = $converter->save('json', translations::get());
		header('Content-Type: '.$output['meta']['mime']);
		echo $output['file'];
	}
	
	public static function login(){
		Flight::render('login');
	}
	
	public static function redirect($url){
		header('Location: '.config::get('base').$url);
		exit;
	}
	
	public static function poll(){
		// Close session because PHP will always wait before serving new pages to the same session
		session_write_close();
		for($i = 0; $i < 50; $i++){

			if(count($tablesToUpdate) > 0){
				json_encode(array(
					'time' => time(),
					'status' => true,
					'response' => array()
				));
			}else{
				usleep(500000); // 0.5s
			}
		}
		printJson("Nothing new");
	}
	
	public static function updateDb(){
		foreach(config::get('masters') as $master){
			$urlcontent = file_get_contents($master.'/dump?apicall');
			$dump = json_decode($urlcontent, true);
			translations::delete();
			translations::append($dump);
		}
		if(isset($_GET['bounceback'])){
			self::redirect($_GET['bounceback']);
		}
		self::redirect('');
	}
	
	private static function render($template, $vars = array()){
		Flight::render($template, $vars, 'body_content');
		Flight::render('layout', $vars);
	}
	
	private static function mirror(){
		if(isset($_GET['apicall'])){
			return false;
		}
		$masters = config::get('masters');
		if($masters){
			foreach($masters as $master){
				if(strstr($master, $_SERVER['HTTP_HOST'])){
					throw new Exception("You might be trying to use your own server as master. That is probably not a good idea. ({$master})");
				}
				$response = self::post($master.str_replace(config::get('base'), "/", $_SERVER['REQUEST_URI']).'?apicall=true', $_POST);
				$result = explode("\r\n", $response);
			}
		}
		return true;
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