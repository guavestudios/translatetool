<?php

namespace Guave\translatetool;

class contao{
	
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
	
	private function buildVarName($content, $keys = array()){
		$rows = array();
		$oldKeys = $keys;
		foreach($content as $key => $row){
			$currentKeys = array_merge($oldKeys, array($key));
			foreach($currentKeys as $k => $oneKey){
				if(!preg_match("|^\['(.*)'\]$|", $currentKeys[$k])){
					$currentKeys[$k] = "['".str_replace("'","\'",$oneKey)."']";
				}
			}
			if(is_array($row)){
				$rows = array_merge($rows, $this->buildVarName($row, $currentKeys));
			}else{
				$rows[] = "\$GLOBALS".implode("", $currentKeys)." = '".str_replace("'", "\'", $row)."';\n";
			}
		}
		
		return $rows;
	}
}