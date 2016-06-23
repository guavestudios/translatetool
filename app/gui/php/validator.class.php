<?php


namespace Guave\translatetool;
require_once(dirname(__FILE__) . '/../../config.class.php');


class Validator {

	/**
	* Get all languages from the config as a sorted Array.
	*
	* @return Array   Contains all the languages of the config (sorted).
	*/
	public static function getLangsFromConfig() {
		//Get all languages from the config
		$configLang = \config::get('languages');
		sort($configLang);
		return $configLang;
	}

	/**
	* Gets all languages from the passed data.
	* The data has to be built the following way: the first column contains the keys
	* the rest of the columns the languages. So we can get all the keys of an entry
	* and delete the first one afterwards (since it contains the keys).
	*
	* @param  Array $data   		Contains all the data
	* @return Array							Contains the languages present in the data (sorted).
	*/
	public static function getLangsFromData($data) {
		$langs = array_keys($data);
		array_shift($langs);
		sort($langs);
		return $langs;
	}

	/**
	* Checks the count of the languages defined in the config-file vs the languages
	* defined in the data.
	* If the data has less languages: warn but import
	* If the data has more languages: warn and no import
	*
	* @param  Array  $configLang									Contains all languages found in the config.
	* @param  Array  $dataLang        						Contains all languages found in the data.
	* @param  Array  &$warnMoreLangInConfig      	Reference to the array, that's passed warnings
	*                                             if some would occur during function execution.
	* @param  Array  &$critMoreLang  							Reference to the array that's passed critical
	*                                      				errors if some are encountered during function execution.
	*/
	public static function checkConfigLangVsDataLang($configLang, $dataLang, &$warnMoreLangInConfig, &$critMoreLang) {
		if($configLang != $dataLang) {
			if(count($configLang) > count($dataLang)) {
				$warnMoreLangInConfig[] = 'WARNING: The config has defined ' . (count($configLang)-count($dataLang)) . ' more language(s) than the data provides';
			} else {
				$additionalDataLang = array_diff($dataLang, $configLang);
				$critMoreLang[] = 'CRITICAL ERROR: The data contains more languages than the config has defined: ' . implode(', ', $additionalDataLang);
			}
		}
	}

	/**
	* Adds to each entry of the passed csv-Data the row-number on which the data
	* occurs in the csv.
	* The index defines the row to start - since the csv-import in this project
	* "cuts off" the head-row, the first entry in the csvData-Array is already the
	* second line in the actual csv - that's why $index is usually 2.
	*
	* @param Array  $csvData  Reference to the array containing all data from the csv.
	* @param Number $index    Number of first row.
	*/
	public static function addRowNumberToCsvData(&$csvData, $index=2) {
		foreach($csvData as $key => $row) {
			$csvData[$key]['row'] = $index;
			$index++;
		}
	}

	/**
	* Gets all keys of the data.
	*
	* @param  Array  $data  	Contains all csvData to get the keys from.
	* @return Array          	Contains all keys from the csv.
	*/
	public static function getAllKeys($data) {
		$keys = array();
		foreach($data as $key => $row) {
			$keys[$key] = $row['key'];
		}
		return $keys;
	}

	/**
	* In case newPath === currentPath then this functions searches the passed data-Array
	* for duplicates and saves all relevant information into the passed 'error-array'.
	*
	* @param	Array	 $row							Contains all the data of the current row.
	* @param  String $newPath       	Contains the path to the current element.
	* @param  String $oldPath		   		Contains the path to the element that was looped.
	*                                	over before the current one.
	* @param  Array  $data       			Contains all data to look for duplicates.
	* @param  Array  $critDuplicate 	Contains the error-messages (if any) when duplicates are present.
	*/
	public static function checkForDuplicates($row, $newPath, $oldPath, $data, &$critDuplicate) {
		if($oldPath !== $newPath) return;
		if(isset($row['row'])) {
			$critDuplicate[$row['row']] = 'CRITICAL ERROR: The key "' . $row['key'] . '" on row ' . $row['row'] . ' is a duplicate.';
			foreach($data as $ro) {
				//Search duplicate
				if(($ro['key'] === implode('.', $oldPath)) && $row['row'] !== $ro['row']) {
					$critDuplicate[$ro['row']] = 'CRITICAL ERROR: The key "' . $row['key'] . '" on row ' . $ro['row'] . ' is a duplicate.';
				}
			}
		} else {
			$critDuplicate[$row['row']] = 'CRITICAL ERROR: The key "' . $row['key'] . ' is a duplicate.';
		}
	}

	/**
	* Test if key-parent-id-language already exists in DB when trying to create a new folder in same hierarchy
	* Get the intersection of the currentPath and the newPath
	* If the intersection is equal to the currentPath we know that the new path
	* is invalid, since a key-value-pair in a folder cannot be at the same time be a 'subfolder'
	*
	* IMPORTANT: The passed csvData has to be sorted, otherwise this function will fail.
	* This function checks the path of the current element with the path of the element
	* that came before it. If the 'common path' of the two is equal to the 'old path'
	* we know that the current element is a folder that should not exist since in the sorted
	* data all files with a name equal to a subfolder are right before those folders:
	* test.testfile
	* test.testfile.invalidsubfolder
	*
	* @param	Array		$row				 	Contains all the data of the current row.
	* @param  String 	$newPath     	Contains the path to the current element.
	* @param  String 	$oldPath     	Contains the path to the element looped over before
	*                               current one.
	* @param  Array	 	$data     		Contains all data of the csv-file.
	* @param  Array		$critFolder  	Contains the error-messages (if any) when folder-names
	*                               are alreay present as entry-names.
	* @return String								The path to the current element.
	*/
	public static function checkForFolderIsFile($row, $newPath, $oldPath, $data, &$critFolder) {
		if($oldPath === $newPath) return;

		$commonPath = array();

		for($i=0; $i < count($oldPath); $i++) {
			if(isset($oldPath[$i]) && isset($newPath[$i]) && $oldPath[$i] === $newPath[$i]) $commonPath[] = $oldPath[$i];
		}

		if($commonPath && $commonPath == $oldPath) {
			$oldKey = implode('.', $oldPath);
			if(isset($row['row'])) {
				$oldRow;
				foreach($data as $r) {
					if($r['key'] === $oldKey) $oldRow = $r['row'];
				}
				$critFolder[] = 'CRITICAL ERROR: The folder "' . $row['key'] . '" on row ' . $row['row'] . ' is in a folder that is already present as key: ' . implode('.', $oldPath) . ' on row ' . $oldRow;
			} else {
				$critFolder[] = 'CRITICAL ERROR: The folder "' . $row['key'] . ' is in a folder that is already present as key: ' . implode('.', $oldPath);
			}
		}
		return $newPath;
	}

	/**
	* Test that all keys from the data are valid
	* Invalid are keys that contain no dot (this would be root-folders) or
	* who contain a space.
	*
	* @param  Array	$row              	Contains all data of the current row.
	* @param  Array	$critInvalidFormat	Contains the error-messages (if any) when key-names
	*                                  	have an invalid format.
	* @return String										The key of the row with the invalid format.
	*/
	public static function checkInvalidFormat($row, &$critInvalidFormat) {
		if(!preg_match("/^([a-zA-Z0-9]{2,})(\.[a-zA-Z0-9]+)+$/", $row['key'])) {
			if(isset($row['row'])) {
				$critInvalidFormat[] = 'CRITICAL ERROR: The key "' . $row['key'] . '" on row ' . $row['row'] . ' has an invalid format.';
			} else {
				$critInvalidFormat[] = 'CRITICAL ERROR: The key "' . $row['key'] . ' has an invalid format.';
			}
			return $row['key'];
		};
	}

	/**
	* Checks if any values in the data are empty, that is if only the keys but no
	* values are provided.
	*
	* @param  Array	$configLang   Contains all languages that are defined in the config.
	* @param  Array	$row          Contains all data of the row to check on empty values.
	* @param  Array	$critEmptyVal Contains the error-messages (if any) when values
	*                              are empty.
	*/
	public static function checkEmptyValuesInData($configLang, $row, &$critEmptyVal) {
		foreach($configLang as $i => $l) {
			if(array_key_exists($l, $row) && !$row[$l]) {
				if(isset($row['row'])) {
					$critEmptyVal[] = 'CRITICAL ERROR: The key "' . $row['key'] . '" on row ' . $row['row'] . ' for the language "' . $l . '" is empty.';
				} else {
					$critEmptyVal[] = 'CRITICAL ERROR: The key "' . $row['key'] . ' for the language "' . $l . '" is empty.';
				}
			}
		}
	}

	/**
	* Gets all keys that are in the DB. The keys will be in the format folder.folder.key
	*
	* @param  Array	$dbDataIndexed	Contains all DB-Data in an indexed form. That means
	*                              	a db-Item with db-id 1 is on position 1 in the array,
	*                                one with the id 2 is on position 2 and so on.
	* @return Array                	Contains all keys that are present in the db.
	*/
	public static function getKeysInDb($dbDataIndexed) {
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
			if(!in_array($path, $dbKeys)) $dbKeys[] = $path;
		}

		return $dbKeys;
	}

	/**
	* Get all keys that are in the data but not in the DB
	* Only run this function if the user didn't explicitly wants to import new
	* values of the data into the DB
	*
	* @param  Boolean	$importValuesInDataNotInDb	Indicates if the user wants to import
	*                                            	values only present in the data or not.
	* @param  Array		$dataKeys                  	Contains all keys found in the data.
	* @param  Array		$dbKeys                   	Contains all keys found in the db.
	* @param  Array		$invalidFormat            	Contains all keys with an invalid format.
	* @param  Array		$critInDataNotInDb         	Reference to an array, to whom the error-message
	*                                           	is passed if some keys are found in the csv but not in the db.
	*/
	public static function checkInDataNotInDb($importValuesInDataNotInDb, $dataKeys, $dbKeys, $invalidFormat, &$critInDataNotInDb) {
		if(!$importValuesInDataNotInDb) {
			$inDataNotInDb = array_diff($dataKeys, $dbKeys);
			$inDataNotInDb = array_diff($inDataNotInDb, $invalidFormat);
			if(count($inDataNotInDb) > 0) {
				$critInDataNotInDb[] = 'CRITICAL ERROR: The following keys are provided in the CSV but not found in the DB: <br>' . implode('<br>', $inDataNotInDb);
			}
		}
	}

	/**
	* Prepares the dbData to have the same structure as the csv-data so we can later
	* easily compare the two datasets.
	* We create a new array whose index of the elements is the same as the id
	* of the db-entries.
	*
	* @param  Array	$dbData 	Contains all the data of the db.
	* @return Array         		Contains the data of the db in an indexed form, that is
	*                          a db-element with an id of 1 is on position 1 in the returned
	*                          array, one with id 2 is on position 2 and so on.
	*/
	public static function getIndexedDbData($dbData) {
		$dbDataIndexed = array();
		foreach($dbData as $k => $v) {
			$dbDataIndexed[$v['id']] = $v;
		}
		return $dbDataIndexed;
	}

	/**
	* Compares the two passed arrays, finds all values that are found only in the first
	* one and returns an array with containing the information about the values
	* that are found only in the first array.
	*
	* @param  Array	$dbKeys							Contains all Keys found in the DB.
	* @param  Array	$dataKeys						Contains all Keys found in the data.
	* @param	Array	$warnInDbNotInData	Contains warnings if any are present.
	*/
	public static function checkInDbNotInData($dbKeys, $dataKeys, &$warnInDbNotInData) {
		$inDbNotInData = array_diff($dbKeys, $dataKeys);
		if(count($inDbNotInData) > 0) {
			$warnInDbNotInData[] = 'WARNING: The following keys are stored in the DB but not provided in the CSV: <br>' . implode('<br>', $inDbNotInData);
		}
	}

}
