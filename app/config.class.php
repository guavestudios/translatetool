<?php

class config{

	private static $cfgPath = '/config.json';
	private static $cfg = null;

	public static function get($key){
		if(!self::$cfg){
			self::$cfg = self::readConfig();
		}
		return isset(self::$cfg[$key]) ? self::$cfg[$key] : null;
	}

	public static function setConfigPath($path) {
		self::$cfgPath = $path;
		$cfg = null;
	}

	private static function readConfig(){
		if(file_exists(__DIR__.self::$cfgPath)){
			return json_decode(file_get_contents(__DIR__.self::$cfgPath), true);
		}
		throw new Exception('Could not open Config file: '.__DIR__.self::$cfgPath);
	}

}
