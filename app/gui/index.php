<?php

require 'php/flight/Flight.php';
require 'php/auth.class.php';
require 'php/translations.class.php';
require 'php/curl.class.php';
require '../config.class.php';
require '../converter/convert.class.php';

require_once '../converter/adapters/csv/parsecsv.lib.php';

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
Flight::route('/download', array('controller','downloadCSV'));
Flight::route('/delete/@keyId/@active', array('controller','deleteKey'));

Flight::route('/poll', array('controller', 'poll'));
Flight::route('/dump', array('controller', 'dump'));
Flight::route('/importCSV', array('controller', 'importCSV'));Flight::route('/update', array('controller', 'updateDb'));
Flight::route('/search', array('controller', 'search'));
Flight::route('/convert', array('controller', 'convertKey'));
Flight::route('/import', array('controller', 'import'));

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

		//Old code: this was used to only load the keys of the csv.
		// $csv = $converter->load('csv', $csvPath);

		/* TODO: deactivated this check because there is not always de and en
		$allGermanTranslations = translations::getTree(true, 0, "language = 'de' OR language IS NULL");
		$allEnglishTranslations = translations::getTree(true, 0, "language = 'en' OR language IS NULL");
		$allPossibleTranslations = self::emptyArrayValues($allGermanTranslations);
		$allEnglishTranslations = array_replace_recursive($allPossibleTranslations, $allEnglishTranslations, $csv);
    $missmatches = array();
    $missmatches = array_merge_recursive($missmatches, self::array_diff_key_recursive($allEnglishTranslations, $allGermanTranslations));
    $missmatches = array_merge_recursive($missmatches, self::array_diff_key_recursive($allGermanTranslations, $allEnglishTranslations));

    if(!empty($missmatches)){
        return $missmatches;
    }
		*/


    $csv = new \parseCSV;
    $csv->linefeed = "\n";
    $csv->delimiter = ";";
    $csv->parse($csvPath);

		$checkCsv = $csv;
		$checkCsvData = $checkCsv->data;

		//Get all languages from the config
		$configLang = config::get('languages');
		sort($configLang);

		//Get all languages from the CSV
		//The CSV is always built the same way: the first column contains the keys
		//the rest of the columns the languages. So we can get all the keys of an entry
		//and delete the first one afterwards (since it contains the keys).
		$csvLang = array_keys($checkCsvData[0]);
		array_shift($csvLang);
		sort($csvLang);

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

		//Check if we truly only have the specified languages in the csv and nothing more or less
		//	If the csv has less languages: warn but import
		//	If the csv has more languages: warn and no import
		if($configLang != $csvLang) {
			if(count($configLang) > count($csvLang)) {
				$warnings[] = 'Import was successful<br>WARNING: The config has defined ' . (count($configLang)-count($csvLang)) . ' more language(s) than the csv provides';
			} else {
				$additionalCsvLang = array_diff($csvLang, $configLang);
				$critMoreLang[] = 'CRITICAL ERROR: The CSV contains more languages than the config has defined: ' . implode(', ', $additionalCsvLang);
			}
		}

		//Get all key-value combos from the DB
		$dbData = translations::get(array(), array('key'));

		//Get all key-value combos for each language from the csv
		//Format should be:
		//key: Test			de: test		en: test
		//Key cannot be same in same folder

		$entryKey;
		$index = 2;
		//Prepare data for sorting and save the row of the entries directly into their array
		//so we can return a reference to the row in case of an error
		foreach($checkCsvData as $key => $row) {
			$entryKey[$key] = $row['key'];
			$checkCsvData[$key]['row'] = $index;
			$index++;
		}

		//Sort all the data
		array_multisort($entryKey, SORT_ASC, $checkCsvData);

		//Remember path to current key
		$currentPath = array();

		$csvDataTransformed = array();
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
		// $commonPath		holds the 'common path' in currentPath that's also in newPath
		foreach($checkCsvData as $key => $row) {

			$newPath = array();
			$newPath = explode('.', $row['key']);


			if($currentPath === $newPath) {
				foreach($checkCsvData as $ro) {
					//Search duplicate
					if(($ro['key'] === implode('.', $currentPath)) && $row['row'] !== $ro['row']) {
						$dupliRow = $ro['row'];
					}
				}
				$critDuplicate[] = 'CRITICAL ERROR: The key "' . $row['key'] . '" on row ' . $row['row'] . ' and on row ' . $dupliRow . ' is a duplicate.';
			} else {
				//Test if key-parent-id-language already exists in DB when trying to create a new folder in same hierarchy
				//Get the intersection of the currentPath and the newPath
				//If the intersection is equal to the currentPath we know that the new path
				//is invalid, since a key-value-pair in a folder cannot be at the same time be a 'subfolder'
				$commonPath = array();
				for($i=0; $i < count($currentPath); $i++) {
					if($currentPath[$i] === $newPath[$i]) $commonPath[] = $currentPath[$i];
				}
				if($commonPath && $commonPath == $currentPath) {
					$oldKey = implode('.', $currentPath);
					$oldRow;
					foreach($checkCsvData as $r) {
						if($r['key'] === $oldKey) $oldRow = $r['row'];
					}
					$critFolder[] = 'CRITICAL ERROR: The folder "' . $row['key'] . '" on row ' . $row['row'] . ' is in a folder that is already present as key: ' . implode('.', $currentPath) . ' on row ' . $oldRow;
				}
				$currentPath = $newPath;
			}

			//	Test that all keys from the csv are valid
			//	Invalid are keys that contain no dot (this would be root-folders) or
			//	who contain a space.
			if(!preg_match("/^([a-zA-Z0-9]{2,})(\.[a-zA-Z0-9]+)+$/", $row['key'])) {
				$critInvalidFormat[] = 'CRITICAL ERROR: The key "' . $row['key'] . '" on row ' . $row['row'] . ' has an invalid format.';
				$invalidFormat[] = $row['key'];
			};
			//	Get all keys from the csv without values
			foreach($configLang as $i => $l) {
				if(array_key_exists($l, $row) && !$row[$l]) {
					$critEmptyVal[] = 'CRITICAL ERROR: The key "' . $row['key'] . '" on row ' . $row['row'] . ' for the language "' . $l . '" is empty.';
				}
			}

			//Save values to an array where the key is the path
			//and the element an array containing only the values for
			//the different languages.
			foreach($row as $k => $v) {
				//Do not row-number or key to the new array
				if($k === 'row' || $k === 'key') continue;
				$csvDataTransformed[$row['key']][$k] = $row[$k];
			}
			$csvKeys[] = $row['key'];
		}

		//Prepare the dbData to have the same structure as the csv-data so we can later
		//easily compare the two datasets.
		//We create a new array whose index of the elements is the same as the id
		//of the db-entries.
		$dbDataIndexed = array();
		foreach($dbData as $k => $v) {
			$dbDataIndexed[$v['id']] = $v;
		}
		$dbDataTransformed = array();
		$dbKeys = array();

		//Loop through the indexed Array with DB-Data.
		foreach($dbDataIndexed as $k => $v) {
			//If the value of the element is null we are in a folder and have to do nothing.
			if($v['value'] === null) continue;

			//Store the key of the element in the path.
			$path = $v['key'];

			//If the parent_id is bigger than 0 we are in a single entry
			if($v['parent_id'] > 0) {
				//Get the 'parent-element' of the entry
				$el = $dbDataIndexed[$v['parent_id']];
				//While the element has a parent prepend the key of the parent-element
				//to the path and save the parent-element as the new element
				while($el['parent_id'] > 0) {
					$path = $el['key'] . '.' . $path;
					$el = $dbDataIndexed[$el['parent_id']];
				}
				//Prepend the key of the last element (that is the root folder)
				$path = $el['key'] . '.' . $path;
			}
			//Store the whole element in the array, with the path as the key.
			$dbDataTransformed[$path][$v['language']] = $v['value'];
			if(!in_array($path, $dbKeys)) $dbKeys[] = $path;
		}

		//Get all errors
		//	Get all keys that are in the DB but are missing in the csv
		$inDbNotInCsv = array_diff($dbKeys, $csvKeys);
		if(count($inDbNotInCsv) > 0) {
			$warning[] = 'WARNING: The following keys are stored in the DB but not provided in the CSV: ' . implode('<br>', $inDbNotInCsv);
		}
		//	Get all keys that are in the csv but not in the DB
		//	Only run this check if the user didn't explicitly wants to import new
		//	values of the csv into the DB
		if(!$importValuesInCsvNotInDb) {
			$inCsvNotInDb = array_diff($csvKeys, $dbKeys);
			$inCsvNotInDb = array_diff($inCsvNotInDb, $invalidFormat);
			if(count($inCsvNotInDb) > 0) {
				$critInCsvNotInDb[] = 'CRITICAL ERROR: The following keys are provided in the CSV but not found in the DB: <br>' . implode('<br>', $inCsvNotInDb);
			}
		}

		//Combine all critical errors in one array.
		$critical['tooManyLangErrors'] = $critMoreLang;
		$critical['duplicateErrors'] = $critDuplicate;
		$critical['folderIsFileErrors'] = $critFolder;
		$critical['invalidFormatErrors'] = $critInvalidFormat;
		$critical['emptyValueErrors'] = $critEmptyVal;
		$critical['inCsvNotInDbErrors'] = $critInCsvNotInDb;

		//If there are critical errors return them and abort the import.
		foreach($critical as $err) {
			if(count($err) > 0) return $critical;
		}

    foreach($csv->data as $row){
      foreach(config::get('languages') as $lang){
        if(isset($row[$lang])){
          self::insertDotDelimitedKeyValue($row['key'], $row[$lang], $lang, true);
        }
      }
    }
		//If there are any warnings return them
		if(count($warnings) > 0) return $warnings;
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

  public static function import(){
    $imported = false;
    $conflicts = array();
    if(isset($_FILES['csv']['name'])){
			$importValuesInCsvNotInDb = isset($_POST['importValuesInCsvNotInDb'])? $_POST['importValuesInCsvNotInDb'] : false;
      $conflicts = self::importCSV($_FILES['csv']['tmp_name'], $importValuesInCsvNotInDb);
      $imported = true;
    }
    self::render('import', array('imported' => $imported, 'conflicts' => $conflicts, 'active' => 0));
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
