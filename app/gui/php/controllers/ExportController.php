<?php

use Guave\translatetool\converter;

class ExportController extends BaseController
{
	public static function export($addInlineToken = false)
	{
		self::exportRaw(isset($_GET['token']));
	}

	public static function exportRaw($addInlineToken)
	{
		$converter = new converter();
		foreach (config::get('exports') as $export) {
			foreach (config::get('languages') as $lang) {
				$exportPlain = true;
				if (isset($export['raw']) and $export['raw'] == 'true') {
					$exportPlain = false;
				}
				$translations = translations::getTree($exportPlain, 0, "language = '{$lang}' OR language IS NULL");
				if ($addInlineToken) {
					$translations = self::addInlineToken($translations);
				}
				$output = $converter->save($export['adapter'], $translations);
				$savePath = self::resolveDocRootPath($export['path'], $lang);
				file_put_contents($savePath, $output['file']);
			}
		}
		if (config::get('exports_combined')) {
			foreach (config::get('exports_combined') as $export) {
				$outputs = array();
				foreach (config::get('languages') as $lang) {
					$exportPlain = true;
					if (isset($export['raw']) and $export['raw'] == 'true') {
						$exportPlain = false;
					}
					$outputs[$lang] = translations::getTree($exportPlain, 0, "language = '{$lang}' OR language IS NULL");
				}
				$output = $converter->save($export['adapter'], $outputs);
				$savePath = self::resolveDocRootPath($export['path']);
				file_put_contents($savePath, $output['file']);
			}
		}
	}

	private static function addInlineToken($array, $prevKey = '')
	{
		foreach ($array as $key => $value) {
			$currentKey = (empty($prevKey) ? $key : $prevKey . '.' . $key);
			if (is_array($value)) {
				$array[$key] = self::addInlineToken($value, $currentKey);
			} else if (is_string($value)) {
				$array[$key] = $value . '<span class="translatetool-phrase" style="display:none" data-key="' . $currentKey . '">' . $value . '</span>';
			}
		}
		return $array;
	}

	public static function downloadCSV()
	{
		self::export();
		$downloadConfig = config::get('export_download');
		if (empty($downloadConfig)) {
			die('No download configured');
		}
		list($type, $key) = explode(":", $downloadConfig);
		$tmp = config::get($type);
		$downloadInfo = $tmp[$key];
		$path = self::resolveDocRootPath($downloadInfo['path']);
		header("Content-Type: text/csv");
		header("Content-disposition: attachment; filename=\"" . basename($path) . "\"");
		header('Content-Length: ' . filesize($path));
		readfile($path);
	}

	public static function dump()
	{
		$converter = new converter();
		$output = $converter->save('json', translations::get());
		header('Content-Type: ' . $output['meta']['mime']);
		echo $output['file'];
	}

	public static function convertKey()
	{
		$adapter = $_POST['adapter'] ? $_POST['adapter'] : config::get('export_key_adapter');
		$error = "";
		$output = "";

		$value = $_POST['value'];
		$key = $_POST['key'];
		$format = $_GET['format'];

		if (empty($adapter)) {
			$error = array("message" => 'The Translatetool needs to have the value in "export_key_adapter" set.');
		} else if (empty($value)) {
			$error = array("message" => 'No value was provided');
		} else if (empty($key)) {
			$error = array("message" => 'No key was provided');
		} else if (preg_match("/^([a-zA-Z0-9]{2,})(\.[a-zA-Z0-9]+)+$/", $key) == false) {
			$error = array("message" => 'Key has the wrong format');
		}

		if (empty($error)) {
			$result = self::insertDotDelimitedKeyValue($key, $value);
			$converter = new converter();

			try {
				$output = $converter->getKeyFormated($adapter, $key, $value);

				if (!empty($result)) {
					$error = array(
						"message" => "Key existiert bereits...",
						"key" => $key,
						"keyFormatted" => $output
					);
				}
			} catch (\Exception $ex) {
				$error = array(
					"message" => "adapter did not support request",
					"error" => $ex->getMessage()
				);
			}
		}

		if ($format == "json") {
			header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
			header('Content-Type: application/json');
			echo json_encode(array(
				"status" => empty($error),
				"response" => empty($error) ? $output : $error
			));
		} else {
			header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
			echo empty($error) ? $output : $error["message"];
		}
	}

	public static function insertDotDelimitedKeyValue($key, $value, $lang = null, $replace = false)
	{
		if (!$lang) {
			$languages = config::get('languages');
			$lang = $languages[0];
		}
		$keys = explode(".", $key);
		$endKey = end($keys);
		$parentId = translations::getParentIdForDotDelimitedKey($key, $lang);
		$result = translations::get(array(), array(), "key = '{$endKey}' AND language = '{$lang}' AND parent_id = {$parentId}");
		if (empty($result)) {
			translations::append(array(
				array(
					'key' => $endKey,
					'parent_id' => $parentId,
					'language' => $lang,
					'value' => $value
				)
			));
		} else if ($replace) {
			translations::update(
				$result[0]['id'],
				array(
					'key' => $endKey,
					'parent_id' => $parentId,
					'language' => $lang,
					'value' => $value
				)
			);
		} else {
			return array(
				"keyDoesNotExistError" => array(
					'CRITICAL ERROR: The following key does not exist in the DB "' . $key . '" language ' . $lang
				)
			);
		}
	}

	public static function updateDb()
	{
		foreach (config::get('masters') as $master) {
			if (strpos($master, 'https://') !== 0) {
				throw new Exception('Master URL must use https: ' . $master);
			}
			$urlcontent = file_get_contents($master . '/dump?apicall');
			$dump = json_decode($urlcontent, true);
			translations::delete();
			translations::append($dump);
		}
		self::export();
		if (isset($_GET['bounceback'])) {
			self::redirect($_GET['bounceback']);
		}
		self::redirect('');
	}
}
