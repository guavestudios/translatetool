<?php

require_once(dirname(__FILE__) . '/php/flight/Flight.php');
require_once(dirname(__FILE__) . '/php/auth.class.php');
require_once(dirname(__FILE__) . '/php/translations.class.php');
require_once(dirname(__FILE__) . '/php/curl.class.php');
require_once(dirname(__FILE__) . '/../config.class.php');
require_once(dirname(__FILE__) . '/../converter/convert.class.php');

require_once(dirname(__FILE__) . '/../converter/adapters/csv/parsecsv.lib.php');
require_once(dirname(__FILE__) . '/php/validator.class.php');

use Guave\translatetool\converter;
use Guave\translatetool\Validator as Validator;

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
Flight::route('/download', array('controller','downloadCSV'));
Flight::route('/delete/@keyId/@active', array('controller','deleteKey'));

Flight::route('/poll', array('controller', 'poll'));
Flight::route('/dump', array('controller', 'dump'));
Flight::route('/importCSV', array('controller', 'importCSV'));Flight::route('/update', array('controller', 'updateDb'));
Flight::route('/search', array('controller', 'search'));
Flight::route('/convert', array('controller', 'convertKey'));
Flight::route('/import', array('controller', 'import'));
Flight::route('/nottranslated', array('controller', 'nottranslated'));

Flight::route('/widget/update', array('widgetController', 'update'));

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

			//handle basic duplicates in view
			$duplicate=false;
			for ($i=$k-1; $i>=0; $i--) {
				if ($_POST['key'][$i] == $key && $_POST['language'][$i] == $language) {
					$duplicate = true;
					break;
				}
			}
			if ($duplicate) {
				continue;
			}

			// Can't use empty() on value and key because then '0' can't be used as value
			if(empty($id) and $value == '' and $key == ''){
				continue;
			}
			if(empty($id) and $value !== '' and $key !== ''){
				$entries[] = array(
					'parent_id' => $keyId,
					'key' => $key,
					'value' => $value,
					'language' => $language
				);
			}
			if(!empty($id) and $value !== '' and $key !== ''){
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

	public static function export($addInlineToken = false){
        self::exportRaw(isset($_GET['token']));
	}

    public static function exportRaw($addInlineToken){
        $converter = new converter();
        foreach(config::get('exports') as $export){
            foreach(config::get('languages') as $lang){
                $exportPlain = true;
                if(isset($export['raw']) and $export['raw'] == 'true'){
                    $exportPlain = false;
                }
                $translations = translations::getTree($exportPlain, 0, "language = '{$lang}' OR language IS NULL");
                if($addInlineToken){
                    $translations = self::addInlineToken($translations);
                }
                $output = $converter->save($export['adapter'], $translations);
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
        //echo 'done';
    }

    private static function addInlineToken($array, $prevKey = ''){
        foreach($array as $key => $value){
            $currentKey = (empty($prevKey) ? $key : $prevKey.'.'.$key);
            if(is_array($value)){
                $array[$key] = self::addInlineToken($value, $currentKey);
            }else if(is_string($value)){
                $array[$key] = $value.'<span class="translatetool-phrase" style="display:none" data-key="'.$currentKey.'">'.$value.'</span>';
            }
        }
        return $array;
    }

    public static function downloadCSV(){
        self::export();
        $downloadConfig = config::get('export_download');
        if(empty($downloadConfig)){
            die('No download configured');
        }
        list($type, $key) = explode(":", $downloadConfig);
        $tmp = config::get($type);
        $downloadInfo = $tmp[$key];
        $path = str_replace("{doc_root}", $_SERVER['DOCUMENT_ROOT'], $downloadInfo['path']);
        header("Content-Type: text/csv");
        header("Content-disposition: attachment; filename=\"".basename($path)."\"");
				header('Content-Length: ' . filesize($path));
        readfile($path);
    }

	public static function dump(){
		$converter = new converter();
		$output = $converter->save('json', translations::get());
		header('Content-Type: '.$output['meta']['mime']);
		echo $output['file'];
	}

	public static function convertKey(){
		$adapter = $_POST['adapter']?$_POST['adapter']:config::get('export_key_adapter');
		$error = "";
		$output = "";

		$value = $_POST['value'];
		$key = $_POST['key'];
		$format = $_GET['format'];


		if(empty($adapter)){
			$error = array("message" => 'The Translatetool needs to have the value in "export_key_adapter" set.');
		} else if (empty($value)) {
			$error = array("message" => 'No value was provided');
		} else if (empty($key)) {
			$error = array("message" => 'No key was provided');
		} else if (preg_match("/^([a-zA-Z0-9]{2,})(\.[a-zA-Z0-9]+)+$/", $key)==false) {
			$error = array("message" => 'Key has the wrong format');
		}

		if (empty($error)) {
			$result = self::insertDotDelimitedKeyValue($key, $value);
			$converter = new converter();

			try {
				$output = $converter->getKeyFormated($adapter, $key, $value);

				if (!empty($result)) {
					$error=array(
						"message" => "Key existiert bereits...",
						"key" => $key,
						"keyFormatted" => $output
					);
				}
			} catch (\Exception $ex) {
				$error= array(
					"message" => "adapter did not support request",
					"error" => $ex->getMessage()
				);
			}
		}

		if ($format=="json") {
			header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
			header('Content-Type: application/json');
			echo json_encode(array(
				"status"=>empty($error),
				"response"=>empty($error)?$output:$error
			));
		} else {
			//obsolte: legacy code (text output)
			header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
			echo empty($error)?$output:$error["message"];
		}
	}

    public static function insertDotDelimitedKeyValue($key, $value, $lang = null, $replace = false){
        if(!$lang){
            $languages = config::get('languages');
            $lang = $languages[0];
        }
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
        }else if($replace){
            translations::update($result[0]['id'],
                array(
                    'key' => $endKey,
                    'parent_id' => $parentId,
                    'language' => $lang,
                    'value' => $value
                )
            );
        }else{
            return array(
							"keyDoesNotExistError" => array(
								'CRITICAL ERROR: The following key does not exist in the DB "' . $key . '" language ' . $lang
							)
						);
        }
    }

	public static function importCSV($csvPath = null, $importValuesInCsvNotInDb = true){
		$converter = new converter();
    if($csvPath === null or !file_exists($csvPath)){
      $csvPath = $_SERVER['DOCUMENT_ROOT'].config::get('base').'castle_trans.csv';
    }

		//Get all data from the csv-to-import
    $csv = $converter->load('csv', $csvPath);
		$checkCsvData = $csv;

		//Get all languages from the config
		$configLang = Validator::getLangsFromConfig();

		//Get all languages from the CSV
		$csvLang = Validator::getLangsFromData($checkCsvData[0]);

		//Prepare arrays to hold all warnings and critical errors
		$warnings = array();
		$critical = array();

		//Separate containers for critical errors, so all errors of the same type
		//are in one container.
		$critMoreLang = array();
		$critDuplicate = array();
		$critFolder = array();
		$critInvalidFormat = array();
		$critEmptyVal = array();
		$critInCsvNotInDb = array();

		//Check if there are the same languages in the csv as in the config defined,
		//else an error-message is stored to the appropriate array.
		Validator::checkConfigLangVsDataLang($configLang, $csvLang, $warnMoreLangInConfig, $critMoreLang);

		//Get all key-value combos from the DB
		$dbData = translations::get(array(), array('key'));

		//Prepare data for sorting and save the row of the entries directly into their array
		//so we can return a reference to the row in case of an error
		Validator::addRowNumberToCsvData($checkCsvData);

		//Get all keys from the csvData then sort the csvData with the help of those keys.
		$entryKey = Validator::getAllKeys($checkCsvData);
		array_multisort($entryKey, SORT_ASC, $checkCsvData);

		//Remember path to current key
		$currentPath = array();

		//Prepare an array to hold all the keys that are in the csv.
		$csvKeys = array();

		//Holds all the keys with an invalid format
		$invalidFormat = array();

		// Loop through all entries/rows and test for
		// * duplicates
		// * invalid keynames
		// * empty values
		//
		// $currentPath 	holds the path to the last csv-item
		// $newPath				holds the path to the next/current csv-item
		foreach($checkCsvData as $key => $row) {

			$newPath = array();
			$newPath = explode('.', $row['key']);

			Validator::checkForDuplicates($row, $newPath, $currentPath, $checkCsvData, $critDuplicate);
			$currentPath = Validator::checkForFolderIsFile($row, $newPath, $currentPath, $checkCsvData, $critFolder);

			$invalidFormat[] = Validator::checkInvalidFormat($row, $critInvalidFormat);

			Validator::checkEmptyValuesInData($configLang, $row, $critEmptyVal);

			$csvKeys[] = $row['key'];
		}

		$dbDataIndexed = Validator::getIndexedDbData($dbData);
		$dbKeys = Validator::getKeysInDb($dbDataIndexed);

		//	Get all keys that are in the DB but are missing in the csv
		Validator::checkInDbNotInData($dbKeys, $csvKeys, $warnInDbNotInCsv);

		Validator::checkInDataNotInDb($importValuesInCsvNotInDb, $csvKeys, $dbKeys, $invalidFormat, $critInCsvNotInDb);

		//Combine all critical errors in one array.
		$critical['tooManyLangErrors'] = $critMoreLang;
		$critical['duplicateErrors'] = $critDuplicate;
		$critical['folderIsFileErrors'] = $critFolder;
		$critical['invalidFormatErrors'] = $critInvalidFormat;
		$critical['emptyValueErrors'] = $critEmptyVal;
		$critical['inCsvNotInDbErrors'] = $critInCsvNotInDb;

		$warnings['inDbNotInCsvWarning'] = $warnInDbNotInCsv;
		$warnings['moreLangInConfigWarning'] = $warnMoreLangInConfig;

		$conflicts = array();
		$conflicts['criticalErrors'] = $critical;
		$conflicts['warnings'] = $warnings;

		//If there are critical errors return them and abort the import.
		foreach($critical as $err) {
			if(count($err) > 0) {
				return $conflicts;
			}
		}

    foreach($csv as $row){
      foreach(config::get('languages') as $lang){
        if(isset($row[$lang])){
          self::insertDotDelimitedKeyValue($row['key'], $row[$lang], $lang, true);
        }
      }
    }
		//If there are any warnings return them
		if(count($warnings) > 0) return $conflicts;
		return array();
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

	protected static function countTwoDimensionalArray($array) {
		$count = 0;
		foreach($array as $a) {
			$count += count($a);
		}
		return $count;
	}

  public static function import(){
    $imported = false;
    $conflicts['criticalErrors'] = array();
		$conflicts['warnings'] = array();
		$countErrors = 0;
		$countWarnings = 0;
    if(isset($_FILES['csv']['name'])){
			$importValuesInCsvNotInDb = isset($_POST['importValuesInCsvNotInDb'])? $_POST['importValuesInCsvNotInDb'] : false;
      $conflicts = self::importCSV($_FILES['csv']['tmp_name'], $importValuesInCsvNotInDb);
      $imported = true;
			$countErrors = self::countTwoDimensionalArray($conflicts['criticalErrors']);
			$countWarnings = self::countTwoDimensionalArray($conflicts['warnings']);
    }
		self::render('import', array('imported' => $imported, 'countErrors' => $countErrors, 'countWarnings' => $countWarnings, 'errors' => $conflicts['criticalErrors'], 'warnings' => $conflicts['warnings'], 'active' => 0));
  }

	/**
	 * route
	 */
	public static function nottranslated() {
		$langs = config::get('languages');
		$dbData = translations::get(array(), array('key'));

		//prepare a map for every entry_id to its keypath
		$keymap = array();
		self::fillKeytree($dbData, $keymap);

		//loop through data and unify language to keypath
		$data = array();
		foreach($dbData as $entry) {
			//skip the root (language)
			if ($entry["parent_id"] == 0)
				continue;

			$key = $keymap[$entry["parent_id"]].'.'.$entry["key"];
			$data[$key][$entry['language']] = $entry;
		}

		//now we loop through all keypaths and check against the language count
		$missing = array();
		foreach($data as $k => $entrylang) {
			if (count($entrylang)<count($langs)) {
				//there is one or more translations missing so we add them to the missings
				$entry = reset($entrylang);
				$miss = array(
					'key' => $k,
					'folder_id' => $entry['parent_id'],
					'folder_name' => $keymap[$entry['parent_id']],
					'languages' => array()
				);

				//add every missing language to the entry
				foreach($langs as $lang) {
					if (empty($entrylang[$lang])) {
						$miss['languages'][]=$lang;
					}
				}

				//finally add this to the missing array
				$missing[] = $miss;
			}
		}

		self::render('nottranslated', array(
			'keys' => $missing
		));
	}

	/**
	 * Maps the id of an element to its keypath
	 *
	 * fills $output with a
	 * assoc array $array[entry_id] = keypath
	 *
	 * @param  array $dbArr  database input array
	 * @param  array $output reference to output
	 */
	private static function fillKeytree(&$dbArr, &$output) {

		foreach ($dbArr as &$entry) {
			if (empty($output[$entry["id"]])) {
				$key = $entry["key"];
				$pid = $entry["parent_id"];
				while ($pid != 0) {
					foreach ($dbArr as &$search) {
						if ($search["id"] == $pid) {
							$key = $search["key"].'.'.$key;
							$pid = $search["parent_id"];
						}
					}
				}
				$output[$entry["id"]] = $key;
			}
		}
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

class widgetController{
    public static function update(){
        header('Access-Control-Allow-Origin: *');
        if(!isset($_POST['keys']) or !isset($_POST['language'])){
            die('no input');
        }
        $values = json_decode($_POST['keys'], true);
        $lang = $_POST['language'];
        foreach($values as $key => $value){
            controller::insertDotDelimitedKeyValue($key, $value, $lang, true);
        }
        echo 'done';
    }
}

Flight::start();
