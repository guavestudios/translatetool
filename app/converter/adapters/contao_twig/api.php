<?php

namespace Guave\translatetool;

class contao_twig {

	public function save($content){
		return array(
			'file' =>'<?php'."\n\n".implode("", $this->buildVarName($content)),
			'meta' => array(
				'extension' => 'json',
				'mime' => 'application/json'
			)
		);
	}

	public function load($file){
		die('cannot load from this format yet');
	}

	public function outputKey($key){
		$this->insertDotDelimitedArray($key, null);
		return '{{ lang.'.$key.' }}';
	}

	private function insertDotDelimitedArray($key, $value, $array = array()){
		$keys = explode(".", $key);
		$firstKey = $keys[0];
		if(!is_array($array)){
			var_dump($array);exit;
		}

		if(count($keys) == 1){
			$array[$firstKey] = $value;
			return $array;
		}
		if(!isset($array[$firstKey])){
			$array[$firstKey] = array();
		}

		unset($keys[0]);
		$array[$firstKey] = $this->insertDotDelimitedArray(implode(".", $keys), $value, $array[$firstKey]);
		return $array;
	}

	private function buildVarName($content, $keys = array(), $useNewline = true, $returnValue = true){
		$rows = array();
		$oldKeys = $keys;
		$newLineChar = $useNewline ? "\n" : "";
		foreach($content as $key => $row){
			$currentKeys = array_merge($oldKeys, array($key));
			foreach($currentKeys as $k => $oneKey){
				if(!preg_match("|^\['(.*)'\]$|", $currentKeys[$k])){
					$currentKeys[$k] = "['".str_replace("'","\'",$oneKey)."']";
				}
			}
			if(is_array($row)){
				$rows = array_merge($rows, $this->buildVarName($row, $currentKeys, $useNewline, $returnValue));
			}else{
				if($returnValue){
					$rows[] = "\$GLOBALS['TL_LANG']".implode("", $currentKeys)." = '".str_replace("'", "\'", $row)."';".$newLineChar;
				}else{
					$rows[] = "\$GLOBALS['TL_LANG']".implode("", $currentKeys).";".$newLineChar;
				}
			}
		}

		return $rows;
	}
}
