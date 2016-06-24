<?php

namespace Guave\translatetool\tests;

require_once(dirname(__FILE__) . '/../app/converter/convert.class.php');
require_once(dirname(__FILE__) . '/../app/config.class.php');
require_once(dirname(__FILE__) . '/../app/gui/php/validator.class.php');
require_once(dirname(__FILE__) . '/../app/gui/php/translations.class.php');
require_once(dirname(__FILE__) . '/SimpleTester.php');


use Guave\translatetool\converter as converter;
use Guave\translatetool\validator as v;
use Guave\translatetool\translations;
use Guave\translatetool\SimpleTester as st;

$c = new converter();

// CSV

echo "Opening ".__DIR__.'\testdata\01_1key OK.csv';
$data = $c->load('csv', __DIR__.'/testdata/01_1key OK.csv');

st::isArray('data', $data);
st::isArray('data-entry', $data[0]);
st::hasKey('data-entry', $data[0], "key");
st::hasKey('data-entry', $data[0], "it");
st::hasKey('data-entry', $data[0], "en");

$entry = $data[0];
st::isEqual('data-entry key', $entry['key'], 'test.eintrag01');
st::isEqual('data-entry it', $entry['it'], 'it-eintrag01');
st::isEqual('data-entry en', $entry['en'], 'en-eintrag01');

$configLang = v::getLangsFromConfig();
st::isArray('configLang', $configLang);
st::isEqual('configLang-entry', $configLang[0], "en");
st::isEqual('configLang-entry', $configLang[1], "it");

$dataLang = v::getLangsFromData($data[0]);
st::isArray('dataLang', $dataLang);
st::isEqual('dataLang-entry', $configLang[0], "en");
st::isEqual('dataLang-entry', $configLang[1], "it");

$err  = array();
$warn = array();
$cL = ['en', 'it'];
$dL = ['en', 'it'];
v::checkConfigLangVsDataLang($cL, $dL, $warn, $err);
st::isEmpty('warning', $warn);
st::isEmpty('error', $err);

$err  = array();
$warn = array();
$cL = ['en', 'it', 'de'];
$dL = ['en', 'it'];
v::checkConfigLangVsDataLang($cL, $dL, $warn, $err);
st::isEmpty('errors', $err);
st::isNotEmpty('warnings', $warn);
st::isEqual('warn-array-length', count($warn), 1);
st::isEqual('warning', $warn[0], "WARNING: The config has defined 1 more language(s) than the data provides");

$err  = array();
$warn = array();
$cL = ['en', 'it'];
$dL = ['en', 'it', 'de'];
v::checkConfigLangVsDataLang($cL, $dL, $warn, $err);
st::isEmpty('warnings', $warn);
st::isNotEmpty('errors', $err);
st::isEqual('error-array-length', count($err), 1);
st::isEqual('errors', $err[0], "CRITICAL ERROR: The data contains more languages than the config has defined: de");

v::addRowNumberToCsvData($data);
$entry = $data[0];
st::hasKey('data-entry', $entry, 'row');

$keys = v::getAllKeys($data);
st::isArray('data-key-array', $keys);
st::isEqual('data-key-entry', $keys[0], "test.eintrag01");
st::isEqual('data-key-entry', $keys[1], "test.eintrag02");

$configlang = v::getLangsFromConfig();

//After every runChecks-passing $warn contains the array: inDbNotInCsvWarning
//errors contains the arrays duplicateErrors, folderIsFileErrors, invalidFormatErrors, emptyValueErrors, inCsvNotInDbErrors
$err = array(); $warn = array();
echo "\n\nfolderIsFileErrors";
runChecks('03_folderIsFileErrors.csv', $configlang, $warn, $err, $c);
st::isEmpty('inDbNotInCsvWarning', $warn['inDbNotInCsvWarning']);
st::isEmpty('duplicateErrors', $err['duplicateErrors']);
st::isNotEmpty('folderIsFileErrors', $err['folderIsFileErrors']);
st::isEqual('folderIsFileErrors', $err['folderIsFileErrors'][0], 'CRITICAL ERROR: The folder "test.eintrag03.invalidEintrag" on row 5 is in a folder that is already present as key: test.eintrag03 on row 4');
st::isEmpty('invalidFormatErrors', $err['invalidFormatErrors']);
st::isEmpty('emptyValueErrors', $err['emptyValueErrors']);
st::isEmpty('inCsvNotInDbErrors', $err['inCsvNotInDbErrors']);

$err = array(); $warn = array();
echo "\n\nSpecialchars - OK";
runChecks('04_specialChars OK.csv', $configlang, $warn, $err, $c);
st::isEmpty('inDbNotInCsvWarning', $warn['inDbNotInCsvWarning']);
st::isEmpty('duplicateErrors', $err['duplicateErrors']);
st::isEmpty('folderIsFileErrors', $err['folderIsFileErrors']);
st::isEmpty('invalidFormatErrors', $err['invalidFormatErrors']);
st::isEmpty('emptyValueErrors', $err['emptyValueErrors']);
st::isEmpty('inCsvNotInDbErrors', $err['inCsvNotInDbErrors']);

$err = array(); $warn = array();
echo "\n\nMultilinehtml - OK";
runChecks('05_multilinehtml OK.csv', $configlang, $warn, $err, $c);
st::isEmpty('inDbNotInCsvWarning', $warn['inDbNotInCsvWarning']);
st::isEmpty('duplicateErrors', $err['duplicateErrors']);
st::isEmpty('folderIsFileErrors', $err['folderIsFileErrors']);
st::isEmpty('invalidFormatErrors', $err['invalidFormatErrors']);
st::isEmpty('emptyValueErrors', $err['emptyValueErrors']);
st::isEmpty('inCsvNotInDbErrors', $err['inCsvNotInDbErrors']);

$err = array(); $warn = array();
echo "\n\nduplicates";
runChecks('07_duplicates.csv', $configlang, $warn, $err, $c);
st::isEmpty('inDbNotInCsvWarning', $warn['inDbNotInCsvWarning']);
st::isNotEmpty('duplicateErrors', $err['duplicateErrors']);
st::isEqual('duplicateErrors-Msg', $err['duplicateErrors'][2], 'CRITICAL ERROR: The key "test.eintrag01" on row 2 is a duplicate.');
st::isEqual('duplicateErrors-Msg', $err['duplicateErrors'][9], 'CRITICAL ERROR: The key "test.eintrag01" on row 9 is a duplicate.');
st::isEmpty('folderIsFileErrors', $err['folderIsFileErrors']);
st::isEmpty('invalidFormatErrors', $err['invalidFormatErrors']);
st::isEmpty('emptyValueErrors', $err['emptyValueErrors']);
st::isEmpty('inCsvNotInDbErrors', $err['inCsvNotInDbErrors']);

$err = array(); $warn = array();
echo "\n\ninvalidFormat - Space in key-name";
runChecks('08_invalidFormat-space.csv', $configlang, $warn, $err, $c);
st::isNotEmpty('inDbNotInCsvWarning', $warn['inDbNotInCsvWarning']);
st::isEmpty('duplicateErrors', $err['duplicateErrors']);
st::isEmpty('folderIsFileErrors', $err['folderIsFileErrors']);
st::isNotEmpty('invalidFormatErrors', $err['invalidFormatErrors']);
st::isEqual('invalidFormatErrors-Msg', $err['invalidFormatErrors'][0], 'CRITICAL ERROR: The key "test eintrag01" on row 2 has an invalid format.');
st::isEmpty('emptyValueErrors', $err['emptyValueErrors']);
st::isEmpty('inCsvNotInDbErrors', $err['inCsvNotInDbErrors']);

$err = array(); $warn = array();
echo "\n\ninvalidFormat - No dot in key-name";
runChecks('09_invalidFormat-noDot.csv', $configlang, $warn, $err, $c);
st::isNotEmpty('inDbNotInCsvWarning', $warn['inDbNotInCsvWarning']);
st::isEmpty('duplicateErrors', $err['duplicateErrors']);
st::isEmpty('folderIsFileErrors', $err['folderIsFileErrors']);
st::isNotEmpty('invalidFormatErrors', $err['invalidFormatErrors']);
st::isEqual('invalidFormatErrors-Msg', $err['invalidFormatErrors'][0], 'CRITICAL ERROR: The key "testeintrag01" on row 2 has an invalid format.');
st::isEmpty('emptyValueErrors', $err['emptyValueErrors']);
st::isEmpty('inCsvNotInDbErrors', $err['inCsvNotInDbErrors']);

$err = array(); $warn = array();
echo "\n\ninvalidFormat - Specialchars in key-name";
runChecks('10_invalidFormat-specialchars.csv', $configlang, $warn, $err, $c);
st::isNotEmpty('inDbNotInCsvWarning', $warn['inDbNotInCsvWarning']);
st::isEmpty('duplicateErrors', $err['duplicateErrors']);
st::isEmpty('folderIsFileErrors', $err['folderIsFileErrors']);
st::isNotEmpty('invalidFormatErrors', $err['invalidFormatErrors']);
st::isEqual('invalidFormatErrors-Msg', $err['invalidFormatErrors'][0], 'CRITICAL ERROR: The key "test.&eintrag03" on row 4 has an invalid format.');
st::isEmpty('emptyValueErrors', $err['emptyValueErrors']);
st::isEmpty('inCsvNotInDbErrors', $err['inCsvNotInDbErrors']);

$err = array(); $warn = array();
echo "\n\nemptyValueErrors";
runChecks('11_emptyValueErrors.csv', $configlang, $warn, $err, $c);
st::isEmpty('inDbNotInCsvWarning', $warn['inDbNotInCsvWarning']);
st::isEmpty('duplicateErrors', $err['duplicateErrors']);
st::isEmpty('folderIsFileErrors', $err['folderIsFileErrors']);
st::isEmpty('invalidFormatErrors', $err['invalidFormatErrors']);
st::isNotEmpty('emptyValueErrors', $err['emptyValueErrors']);
st::isEqual('emptyValueErrors-Msg', $err['emptyValueErrors'][0], 'CRITICAL ERROR: The key "test.eintrag01" on row 2 for the language "it" is empty.');
st::isEmpty('inCsvNotInDbErrors', $err['inCsvNotInDbErrors']);

$err = array(); $warn = array();
echo "\n\ninCsvNotInDbErrors";
runChecks('12_inCsvNotInDbErrors.csv', $configlang, $warn, $err, $c);
st::isEmpty('inDbNotInCsvWarning', $warn['inDbNotInCsvWarning']);
st::isEmpty('duplicateErrors', $err['duplicateErrors']);
st::isEmpty('folderIsFileErrors', $err['folderIsFileErrors']);
st::isEmpty('invalidFormatErrors', $err['invalidFormatErrors']);
st::isEmpty('emptyValueErrors', $err['emptyValueErrors']);
st::isNotEmpty('inCsvNotInDbErrors', $err['inCsvNotInDbErrors']);
st::isEqual('inCsvNotInDbErrors-Msg', $err['inCsvNotInDbErrors'][0], 'CRITICAL ERROR: The following keys are provided in the CSV but not found in the DB: <br>test.eintragOnlyInCsv');

$err = array(); $warn = array();
echo "\n\ninDbNotInCsvWarning";
runChecks('20_inDbNotInCsvWarning.csv', $configlang, $warn, $err, $c);
st::isNotEmpty('inDbNotInCsvWarning', $warn['inDbNotInCsvWarning']);
st::isEqual('inDbNotInCsvWarning-Msg', $warn['inDbNotInCsvWarning'][0], 'WARNING: The following keys are stored in the DB but not provided in the CSV: <br>test.subfolder2.subfolder21.eintrag211');
st::isEmpty('duplicateErrors', $err['duplicateErrors']);
st::isEmpty('folderIsFileErrors', $err['folderIsFileErrors']);
st::isEmpty('invalidFormatErrors', $err['invalidFormatErrors']);
st::isEmpty('emptyValueErrors', $err['emptyValueErrors']);
st::isEmpty('inCsvNotInDbErrors', $err['inCsvNotInDbErrors']);


st::printStats();



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
