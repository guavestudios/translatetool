<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config.class.php';
\config::setConfigPath('/config.test.json');

require_once __DIR__ . '/../app/converter/convert.class.php';
require_once __DIR__ . '/../app/gui/php/translations.class.php';
require_once __DIR__ . '/../app/gui/php/validator.class.php';
require_once __DIR__ . '/SimpleTester.php';

use Guave\translatetool\converter;
use Guave\translatetool\Validator;
use Guave\translatetool\SimpleTester as st;

function getSqliteInstance(): SQLite3
{
	$reflection = new ReflectionClass('translations');
	$method = $reflection->getMethod('getSql');
	$method->setAccessible(true);
	return $method->invoke(null);
}

function assertDbShape(SQLite3 $db): void
{
	$translationsColumns = array();
	$translationsResult = $db->query("PRAGMA table_info('translations')");
	while ($row = $translationsResult->fetchArray(SQLITE3_ASSOC)) {
		$translationsColumns[] = $row['name'];
	}

	$logColumns = array();
	$logResult = $db->query("PRAGMA table_info('log')");
	while ($row = $logResult->fetchArray(SQLITE3_ASSOC)) {
		$logColumns[] = $row['name'];
	}

	st::isEqual('translations columns', implode(',', $translationsColumns), 'key,value,parent_id,language,id');
	st::isEqual('log columns', implode(',', $logColumns), 'key,value,parent_id,language,id,modDate,action');
}

function cleanupTestDb(SQLite3 $db): void
{
	$db->exec("DELETE FROM translations");
	$db->exec("DELETE FROM log");
}

$db = getSqliteInstance();
cleanupTestDb($db);
assertDbShape($db);

$converter = new converter();
$yaml = $converter->save('yaml', array('hello' => array('world' => 'ok')));
st::isEqual('yaml extension', $yaml['meta']['extension'], 'yml');

$csvData = $converter->load('csv', __DIR__ . '/testdata/01_1key OK.csv');
st::isArray('csv data', $csvData);
st::isEqual('csv first key', $csvData[0]['key'], 'test.eintrag01');

$langsFromConfig = Validator::getLangsFromConfig();
st::isEqual('lang count', count($langsFromConfig), 2);

translations::append(array(
	array(
		'key' => 'test',
		'parent_id' => 0,
		'value' => null
	)
));
$folderId = translations::insertId();
translations::append(array(
	array(
		'key' => 'eintrag01',
		'parent_id' => $folderId,
		'language' => 'en',
		'value' => 'value-en'
	)
));

$tree = translations::getTree(true);
st::isArray('tree', $tree);
st::hasKey('tree root', $tree, 'test');

cleanupTestDb($db);
st::printStats();
