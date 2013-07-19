<?php

class translations{
	
	private static $sql = null;
	private static $config = null;
	private static $sql_mode = SQLITE3_ASSOC;
	private static $fieldTypes = array();
	private static $translationTable = 'translations';
	private static $logTable = 'log';

	public static function append($array){
		foreach($array as $row){
			$qryStringPrep = array();
			foreach($row as $item){
				if($item === null){
					$item = 'NULL';
				}else{
					$item = (is_numeric($item)) ? $item : "'".self::getSql()->escapeString($item)."'";
				}
				$qryStringPrep[] = $item;
			}

			self::qry("
				INSERT INTO
					".self::$translationTable."(
						".implode(",", array_keys($row))."
					)
				VALUES (
					".implode(",", $qryStringPrep)."
				)
			");
		}
	}
	
	public static function update($id, $values){
		$qryStringPrep = array();
		
		foreach($values as $key => $value){
			$valuePrep = is_numeric($value) ? $value : "'".$value."'";
			$qryStringPrep[] = "{$key} = {$valuePrep}";
		}

		self::qry("
			UPDATE ".self::$translationTable." SET ".implode(", ", $qryStringPrep)." WHERE id = {$id}
		");
	}
	
	public static function deleteRow($id){
		if(!is_numeric($id)){
			throw new Exception("ID is not numeric (given: '{$id}')");
		}
		self::qry("DELETE FROM ".self::$translationTable." WHERE id = {$id}");
	}
	
	public static function insertId(){
		return self::getSql()->lastInsertRowid();
	}
	
	public static function get($array = array(), $orderBy = array(), $where = null){
		$select = empty($array) ? '*' : implode(",", array_unique(array_merge($array, array('id'))));
		$order = empty($orderBy) ? 'id DESC' : implode(",", $orderBy);
		if($where){
			$where = "WHERE {$where}";
		}
		$results = self::qry("
			SELECT
				{$select}
			FROM
				".self::$translationTable."
			{$where}
			ORDER BY
				{$order}
		");
		$result = array();
		while($r = $results->fetchArray(self::$sql_mode)){
			$result[] = $r;
		}
		return $result;
	}
	
	public static function getTree($plain = false, $root = 0){
		$all = self::get(array(), array('value','key'));
		$assocKeys = array();
		$rootKeys = array();
		foreach($all as $key){
			if($key['parent_id'] > 0){
				$assocKeys[$key['parent_id']][] = $key;
			}else{
				$rootKeys[] = $key;
			}
		}
		
		$tree = self::buildTree($rootKeys, $assocKeys, $plain);
		return $tree;
	}
	
	public static function getValues($keyId){
		$results = self::qry("
			SELECT
				*
			FROM
				".self::$translationTable."
			WHERE
				parent_id = {$keyId}
			AND
				value IS NOT NULL
			ORDER BY
				key
		");
		$result = array();
		if($results){
			while($r = $results->fetchArray(self::$sql_mode)){
				$result[] = $r;
			}
		}
		return $result;
	}
	
	private static function buildTree($keys, &$assocKeys, $plain){
		$treePart = array();
		foreach($keys as $key){
			if($key['value'] === null){
				$subtree = isset($assocKeys[$key['id']]) ? $assocKeys[$key['id']] : array();
				if($plain){
					$treePart[$key['key']] = self::buildTree($subtree, $assocKeys, $plain);
				}else{
					$treePart[$key['key']] = array('value' => false, 'content' => $key, 'children' => self::buildTree($subtree, $assocKeys, $plain));
				}
			}else{
				if($plain){
					$treePart[$key['key']] = $key['value'];
				}else{
					$treePart[$key['key']] = array('value' => true, 'content' => $key);
				}
			}
		}
		return $treePart;
	}
	
	public static function getTreeHtml($active = 0, $tree = null){
		if($tree === null){
			$tree = self::getTree();
		}
		$html = '<ul>';
		foreach($tree as $key => $value){
			if(!isset($value['children'])){
				$html .= '<li class="'.($value['content']['id'] == $active ? 'active' : '').'">'.$value['content']['key'].'</li>';
			}else{
				$subtree = self::getTreeHtml($active, $value['children']);
				$html .= '<li class="'.($value['content']['id'] == $active ? 'active' : '').'">'
						.'<span class="row"><a href="key/'.$value['content']['id'].'">'.$key.'</a>';
				if(auth::has('admin')){
				$html .= '<span class="hovermenu">'
						.'<a href="add/folder/'.$value['content']['id'].'"><img src="gui/images/add-icon.png" height="14"></a>'
						.'<a href="edit/folder/'.$value['content']['id'].'"><img src="gui/images/edit-icon.png" height="14"></a>'
						.'<a href="del/folder/'.$value['content']['id'].'" onClick="return confirm(\'Wirklich löschen?\')"><img src="gui/images/delete-icon.png" height="14"></a>'
						.'</span>';
				}
				$html .= '</span>'.$subtree.'</li>';
			}
		}
		$html .= '</ul>';
		return $html;
	}

	public static function getOne($id){
		return self::qry("
			SELECT
				*
			FROM
				".self::$translationTable."
			WHERE
				id = {$id}
		")->fetchArray(self::$sql_mode);
	}

	private static function getSql(){
		if(!self::$sql){
			$error = '';
			$firstRun = false;
			if(!file_exists(__DIR__.'/'.self::config('dbpath'))){
				$firstRun = true;
			}
			if(!self::$sql = new SQLite3(__DIR__.'/'.self::config('dbpath'), 0666, $error)){
				die($error);
			}
			if($firstRun){
				self::createTable();
			}
		}
		return self::$sql;
	}
	
	private static function createTable(){
		self::getSql()->query("
			CREATE TABLE IF NOT EXISTS `".self::$translationTable."`(
				".  implode(",\n", self::config('fields')).",
				`id` INTEGER PRIMARY KEY
			);
		");
		self::getSql()->query("
			CREATE TABLE IF NOT EXISTS `".self::$logTable."`(
				".  implode(",\n", self::config('fields')).",
				`id` INTEGER,
				`modDate` INTEGER,
				`action` TEXT
			);
		");
		self::getSql()->query("
			CREATE TRIGGER IF NOT EXISTS fill_log_insert INSERT ON ".self::$translationTable."
			BEGIN
				INSERT INTO log(key, value, parent_id, id, modDate, action) VALUES (NEW.key, NEW.value, NEW.parent_id, NEW.id, strftime('%s', 'now'), 'insert');
			END;
			CREATE TRIGGER IF NOT EXISTS fill_log_update UPDATE ON ".self::$translationTable."
			BEGIN
				INSERT INTO log(key, value, parent_id, id, modDate, action) VALUES (NEW.key, NEW.value, NEW.parent_id, NEW.id, strftime('%s', 'now'), 'update');
			END;
			CREATE TRIGGER IF NOT EXISTS fill_log_delete UPDATE ON ".self::$translationTable."
			BEGIN
				INSERT INTO log(key, value, parent_id, id, modDate, action) VALUES (NEW.key, NEW.value, NEW.parent_id, NEW.id, strftime('%s', 'now'), 'delete');
			END;
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
		return config::get($key);
	}
	
	public static function backup($fileIdent = ''){
		$path = __DIR__.'/'.self::config('dbpath');
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
		return unlink(__DIR__.'/'.$path);
	}
	
}