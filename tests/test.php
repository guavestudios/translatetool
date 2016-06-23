<?php

namespace Guave\translatetool\tests;

require_once(dirname(__FILE__) . '/../app/converter/convert.class.php');
require_once(dirname(__FILE__) . '/../app/config.class.php');
require_once(dirname(__FILE__) . '/../app/gui/php/validator.class.php');
require_once(dirname(__FILE__) . '/../app/gui/php/translations.class.php');


use Guave\translatetool\converter as converter;
use Guave\translatetool\validator as v;
use Guave\translatetool\translations;

$c = new converter();

// CSV

echo "Opening ".__DIR__.'\testdata\01_1key OK.csv';
$data = $c->load('csv', __DIR__.'/testdata/01_1key OK.csv');

TestTranslator::isArray('data', $data);
TestTranslator::isArray('data-entry', $data[0]);
TestTranslator::hasKey('data-entry', $data[0], "key");
TestTranslator::hasKey('data-entry', $data[0], "it");
TestTranslator::hasKey('data-entry', $data[0], "en");

$entry = $data[0];
TestTranslator::isEqual('data-entry key', $entry['key'], 'test.eintrag01');
TestTranslator::isEqual('data-entry it', $entry['it'], 'it-eintrag01');
TestTranslator::isEqual('data-entry en', $entry['en'], 'en-eintrag01');

$configLang = v::getLangsFromConfig();
TestTranslator::isArray('configLang', $configLang);
TestTranslator::isEqual('configLang-entry', $configLang[0], "en");
TestTranslator::isEqual('configLang-entry', $configLang[1], "it");

$dataLang = v::getLangsFromData($data[0]);
TestTranslator::isArray('dataLang', $dataLang);
TestTranslator::isEqual('dataLang-entry', $configLang[0], "en");
TestTranslator::isEqual('dataLang-entry', $configLang[1], "it");

$err  = array();
$warn = array();
$cL = ['en', 'it'];
$dL = ['en', 'it'];
v::checkConfigLangVsDataLang($cL, $dL, $warn, $err);
TestTranslator::isEmpty('warning', $warn);
TestTranslator::isEmpty('error', $err);

$err  = array();
$warn = array();
$cL = ['en', 'it', 'de'];
$dL = ['en', 'it'];
v::checkConfigLangVsDataLang($cL, $dL, $warn, $err);
TestTranslator::isEmpty('errors', $err);
TestTranslator::isNotEmpty('warnings', $warn);
TestTranslator::isEqual('warn-array-length', count($warn), 1);
TestTranslator::isEqual('warning', $warn[0], "WARNING: The config has defined 1 more language(s) than the data provides");

$err  = array();
$warn = array();
$cL = ['en', 'it'];
$dL = ['en', 'it', 'de'];
v::checkConfigLangVsDataLang($cL, $dL, $warn, $err);
TestTranslator::isEmpty('warnings', $warn);
TestTranslator::isNotEmpty('errors', $err);
TestTranslator::isEqual('error-array-length', count($err), 1);
TestTranslator::isEqual('errors', $err[0], "CRITICAL ERROR: The data contains more languages than the config has defined: de");

v::addRowNumberToCsvData($data);
$entry = $data[0];
TestTranslator::hasKey('data-entry', $entry, 'row');

$keys = v::getAllKeys($data);
TestTranslator::isArray('data-key-array', $keys);
TestTranslator::isEqual('data-key-entry', $keys[0], "test.eintrag01");
TestTranslator::isEqual('data-key-entry', $keys[1], "test.eintrag02");

$configlang = v::getLangsFromConfig();

//After every runChecks-passing $warn contains the array: inDbNotInCsvWarning
//errors contains the arrays duplicateErrors, folderIsFileErrors, invalidFormatErrors, emptyValueErrors, inCsvNotInDbErrors
$err = array(); $warn = array();
echo "\n\nfolderIsFileErrors";
runChecks('03_folderIsFileErrors.csv', $configlang, $warn, $err, $c);
TestTranslator::isEmpty('inDbNotInCsvWarning', $warn['inDbNotInCsvWarning']);
TestTranslator::isEmpty('duplicateErrors', $err['duplicateErrors']);
TestTranslator::isNotEmpty('folderIsFileErrors', $err['folderIsFileErrors']);
TestTranslator::isEqual('folderIsFileErrors', $err['folderIsFileErrors'][0], 'CRITICAL ERROR: The folder "test.eintrag03.invalidEintrag" on row 5 is in a folder that is already present as key: test.eintrag03 on row 4');
TestTranslator::isEmpty('invalidFormatErrors', $err['invalidFormatErrors']);
TestTranslator::isEmpty('emptyValueErrors', $err['emptyValueErrors']);
TestTranslator::isEmpty('inCsvNotInDbErrors', $err['inCsvNotInDbErrors']);

$err = array(); $warn = array();
echo "\n\nSpecialchars - OK";
runChecks('04_specialChars OK.csv', $configlang, $warn, $err, $c);
TestTranslator::isEmpty('inDbNotInCsvWarning', $warn['inDbNotInCsvWarning']);
TestTranslator::isEmpty('duplicateErrors', $err['duplicateErrors']);
TestTranslator::isEmpty('folderIsFileErrors', $err['folderIsFileErrors']);
TestTranslator::isEmpty('invalidFormatErrors', $err['invalidFormatErrors']);
TestTranslator::isEmpty('emptyValueErrors', $err['emptyValueErrors']);
TestTranslator::isEmpty('inCsvNotInDbErrors', $err['inCsvNotInDbErrors']);

$err = array(); $warn = array();
echo "\n\nMultilinehtml - OK";
runChecks('05_multilinehtml OK.csv', $configlang, $warn, $err, $c);
TestTranslator::isEmpty('inDbNotInCsvWarning', $warn['inDbNotInCsvWarning']);
TestTranslator::isEmpty('duplicateErrors', $err['duplicateErrors']);
TestTranslator::isEmpty('folderIsFileErrors', $err['folderIsFileErrors']);
TestTranslator::isEmpty('invalidFormatErrors', $err['invalidFormatErrors']);
TestTranslator::isEmpty('emptyValueErrors', $err['emptyValueErrors']);
TestTranslator::isEmpty('inCsvNotInDbErrors', $err['inCsvNotInDbErrors']);

$err = array(); $warn = array();
echo "\n\nduplicates";
runChecks('07_duplicates.csv', $configlang, $warn, $err, $c);
TestTranslator::isEmpty('inDbNotInCsvWarning', $warn['inDbNotInCsvWarning']);
TestTranslator::isNotEmpty('duplicateErrors', $err['duplicateErrors']);
TestTranslator::isEqual('duplicateErrors-Msg', $err['duplicateErrors'][2], 'CRITICAL ERROR: The key "test.eintrag01" on row 2 is a duplicate.');
TestTranslator::isEqual('duplicateErrors-Msg', $err['duplicateErrors'][9], 'CRITICAL ERROR: The key "test.eintrag01" on row 9 is a duplicate.');
TestTranslator::isEmpty('folderIsFileErrors', $err['folderIsFileErrors']);
TestTranslator::isEmpty('invalidFormatErrors', $err['invalidFormatErrors']);
TestTranslator::isEmpty('emptyValueErrors', $err['emptyValueErrors']);
TestTranslator::isEmpty('inCsvNotInDbErrors', $err['inCsvNotInDbErrors']);

$err = array(); $warn = array();
echo "\n\ninvalidFormat - Space in key-name";
runChecks('08_invalidFormat-space.csv', $configlang, $warn, $err, $c);
TestTranslator::isNotEmpty('inDbNotInCsvWarning', $warn['inDbNotInCsvWarning']);
TestTranslator::isEmpty('duplicateErrors', $err['duplicateErrors']);
TestTranslator::isEmpty('folderIsFileErrors', $err['folderIsFileErrors']);
TestTranslator::isNotEmpty('invalidFormatErrors', $err['invalidFormatErrors']);
TestTranslator::isEqual('invalidFormatErrors-Msg', $err['invalidFormatErrors'][0], 'CRITICAL ERROR: The key "test eintrag01" on row 2 has an invalid format.');
TestTranslator::isEmpty('emptyValueErrors', $err['emptyValueErrors']);
TestTranslator::isEmpty('inCsvNotInDbErrors', $err['inCsvNotInDbErrors']);

$err = array(); $warn = array();
echo "\n\ninvalidFormat - No dot in key-name";
runChecks('09_invalidFormat-noDot.csv', $configlang, $warn, $err, $c);
TestTranslator::isNotEmpty('inDbNotInCsvWarning', $warn['inDbNotInCsvWarning']);
TestTranslator::isEmpty('duplicateErrors', $err['duplicateErrors']);
TestTranslator::isEmpty('folderIsFileErrors', $err['folderIsFileErrors']);
TestTranslator::isNotEmpty('invalidFormatErrors', $err['invalidFormatErrors']);
TestTranslator::isEqual('invalidFormatErrors-Msg', $err['invalidFormatErrors'][0], 'CRITICAL ERROR: The key "testeintrag01" on row 2 has an invalid format.');
TestTranslator::isEmpty('emptyValueErrors', $err['emptyValueErrors']);
TestTranslator::isEmpty('inCsvNotInDbErrors', $err['inCsvNotInDbErrors']);

$err = array(); $warn = array();
echo "\n\ninvalidFormat - Specialchars in key-name";
runChecks('10_invalidFormat-specialchars.csv', $configlang, $warn, $err, $c);
TestTranslator::isNotEmpty('inDbNotInCsvWarning', $warn['inDbNotInCsvWarning']);
TestTranslator::isEmpty('duplicateErrors', $err['duplicateErrors']);
TestTranslator::isEmpty('folderIsFileErrors', $err['folderIsFileErrors']);
TestTranslator::isNotEmpty('invalidFormatErrors', $err['invalidFormatErrors']);
TestTranslator::isEqual('invalidFormatErrors-Msg', $err['invalidFormatErrors'][0], 'CRITICAL ERROR: The key "test.&eintrag03" on row 4 has an invalid format.');
TestTranslator::isEmpty('emptyValueErrors', $err['emptyValueErrors']);
TestTranslator::isEmpty('inCsvNotInDbErrors', $err['inCsvNotInDbErrors']);

$err = array(); $warn = array();
echo "\n\nemptyValueErrors";
runChecks('11_emptyValueErrors.csv', $configlang, $warn, $err, $c);
TestTranslator::isEmpty('inDbNotInCsvWarning', $warn['inDbNotInCsvWarning']);
TestTranslator::isEmpty('duplicateErrors', $err['duplicateErrors']);
TestTranslator::isEmpty('folderIsFileErrors', $err['folderIsFileErrors']);
TestTranslator::isEmpty('invalidFormatErrors', $err['invalidFormatErrors']);
TestTranslator::isNotEmpty('emptyValueErrors', $err['emptyValueErrors']);
TestTranslator::isEqual('emptyValueErrors-Msg', $err['emptyValueErrors'][0], 'CRITICAL ERROR: The key "test.eintrag01" on row 2 for the language "it" is empty.');
TestTranslator::isEmpty('inCsvNotInDbErrors', $err['inCsvNotInDbErrors']);

$err = array(); $warn = array();
echo "\n\ninCsvNotInDbErrors";
runChecks('12_inCsvNotInDbErrors.csv', $configlang, $warn, $err, $c);
TestTranslator::isEmpty('inDbNotInCsvWarning', $warn['inDbNotInCsvWarning']);
TestTranslator::isEmpty('duplicateErrors', $err['duplicateErrors']);
TestTranslator::isEmpty('folderIsFileErrors', $err['folderIsFileErrors']);
TestTranslator::isEmpty('invalidFormatErrors', $err['invalidFormatErrors']);
TestTranslator::isEmpty('emptyValueErrors', $err['emptyValueErrors']);
TestTranslator::isNotEmpty('inCsvNotInDbErrors', $err['inCsvNotInDbErrors']);
TestTranslator::isEqual('inCsvNotInDbErrors-Msg', $err['inCsvNotInDbErrors'][0], 'CRITICAL ERROR: The following keys are provided in the CSV but not found in the DB: <br>test.eintragOnlyInCsv');

$err = array(); $warn = array();
echo "\n\ninDbNotInCsvWarning";
runChecks('20_inDbNotInCsvWarning.csv', $configlang, $warn, $err, $c);
TestTranslator::isNotEmpty('inDbNotInCsvWarning', $warn['inDbNotInCsvWarning']);
TestTranslator::isEqual('inDbNotInCsvWarning-Msg', $warn['inDbNotInCsvWarning'][0], 'WARNING: The following keys are stored in the DB but not provided in the CSV: <br>test.subfolder2.subfolder21.eintrag211');
TestTranslator::isEmpty('duplicateErrors', $err['duplicateErrors']);
TestTranslator::isEmpty('folderIsFileErrors', $err['folderIsFileErrors']);
TestTranslator::isEmpty('invalidFormatErrors', $err['invalidFormatErrors']);
TestTranslator::isEmpty('emptyValueErrors', $err['emptyValueErrors']);
TestTranslator::isEmpty('inCsvNotInDbErrors', $err['inCsvNotInDbErrors']);


TestTranslator::printStats();



function runChecks($csvFile, $configLang, &$warnings, &$errors, $c) {

	$currentPath = array();
	$csvKeys = array();
	$invalidFormat = array();

	$critDuplicate = array();
	$critFolder = array();
	$critInvalidFormat = array();
	$critEmptyVal = array();
	$critInCsvNotInDb = array();

	$warnInDbNotInCsv = array();

	$data = $c->load('csv', __DIR__.'/testdata/' . $csvFile);
	v::addRowNumberToCsvData($data);
	$keys = v::getAllKeys($data);
	array_multisort($keys, SORT_ASC, $data);
	foreach($data as $k => $r) {
		$newPath = array();
		$newPath = explode('.', $r['key']);

		v::checkForDuplicates($r, $newPath, $currentPath, $data, $critDuplicate);
		$currentPath = v::checkForFolderIsFile($r, $newPath, $currentPath, $data, $critFolder);

		$invalidFormat[] = v::checkInvalidFormat($r, $critInvalidFormat);

		v::checkEmptyValuesInData($configLang, $r, $critEmptyVal);

		$csvKeys[] = $r['key'];
	}
	$dbData = \translations::get(array(), array('key'));
	$dbDataIndexed = v::getIndexedDbData($dbData);
	$dbKeys = v::getKeysInDb($dbDataIndexed);

	//	Get all keys that are in the DB but are missing in the csv
	v::checkInDbNotInData($dbKeys, $csvKeys, $warnInDbNotInCsv);

	v::checkInDataNotInDb(false, $csvKeys, $dbKeys, $invalidFormat, $critInCsvNotInDb);

	$errors['duplicateErrors'] = $critDuplicate;
	$errors['folderIsFileErrors'] = $critFolder;
	$errors['invalidFormatErrors'] = $critInvalidFormat;
	$errors['emptyValueErrors'] = $critEmptyVal;
	$errors['inCsvNotInDbErrors'] = $critInCsvNotInDb;

	$warnings['inDbNotInCsvWarning'] = $warnInDbNotInCsv;
}


class TestTranslator {

	private static $testCount;
	private static $testFailedCount;
	private static $testSuccessCount;

	public static function printStats() {
		echo "\n\n\n";
		echo "\nTestruns: " . self::$testCount;
		echo "\n\033[32mSuccess : " . self::$testSuccessCount . "\033[0m";
		echo "\n\033[31mFailed  : " . self::$testFailedCount . "\033[0m";
		echo "\n";
	}

	public static function isNotEmpty($n, $arr) {
		self::$testCount++;
		if(!empty($arr)) {
			self::$testSuccessCount++;
			echo sprintf("\n\033[32m o \033[0m" . '%s is not empty', $n);
		} else {
			self::$testFailedCount++;
			echo sprintf("\n\033[31m x \033[0m" . '%s should be empty', $n);
		}
	}

	public static function isEmpty($n, $arr) {
		self::$testCount++;
		if(empty($arr)) {
			self::$testSuccessCount++;
			echo sprintf("\n\033[32m o \033[0m" . '%s is empty', $n);
		} else {
			self::$testFailedCount++;
			echo sprintf("\n\033[31m x \033[0m" . '%s should be empty', $n);
		}
	}

	public static function hasKey($n, $arr, $key) {
		self::$testCount++;
		if(array_key_exists($key, $arr)) {
			self::$testSuccessCount++;
			echo sprintf("\n\033[32m o \033[0m" . 'Key ' . $key . ' exists in %s', $n);
		} else {
			self::$testFailedCount++;
			echo sprintf("\n\033[31m x \033[0m" . 'Key ' . $key . ' should exist in %s', $n);
		}
	}

	public static function isEqual($n, $v1, $v2) {
		self::$testCount++;
		if($v1 === $v2) {
			self::$testSuccessCount++;
			echo sprintf("\n\033[32m o \033[0m" . '%s "%s" is equal to "%s"', $n, self::stringify($v1), self::stringify($v2));
		} else {
			self::$testFailedCount++;
			echo sprintf("\n\033[31m x \033[0m" . '%s "%s" should be equal to "%s"', $n, self::stringify($v1), self::stringify($v2));
		}
	}

	public static function isArray($n, $arr) {
		self::$testCount++;
		if(is_array($arr)) {
			self::$testSuccessCount++;
			echo sprintf("\n\033[32m o \033[0m" . '%s is an Array', $n);
		} else {
			self::$testFailedCount++;
			echo sprintf("\n\033[31m x \033[0m" . '%s should be an Array', $n);
		}
	}

	//Via github:
	//https://github.com/beberlei/assert/blob/master/lib/Assert/Assertion.php
	private static function stringify($v) {
		if(is_bool($v)) {
			return $v? 'true' : 'false';
		}
		if(is_array($v)) {
			return 'Array';
		}
		if(is_object($v)) {
			return get_class($v);
		}
		if($v === NULL) {
			return 'null';
		}
		if(is_string($v)) {
			return $v;
		}
		if(is_scalar($v)) {
			$v = (string) $v;
			if(strlen($v) > 100) {
				$v = substr($v, 0, 97) . '...';
			}
			return $v;
		}
	}

}
