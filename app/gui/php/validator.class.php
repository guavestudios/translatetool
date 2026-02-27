<?php


namespace Guave\translatetool;

require_once(dirname(__FILE__) . '/../../config.class.php');


class Validator
{

	/**
	 * Get all languages from the config as a sorted Array.
	 *
	 * @return Array   Contains all the languages of the config (sorted).
	 */
	public static function getLangsFromConfig()
	{
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
	public static function getLangsFromData($data)
	{
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
	public static function checkConfigLangVsDataLang($configLang, $dataLang, &$warnMoreLangInConfig, &$critMoreLang)
	{
		if ($configLang != $dataLang) {
			if (count($configLang) > count($dataLang)) {
				$warnMoreLangInConfig[] = 'WARNING: The config has defined ' . (count($configLang) - count($dataLang)) . ' more language(s) than the data provides';
			} else {
				$additionalDataLang = array_diff($dataLang, $configLang);
				$critMoreLang[] = 'The data contains more languages than the config has defined: [' . implode(',', $additionalDataLang) . ']';
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
	public static function addRowNumberToCsvData(&$csvData, $index = 2)
	{
		foreach ($csvData as $key => $row) {
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
	public static function getAllKeys($data)
	{
		$keys = array();
		foreach ($data as $key => $row) {
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
	public static function checkForDuplicates($row, $newPath, $oldPath, $data, &$critDuplicate)
	{
		if ($oldPath !== $newPath) return;
		
		if (isset($row['row'])) {
			$critDuplicate[$row['row']] = '"' . $row['key'] . '" (row ' . $row['row'] . ')';
			foreach ($data as $ro) {
				//Search duplicate - fix: use $row['key'] instead of implode('.', $oldPath)
				if (($ro['key'] === $row['key']) && $row['row'] !== $ro['row']) {
					$critDuplicate[$ro['row']] = '"' . $ro['key'] . '" (row ' . $ro['row'] . ')';
				}
			}
		} else {
			// Fix: missing closing quote and ensure proper array key handling
			$key = isset($row['key']) ? $row['key'] : '';
			$critDuplicate[] = '"' . $key;
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
	public static function checkForFolderIsFile($row, $newPath, $oldPath, $data, &$critFolder)
	{
		if ($oldPath === $newPath) return;

		$commonPath = array();

		// Ensure $oldPath and $newPath are arrays
		$oldPathArray = is_array($oldPath) ? $oldPath : ($oldPath === null ? array() : explode('.', (string) $oldPath));
		$newPathArray = is_array($newPath) ? $newPath : ($newPath === null ? array() : explode('.', (string) $newPath));

		for ($i = 0; $i < count($oldPathArray); $i++) {
			if (isset($oldPathArray[$i]) && isset($newPathArray[$i]) && $oldPathArray[$i] === $newPathArray[$i]) $commonPath[] = $oldPathArray[$i];
		}

		if ($commonPath && $commonPath == $oldPath) {
			$oldKey = implode('.', $oldPath);
			if (isset($row['row'])) {
				$oldRow;
				foreach ($data as $r) {
					if ($r['key'] === $oldKey) $oldRow = $r['row'];
				}
				$critFolder[] = 'The folder "' . $row['key'] . '" on row ' . $row['row'] . ' is in a folder that is already present as key: ' . implode('.', $oldPath) . ' on row ' . $oldRow;
			} else {
				$critFolder[] = 'The folder "' . $row['key'] . ' is in a folder that is already present as key: ' . implode('.', $oldPath);
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
	public static function checkInvalidFormat($row, &$critInvalidFormat)
	{
		if (!preg_match("/^([a-zA-Z0-9_-]{2,})(\.[a-zA-Z0-9_-]+)+$/", $row['key'])) {
			$rowInfo = isset($row['row']) ? ' (row ' . $row['row'] . ')' : '';
			$critInvalidFormat[] = '"' . $row['key'] . '"' . $rowInfo;
			return $row['key'];
		}
	}

	/**
	 * Checks if any language cells in the data are empty. Empty cells are skipped
	 * during import (they do not overwrite existing values) and are reported as
	 * warnings rather than blocking errors.
	 *
	 * Uses strict empty-string comparison so that "0" remains a valid translation.
	 *
	 * @param  Array	$configLang    Contains all languages that are defined in the config.
	 * @param  Array	$row           Contains all data of the row to check on empty values.
	 * @param  Array	$warnEmptyVal  Contains the warning messages (if any) when values
	 *                               are empty.
	 */
	public static function checkEmptyValuesInData($configLang, $row, &$warnEmptyVal)
	{
		foreach ($configLang as $l) {
			if (isset($row[$l]) && $row[$l] === '') {
				$rowInfo = isset($row['row']) ? ' on row ' . $row['row'] : '';
				$warnEmptyVal[] = 'Key "' . $row['key'] . '"' . $rowInfo . ' for language "' . $l . '" is empty and will be skipped.';
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
	public static function getKeysInDb($dbDataIndexed)
	{
		$dbKeys = array();

		//Loop through the indexed Array with DB-Data.
		foreach ($dbDataIndexed as $k => $v) {
			//If the value of the element is null we are in a folder and have to do nothing.
			if ($v['value'] === null) continue;

			//Store the key of the element in the path.
			$path = $v['key'];

			//If the parent_id is bigger than 0 we are in a single entry
			if ($v['parent_id'] > 0) {
				//Get the 'parent-element' of the entry
				$el = $dbDataIndexed[$v['parent_id']];
				//While the element has a parent prepend the key of the parent-element
				//to the path and save the parent-element as the new element
				while ($el['parent_id'] > 0) {
					$path = $el['key'] . '.' . $path;
					$el = $dbDataIndexed[$el['parent_id']];
				}
				//Prepend the key of the last element (that is the root folder)
				$path = $el['key'] . '.' . $path;
			}
			if (!in_array($path, $dbKeys)) $dbKeys[] = $path;
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
	public static function checkInDataNotInDb($importValuesInDataNotInDb, $dataKeys, $dbKeys, $invalidFormat, &$critInDataNotInDb)
	{
		if (!$importValuesInDataNotInDb) {
			$inDataNotInDb = array_diff($dataKeys, $dbKeys);
			$inDataNotInDb = array_diff($inDataNotInDb, $invalidFormat);
			foreach ($inDataNotInDb as $key) {
				$critInDataNotInDb[] = 'Key "' . $key . '" is provided in the CSV but not found in the DB.';
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
	public static function getIndexedDbData($dbData)
	{
		$dbDataIndexed = array();
		foreach ($dbData as $k => $v) {
			$dbDataIndexed[$v['id']] = $v;
		}
		return $dbDataIndexed;
	}

	/**
	 * Extract the folder key from a dot-delimited key by removing the last part.
	 *
	 * @param  String $dotDelimitedKey	The full dot-delimited key.
	 * @return String					The folder key without the final part.
	 */
	public static function getFolderKeyFromDotDelimitedKey($dotDelimitedKey)
	{
		$parts = explode('.', $dotDelimitedKey);
		if (count($parts) >= 2) {
			return $parts[count($parts) - 2];
		}
		return '';
	}

	/**
	 * Get the parent ID for a given folder key by traversing the folder hierarchy.
	 *
	 * @param  String $folderKey		The folder key to find the parent ID for.
	 * @param  Array  $dbDataIndexed	Contains all DB-Data in an indexed form.
	 * @return Integer|null			The parent ID, 0 for root, or null if not found.
	 */
	public static function getParentIdForFolderKey($folderKey, $dbDataIndexed)
	{
		if (empty($folderKey)) {
			return 0; // root
		}
		$parts = explode('.', $folderKey);
		$parentId = 0;
		foreach ($parts as $part) {
			$found = false;
			foreach ($dbDataIndexed as $entry) {
				if ($entry['key'] == $part && $entry['parent_id'] == $parentId && is_null($entry['language'])) {
					$parentId = $entry['id'];
					$found = true;
					break;
				}
			}
			if (!$found) {
				return null; // folder not found
			}
		}
		return $parentId;
	}

	/**
	 * Check if the value from CSV matches the existing value in the database
	 * for the given key and language combination.
	 *
	 * @param  String $key           The dot-delimited key to check
	 * @param  String $language      The language code to check
	 * @param  String $csvValue      The value from the CSV
	 * @param  Array  $dbDataIndexed Contains all DB-Data in an indexed form
	 * @return Array                 Contains status and details about the comparison
	 */
	public static function checkValueMatch($key, $language, $csvValue, $dbDataIndexed)
	{
		// Get existing value from database
		$existingValue = self::getExistingValue($key, $language, $dbDataIndexed);

		if ($existingValue === null) {
			return array(
				'status' => 'new',
				'existing_value' => null,
				'csv_value' => $csvValue
			);
		}

		if ($existingValue === $csvValue) {
			return array(
				'status' => 'unchanged',
				'existing_value' => $existingValue,
				'csv_value' => $csvValue
			);
		}

		return array(
			'status' => 'changed',
			'existing_value' => $existingValue,
			'csv_value' => $csvValue
		);
	}

	/**
	 * Get the existing value from the database for a given key and language.
	 *
	 * @param  String $key           The dot-delimited key
	 * @param  String $language      The language code
	 * @param  Array  $dbDataIndexed Contains all DB-Data in an indexed form
	 * @return String|null           The existing value or null if not found
	 */
	private static function getExistingValue($key, $language, $dbDataIndexed)
	{
		$parts = explode('.', $key);
		$keyName = array_pop($parts);
		$folderKey = implode('.', $parts);

		$parentId = self::getParentIdForFolderKey($folderKey, $dbDataIndexed);

		if ($parentId === null) {
			return null; // Folder path doesn't exist
		}

		// Find the entry with matching key, language, and parent_id
		foreach ($dbDataIndexed as $entry) {
			if (
				$entry['key'] === $keyName &&
				$entry['language'] === $language &&
				$entry['parent_id'] === $parentId
			) {
				return $entry['value'];
			}
		}

		return null; // Entry not found
	}

	/**
	 * Batch check multiple CSV rows against database values.
	 *
	 * @param  Array $csvData        Array of CSV rows to check
	 * @param  Array $dbDataIndexed  Contains all DB-Data in an indexed form
	 * @return Array                 Summary of changes, conflicts, and new entries
	 */
	public static function compareCSVWithDatabase($csvData, $dbDataIndexed)
	{
		$results = array(
			'unchanged' => array(),
			'changed' => array(),
			'new' => array(),
			'summary' => array()
		);

		foreach ($csvData as $row) {
			foreach (\config::get('languages') as $lang) {
				if (isset($row[$lang]) && $row[$lang] !== '') {
					$comparison = self::checkValueMatch($row['key'], $lang, $row[$lang], $dbDataIndexed);
					$results[$comparison['status']][] = array_merge($comparison, array(
						'key' => $row['key'],
						'language' => $lang
					));
				}
			}
		}

		$results['summary'] = array(
			'unchanged_count' => count($results['unchanged']),
			'changed_count' => count($results['changed']),
			'new_count' => count($results['new'])
		);

		return $results;
	}
}
