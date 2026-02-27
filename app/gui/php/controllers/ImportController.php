<?php

use Guave\translatetool\converter;
use Guave\translatetool\Validator as Validator;

class ImportController extends BaseController
{
	private static function countTwoDimensionalArray($array)
	{
		$count = 0;
		foreach ($array as $a) {
			$count += count($a);
		}
		return $count;
	}

	public static function validateCSV($csvPath)
	{
		$converter = new converter();
		if (!file_exists($csvPath)) {
			return array('errors' => array('File not found'));
		}

		$csv = $converter->load('csv', $csvPath);
		$checkCsvData = $csv;
		$configLang = Validator::getLangsFromConfig();
		$csvLang = Validator::getLangsFromData($checkCsvData[0]);
		$warnings = array();
		$critical = array();
		$critMoreLang = array();
		$critDuplicate = array();
		$critFolder = array();
		$critInvalidFormat = array();
		$critEmptyVal = array();
		$critInCsvNotInDb = array();

		Validator::checkConfigLangVsDataLang($configLang, $csvLang, $warnMoreLangInConfig, $critMoreLang);
		$dbData = translations::get(array(), array('key'));
		Validator::addRowNumberToCsvData($checkCsvData);
		$entryKey = Validator::getAllKeys($checkCsvData);
		array_multisort($entryKey, SORT_ASC, $checkCsvData);

		$currentPath = array();
		$csvKeys = array();
		$invalidFormat = array();
		foreach ($checkCsvData as $key => $row) {
			$newPath = explode('.', $row['key']);
			Validator::checkForDuplicates($row, $newPath, $currentPath, $checkCsvData, $critDuplicate);
			$currentPath = Validator::checkForFolderIsFile($row, $newPath, $currentPath, $checkCsvData, $critFolder);
			$invalidFormat[] = Validator::checkInvalidFormat($row, $critInvalidFormat);
			Validator::checkEmptyValuesInData($configLang, $row, $critEmptyVal);
			$csvKeys[] = $row['key'];
		}

		$dbDataIndexed = Validator::getIndexedDbData($dbData);
		$dbKeys = Validator::getKeysInDb($dbDataIndexed);
		Validator::checkInDbNotInData($dbKeys, $csvKeys, $warnInDbNotInCsv);
		Validator::checkInDataNotInDb(true, $csvKeys, $dbKeys, $invalidFormat, $critInCsvNotInDb);

		$critical = array(
			'tooManyLangErrors' => $critMoreLang,
			'duplicateErrors' => $critDuplicate,
			'folderIsFileErrors' => $critFolder,
			'invalidFormatErrors' => $critInvalidFormat,
			'emptyValueErrors' => $critEmptyVal,
			'inCsvNotInDbErrors' => $critInCsvNotInDb
		);

		$valueComparison = Validator::compareCSVWithDatabase($csv, $dbDataIndexed);
		return array(
			'errors' => $critical,
			'warnings' => $warnings,
			'value_comparison' => $valueComparison
		);
	}

	public static function performCSVImport($csvPath, $importValuesInCsvNotInDb = false)
	{
		$converter = new converter();
		$csv = $converter->load('csv', $csvPath);

		if (!$importValuesInCsvNotInDb) {
			$dbData = translations::get(array(), array('key'));
			$dbDataIndexed = Validator::getIndexedDbData($dbData);
			$dbKeys = Validator::getKeysInDb($dbDataIndexed);
		}

		foreach ($csv as $row) {
			$rowKey = $row['key'];
			if (!$importValuesInCsvNotInDb && !in_array($rowKey, $dbKeys)) {
				continue;
			}

			foreach (config::get('languages') as $lang) {
				if (isset($row[$lang])) {
					ExportController::insertDotDelimitedKeyValue($row['key'], $row[$lang], $lang, true);
				}
			}
		}
		return array();
	}

	public static function import()
	{
		$errors = array();
		$uploaded = false;

		if (isset($_FILES['csv']['name'])) {
			$uploaded = true;
			$tempFile = sys_get_temp_dir() . '/translatetool_' . session_id() . '.csv';
			move_uploaded_file($_FILES['csv']['tmp_name'], $tempFile);

			$validation = self::validateCSV($tempFile);
			$valueComparison = $validation['value_comparison'];
			$hasErrors = self::countTwoDimensionalArray($validation['errors']) > 0;
			$errors = array();

			if ($hasErrors) {
				foreach ($validation['errors'] as $errorType => $errorList) {
					foreach ($errorList as $error) {
						$errors[] = is_array($error) ? implode(', ', $error) : $error;
					}
				}
			}

			if (!$hasErrors) {
				$_SESSION['pending_csv'] = $tempFile;
			} else {
				unlink($tempFile);
			}
		}

		self::render('import', array(
			'csvData' => isset($valueComparison) ? $valueComparison : array(),
			'errors' => isset($validation) ? $validation['errors'] : $errors,
			'hasErrors' => isset($validation) ? $hasErrors : !empty($errors),
			'uploaded' => $uploaded,
			'active' => 0
		));
	}

	public static function confirmImport()
	{
		if (!isset($_SESSION['pending_csv']) || !file_exists($_SESSION['pending_csv'])) {
			self::redirect('import');
			return;
		}

		$importValuesInCsvNotInDb = isset($_POST['importValuesInCsvNotInDb']) && $_POST['importValuesInCsvNotInDb'] === 'true';
		$conflicts = self::performCSVImport($_SESSION['pending_csv'], $importValuesInCsvNotInDb);
		unlink($_SESSION['pending_csv']);
		unset($_SESSION['pending_csv']);

		self::render('import_result', array(
			'conflicts' => $conflicts,
			'success' => empty($conflicts['criticalErrors']),
			'active' => 0
		));
	}
}
