<?php

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/php/auth.class.php';
require_once dirname(__FILE__) . '/php/translations.class.php';
require_once dirname(__FILE__) . '/../config.class.php';
require_once dirname(__FILE__) . '/../converter/convert.class.php';
require_once dirname(__FILE__) . '/php/validator.class.php';
require_once dirname(__FILE__) . '/php/controllers/BaseController.php';
require_once dirname(__FILE__) . '/php/controllers/AuthController.php';
require_once dirname(__FILE__) . '/php/controllers/ExportController.php';
require_once dirname(__FILE__) . '/php/controllers/ImportController.php';
require_once dirname(__FILE__) . '/php/controllers/TranslationController.php';
require_once dirname(__FILE__) . '/php/controllers/WidgetController.php';

date_default_timezone_set("Europe/Zurich");
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}

Flight::before('route', function (&$params, &$output) {
	if (!auth::ed() and $_SERVER['REQUEST_URI'] != config::get('base') . 'login' and !isset($_GET['apicall'])) {
		BaseController::redirect('login');
	}
});

Flight::route('/', function () {
	BaseController::redirect('overview');
});

Flight::route('POST /login', array('AuthController', 'loginAuth'));
Flight::route('/login', array('AuthController', 'login'));
Flight::route('/logout', array('AuthController', 'logout'));
Flight::route('/overview', array('TranslationController', 'overview'));
Flight::route('POST /key/@keyId', array('TranslationController', 'saveKeys'));
Flight::route('/key/@keyId', array('TranslationController', 'key'));
Flight::route('POST /add/folder', array('TranslationController', 'addFolder'));
Flight::route('/add/folder/@parentId', array('TranslationController', 'showAddFolder'));
Flight::route('POST /edit/folder', array('TranslationController', 'editFolder'));
Flight::route('/edit/folder/@editId', array('TranslationController', 'showEditFolder'));
Flight::route('/del/folder/@delId', array('TranslationController', 'delFolder'));
Flight::route('/export', array('ExportController', 'export'));
Flight::route('/download', array('ExportController', 'downloadCSV'));
Flight::route('POST /delete/multikeys', array('TranslationController', 'deleteMultiKeys'));
Flight::route('/delete/@keyId/@active', array('TranslationController', 'deleteKey'));
Flight::route('/poll', array('TranslationController', 'poll'));
Flight::route('/dump', array('ExportController', 'dump'));
Flight::route('/update', array('ExportController', 'updateDb'));
Flight::route('/search', array('TranslationController', 'search'));
Flight::route('/convert', array('ExportController', 'convertKey'));
Flight::route('/import', array('ImportController', 'import'));
Flight::route('/importCSV', array('ImportController', 'import'));
Flight::route('POST /import/confirm', array('ImportController', 'confirmImport'));
Flight::route('/nottranslated', array('TranslationController', 'nottranslated'));
Flight::route('/widget/update', array('WidgetController', 'update'));

Flight::start();
