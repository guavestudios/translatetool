<?php

class BaseController
{
	protected static function render($template, $vars = array())
	{
		Flight::render($template, $vars, 'body_content');
		Flight::render('layout', $vars);
	}

	public static function redirect($url)
	{
		header('Location: ' . config::get('base') . $url);
		exit;
	}

	protected static function mirror()
	{
		if (isset($_GET['apicall'])) {
			return false;
		}
		$masters = config::get('masters');
		if ($masters) {
			foreach ($masters as $master) {
				if (strstr($master, $_SERVER['HTTP_HOST'])) {
					throw new Exception("You might be trying to use your own server as master. That is probably not a good idea. ({$master})");
				}
				self::assertSecureMasterUrl($master);
				self::post($master . str_replace(config::get('base'), "/", $_SERVER['REQUEST_URI']) . '?apicall=true', $_POST);
			}
		}
		return true;
	}

	protected static function post($url, $post = array())
	{
		self::assertSecureMasterUrl($url);
		$curl = curl_init($url);
		if (count($post) > 0) {
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, self::asPostString($_POST));
		}
		curl_setopt($curl, CURLOPT_HEADER, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		$return = curl_exec($curl);
		curl_close($curl);
		return $return;
	}

	protected static function asPostString($theData, $theName = null)
	{
		$thePostString = '';
		$thePrefix = $theName;

		if (is_array($theData)) {
			foreach ($theData as $theKey => $theValue) {
				if ($thePrefix === null) {
					$thePostString .= '&' . self::asPostString($theValue, $theKey);
				} else {
					$thePostString .= '&' . self::asPostString($theValue, $thePrefix . '[' . $theKey . ']');
				}
			}
		} else {
			$thePostString .= '&' . urlencode((string) $thePrefix) . '=' . urlencode((string) $theData);
		}

		return substr($thePostString, 1);
	}

	protected static function resolveDocRootPath($configuredPath, $lang = null)
	{
		$resolvedPath = str_replace("{doc_root}", $_SERVER['DOCUMENT_ROOT'], $configuredPath);
		if ($lang !== null) {
			$resolvedPath = str_replace("{lang}", $lang, $resolvedPath);
		}

		$docRootRealPath = realpath($_SERVER['DOCUMENT_ROOT']);
		$targetDirectory = dirname($resolvedPath);
		if (!is_dir($targetDirectory)) {
			throw new Exception('Target directory does not exist: ' . $targetDirectory);
		}

		$targetDirectoryRealPath = realpath($targetDirectory);
		if ($docRootRealPath === false || $targetDirectoryRealPath === false || strpos($targetDirectoryRealPath, $docRootRealPath) !== 0) {
			throw new Exception('Refusing to write outside document root: ' . $resolvedPath);
		}

		return $resolvedPath;
	}

	private static function assertSecureMasterUrl($url)
	{
		$parts = parse_url($url);
		if ($parts === false || !isset($parts['scheme']) || strtolower($parts['scheme']) !== 'https') {
			throw new Exception('Master URL must use https: ' . $url);
		}
	}
}
