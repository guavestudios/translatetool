<?php

class WidgetController extends BaseController
{
	public static function update()
	{
		header('Access-Control-Allow-Origin: *');
		if (!isset($_POST['keys']) || !isset($_POST['language'])) {
			die('no input');
		}
		$values = json_decode($_POST['keys'], true);
		$lang = $_POST['language'];
		foreach ($values as $key => $value) {
			ExportController::insertDotDelimitedKeyValue($key, $value, $lang, true);
		}
		echo 'done';
	}
}
