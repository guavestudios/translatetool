<?php

namespace Guave\translatetool;

require 'parsecsv.lib.php';

class csv{
	
	public function save($content){
		$csvArray = $this->encodeCSV($content);
		$csv = array();
		foreach($csvArray as $row){
			$csv[] = implode(";", $row);
		}
		return array(
			'file' => implode("\n", $csv),
			'meta' => array(
				'extension' => 'csv',
				'mime' => 'application/csv'
			)
		);
	}
	
	public function load($file){
		if(!file_exists($file)){
			throw new \Exception("File {$file} not found");
		}
		$csv = new \parseCSV;
		$csv->linefeed = "\n";
		$csv->delimiter = ";";
		$csv->parse($file);

		$newArray = array();
		foreach($csv->data as $k => $row){
			$newArray = array_replace_recursive($newArray, $this->insertDotDelimitedArray($row['key'], $row['trans']));
		}
		return $newArray;
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
	
	private function encodeCSV($content, $keys = array()){
		$rows = array();
		$oldKeys = $keys;
		foreach($content as $key => $row){
			$currentKeys = array_merge($oldKeys, array($key));
			if(is_array($row)){
				$rows = array_merge($rows, $this->encodeCSV($row, $currentKeys));
			}else{
				$rows[] = array(
					'"'.implode(".", $currentKeys).'"',
					'"'.str_replace('"', '\"', $row).'"'
				);
			}
		}
		
		return $rows;
	}
}