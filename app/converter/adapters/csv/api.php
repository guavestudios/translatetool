<?php

namespace Guave\translatetool;

require_once(dirname(__FILE__) . '/../AbstractBaseAdapter.class.php');
require_once(dirname(__FILE__) . '/parsecsv.lib.php');
require_once(dirname(__FILE__) . '/../../../config.class.php');

use Guave\translatetool\AbstractBaseAdapter as AbstractBaseAdapter;

class csv extends AbstractBaseAdapter{

	public function save($content){
    $csvArrays = array();
    foreach($content as $lang => $c){
      $csvArrays[$lang] = $this->encodeCSV($c, $lang);
    }
    $csvArray = array();
    foreach($csvArrays as $a){
      $csvArray = array_merge_recursive($csvArray, $this->array_filter_recursive($a));
    }
    $csv = array();
    $csv[] = implode(";", array_merge(array('key'), \config::get('languages')));
    foreach($csvArray as $key => $row){
      $rows = '';
      foreach(\config::get('languages') as $l){
        $r = '';
        if(isset($row[$l])){
          $r = $row[$l];
        }
        $rows .= ';'.$r;
      }
      $csv[] = $key.$rows;
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
		$csvData = $csv;
		$csvData = $csvData->data;

		return $csvData;
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

	private function encodeCSV($content, $lang, $keys = array()){
		$rows = array();
		$oldKeys = $keys;
		foreach($content as $key => $row){
			$currentKeys = array_merge($oldKeys, array($key));
			if(is_array($row)){
				$rows = array_merge_recursive($rows, $this->encodeCSV($row, $lang, $currentKeys));
			}else{
        if(!isset($rows[implode(".", $currentKeys)])){
          $rows[implode(".", $currentKeys)] = array_combine(\config::get('languages'), array_fill(0, count(\config::get('languages')), null));
        }
				$rows[implode(".", $currentKeys)][$lang] = str_replace('"', '\"', $row);

			}
		}

		return $rows;
	}

  public function array_filter_recursive($input){
    foreach ($input as &$value){
      if (is_array($value)){
        $value = $this->array_filter_recursive($value);
      }
    }

    return array_filter($input);
  }
}
