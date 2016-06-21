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
			$valuePrep = is_numeric($value) ? $value : "'".self::getSql()->escapeString($value)."'";
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

	/**
	 * Gets data from the SQLite-DB.
	 *
	 * @param		array   $array   		Array containing all the columns from which the data should
	 *                           		be retrieved (basically, it indicates the params for the SELECT-
	 *                            	clause in the SQL-Statement). Defaults to * if empty.
	 * @param		array   $orderBy		Array, containing the params for the ORDER BY-Clause.
	 * @return	string  $where			String, containing all the WHERE-params for the SQL-Clause
	 *                          		eg. "language='de'".
	 *                           		Defaults to null, so no conditions are passed.
	 * @return	array								An array containing each row of the resulting query.
	 */
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

	/**
	 * Gets a tree out of DB-Data.
	 *
	 * @param  boolean	$plain	Defines if only key-value combos should be returned
	 *                        	or key-object combos (the objects contain all data of the entries)
	 * @param  integer	$root		[description]
	 * @param  [type]		$where	[description]
	 * @return [type]						[description]
	 */
	public static function getTree($plain = false, $root = 0, $where = null){
		$all = self::get(array(), array('value','key'), $where);
		//Multidimensional array holding all the files and subfolders
		//The first dimension of the array indicates the parent_id, the second one
		//is a 'normal' array-index
		//eg. the structure
		//* folder1
		//	* file1
		//	* file2
		//	* subfolder1
		//		* file11
		//		* file12
		//translates to the array:
		//$assocKeys
		//[1][0] folder1 (id=2)
		//[2][0] file2
		//[2][1] file2
		//[2][2] subfolder1 (id=5)
		//[5][0] file11
		//[5][1] file12
		$assocKeys = array();

		//Array containing all folders in the root-level (parent-id === 0)
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
				$result[$r['key']][$r['language']][] = $r;
				$result[$r['key']]['keyName'] = $r['key'];
				$debug[] = $r;
			}
		}
		return $result;
	}

	/**
	 * Builds a tree out of the passed data.
	 *
	 * @param  array		$keys      	The 'base' of the tree. Array holds all the
	 *                            	entries at the root-level of the tree.
	 * @param  array		$assocKeys 	Array, containing all the 'subentries', that is entries
	 *                            	that are contained in folders.
	 * @param  boolean	$plain     	Defines if the value of the entry should be
	 *                             	stored as only the value or as an array
	 *                             	with 'value' => true and 'content' => $key
	 *                             	(where key is an array).
	 * @return array								[description]
	 */
	private static function buildTree($keys, &$assocKeys, $plain){
		$treePart = array();
		foreach($keys as $key){
			//If value === null we know that the entry/$key is a folder
			if($key['value'] === null){
				//If we have entries in folders assign it to the tmp-var $subtree, else assign empty array to it.
				$subtree = isset($assocKeys[$key['id']]) ? $assocKeys[$key['id']] : array();
				//Check if we want a plain-tree or not.
				//Then build the treepart with buildTree
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
				//$html .= '<li class="'.($value['content']['id'] == $active ? 'active' : '').'">'.$value['content']['key'].'</li>';
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

	public static function getParentIdForDotDelimitedKey($key){
		$keys = explode(".", $key);
		unset($keys[count($keys) - 1]);
		return self::createFoldersFromDotDelimitedKey(array_values($keys));
	}

	public static function createFoldersFromDotDelimitedKey($keys, $parentId = 0){
		if(empty($keys)){
			return $parentId;
		}
		$currentKey = $keys[0];
		unset($keys[0]);
		$keys = array_values($keys);
		$result = self::get(array('id'), array(), "parent_id = {$parentId} AND key = '{$currentKey}' AND language IS NULL");
		if(empty($result)){
			self::append(array(
				array(
					'key' => $currentKey,
					'parent_id' => $parentId,
					'value' => null
				)
			));
			$newId = self::insertId();
			return self::createFoldersFromDotDelimitedKey($keys, $newId);
		}else{
			return self::createFoldersFromDotDelimitedKey($keys, $result[0]['id']);
		}
	}

	private static function getSql(){
		if(!self::$sql){
			$error = '';
			$firstRun = false;
			if(!file_exists(__DIR__.'/'.self::config('dbpath'))){
				$firstRun = true;
			}
			self::$sql = new SQLite3(__DIR__.'/'.self::config('dbpath'));

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
				INSERT INTO log(key, value, parent_id, id, language, modDate, action)
				VALUES (NEW.key, NEW.value, NEW.parent_id, NEW.id, NEW.language, strftime('%s', 'now'), 'insert');
			END;
			CREATE TRIGGER IF NOT EXISTS fill_log_update UPDATE ON ".self::$translationTable."
			BEGIN
				INSERT INTO log(key, value, parent_id, id, language, modDate, action)
				VALUES (NEW.key, NEW.value, NEW.parent_id, NEW.id, NEW.language, strftime('%s', 'now'), 'update');
			END;
			CREATE TRIGGER IF NOT EXISTS fill_log_delete UPDATE ON ".self::$translationTable."
			BEGIN
				INSERT INTO log(key, value, parent_id, id, language, modDate, action)
				VALUES (NEW.key, NEW.value, NEW.parent_id, NEW.id, NEW.language, strftime('%s', 'now'), 'delete');
			END;
		");
	}

	public static function searchForKey($string){
		$results = self::getSql()->query("
			SELECT t1.*, t2.id as folder_id, t2.key as folder_name
			FROM `".self::$translationTable."` t1
			LEFT JOIN `".self::$translationTable."` t2 ON t1.parent_id = t2.id
			WHERE t1.value LIKE '%".self::getSql()->escapeString($string)."%' OR t1.key LIKE '%".self::getSql()->escapeString($string)."%'
			ORDER BY t1.key DESC
		");
		$result = array();
		if($results){
			while($r = $results->fetchArray(self::$sql_mode)){
				$result[] = $r;
			}
		}
		return $result;
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
