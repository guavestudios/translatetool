<?php

namespace Guave\translatetool;

class csv_export{
	
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
		die('you cannot load from this adapter');
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