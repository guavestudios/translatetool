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
Flight::route('/importCSV', array('controller', 'importCSV'));Flight::route('/update', array('controller', 'updateDb'));
Flight::route('/search', array('controller', 'search'));
Flight::route('/convert', array('controller', 'convertKey'));
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
				$exportPlain = true;
				if(isset($export['raw']) and $export['raw'] == 'true'){
					$exportPlain = false;
				}
				$output = $converter->save($export['adapter'], translations::getTree($exportPlain, 0, "language = '{$lang}' OR language IS NULL"));
				$savePath = str_replace("{doc_root}", $_SERVER['DOCUMENT_ROOT'], $export['path']);
				$savePath = str_replace("{lang}", $lang, $savePath);
				file_put_contents($savePath, $output['file']);
			}
		}
        if(config::get('exports_combined')){
            foreach(config::get('exports_combined') as $export) {
                $outputs = array();
                foreach(config::get('languages') as $lang) {
                    $exportPlain = true;
                    if(isset($export['raw']) and $export['raw'] == 'true') {
                        $exportPlain = false;
                    }
                    $outputs[$lang] = translations::getTree($exportPlain, 0, "language = '{$lang}' OR language IS NULL");
                }
                $output = $converter->save($export['adapter'], $outputs);
                $savePath = str_replace("{doc_root}", $_SERVER['DOCUMENT_ROOT'], $export['path']);
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
	
	public static function convertKey(){
		$adapter = config::get('export_key_adapter');
		if(empty($adapter)){
			echo 'The Translatetool needs to have the value in "export_key_adapter" set.';
			exit;
		}
		$languages = config::get('languages');
		$lang = $languages[0];
		$value = $_POST['value'];
		$key = $_POST['key'];
		$keys = explode(".", $key);
		$endKey = end($keys);
		$parentId = translations::getParentIdForDotDelimitedKey($key, $lang);
		$result = translations::get(array(), array(), "key = '{$endKey}' AND language = '{$lang}' AND parent_id = {$parentId}");
		if(empty($result)){
			translations::append(array(
				array(
					'key' => $endKey,
					'parent_id' => $parentId,
					'language' => $lang,
					'value' => $value
				)
			));
		}else{
			die('Key existiert bereits...');
		}
		$converter = new converter();
		echo $converter->getKeyFormated($adapter, $key, $value);
		exit;
	}
	
	public static function importCSV(){
		$converter = new converter();
		$csv = $converter->load('csv', $_SERVER['DOCUMENT_ROOT'].'/castle_trans.csv');
		$allGermanTranslations = translations::getTree(true, 0, "language = 'de' OR language IS NULL");
		$allEnglishTranslations = translations::getTree(true, 0, "language = 'en' OR language IS NULL");
		$allPossibleTranslations = self::emptyArrayValues($allGermanTranslations);
		//var_dump(self::array_diff_key_recursive($csv, $allGermanTranslations));exit;
		$allEnglishTranslations = array_replace_recursive($allPossibleTranslations, $allEnglishTranslations, $csv);
		/*
		var_dump(self::array_diff_key_recursive($allEnglishTranslations, $allGermanTranslations));
		var_dump(self::array_diff_key_recursive($allGermanTranslations, $allEnglishTranslations));exit;
		*/
		$de = $converter->save('csv', $allGermanTranslations);
		$en = $converter->save('csv', $allEnglishTranslations);
		file_put_contents($_SERVER['DOCUMENT_ROOT'].'/castle_german_export.csv', $de['file']);
		file_put_contents($_SERVER['DOCUMENT_ROOT'].'/castle_english_export.csv', $en['file']);
		echo 'done';
	}
	
	private static function array_diff_key_recursive(array $arr1, array $arr2) {
		$diff = array_diff_key($arr1, $arr2);
		$intersect = array_intersect_key($arr1, $arr2);
		
		foreach ($intersect as $k => $v) {
			if (is_array($arr1[$k]) && is_array($arr2[$k])) {
				$d = self::array_diff_key_recursive($arr1[$k], $arr2[$k]);
				
				if ($d) {
					$diff[$k] = $d;
				}
			}
		}
		
		return $diff;
	}
	
	private static function emptyArrayValues(array $array){
		$newArray = array();
		foreach($array as $key => $value){
			if(is_array($value)){
				$newArray[$key] = self::emptyArrayValues($value);
			}else{
				$newArray[$key] = '';
			}
		}
		return $newArray;
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
		self::export();
		if(isset($_GET['bounceback'])){
			self::redirect($_GET['bounceback']);
		}
		self::redirect('');
	}
	
	public static function search(){
		$searchString = $_POST['search'];
		$results = translations::searchForKey($searchString);
		self::render('search', array('results' => $results, 'active' => 0));
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