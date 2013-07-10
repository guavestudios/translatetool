<?php

class log{
	
	private static $sql = null;
	private static $config = null;
	private static $sql_mode = SQLITE3_ASSOC;
	private static $fieldTypes = array();

	public static function append($array){
		foreach($array as $row){
			$qryStringPrep = array();
			foreach($row as $item){
				$item = (is_numeric($item)) ? $item : "'".self::getSql()->escapeString($item)."'";
				$qryStringPrep[] = $item;
			}

			self::qry("
				INSERT INTO
					log(
						".implode(",", array_keys($row))."
					)
				VALUES (
					".implode(",", $qryStringPrep)."
				)
			");
		}
	}
	
	public static function get($array = array(), $orderBy = array()){
		$select = empty($array) ? '*' : implode(",", array_unique(array_merge($array, array('id'))));
		$order = empty($orderBy) ? 'id DESC' : implode(",", $order);
		$results = self::qry("
			SELECT
				{$select}
			FROM
				log
			ORDER BY
				{$order}
		");
		$result = array();
		while($r = $results->fetchArray(self::$sql_mode)){
			$result[] = $r;
		}
		return $result;
	}

	public static function getOne($id){
		return self::qry("
			SELECT
				*
			FROM
				log
			WHERE
				id = {$id}
		")->fetchArray(self::$sql_mode);
	}

	private static function getSql(){
		if(!self::$sql){
			$error = '';
			if(!self::$sql = new SQLite3(self::config('dbpath'), 0666, $error)){
				die($error);
			}
			self::createTable();
		}
		return self::$sql;
	}
	
	private static function createTable(){
		self::getSql()->query("
			CREATE TABLE IF NOT EXISTS `log`(
				".  implode(",\n", self::config('fields')).",
				`id` INTEGER PRIMARY KEY
			);
		");
	}
	
	private static function qry($qry){
		$sql = self::getSql();
		return $sql->query($qry);
	}
	
	public static function displayCol($key, $value, $escape = true, $nl2br = true){
		$el = self::config('displayElements');
		if($nl2br){
			$value = nl2br($value);
		}
		if($escape){
			$value = htmlspecialchars($value);
		}
		if(isset($el[$key])){
			$tmpl = str_replace('{{ value }}', $value, file_get_contents('resrc/'.$el[$key].'.tmpl'));
		}else{
			$tmpl = $value;
		}
		return $tmpl;
	}
	
	public static function config($key){
		if(!self::$config){
			self::$config = json_decode(file_get_contents("config.json"), true);
		}
		if(isset(self::$config[$key])){
			return self::$config[$key];
		}else{
			return false;
		}
	}
	
	public static function backup($fileIdent = ''){
		$path = self::config('dbpath');
		$file = basename($path);
		$dir = dirname($path);
		if($fileIdent != ''){
			$fileIdent .= '_';
		}
		return copy($path, $dir."/".$fileIdent.time()."_".$file);
	}
	
	public static function delete($backup = true, $fileIdent = ''){
		if($backup){
			if(!self::backup($fileIdent)){
				die('Failed to backup DB. Delete canceled');
			}
		}
		$path = self::config('dbpath');
		return unlink($path);
	}
	
}