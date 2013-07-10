<?php

namespace Guave\translatetool;

class json{
	
	public function save($content){
		return array(
			'file' =>json_encode($content),
			'meta' => array(
				'extension' => 'json'
			)
		);
	}
	
	public function load($file){
		if(!file_exists($file)){
			throw new \Exception("File {$file} not found");
		}
		$content = file_get_contents($file);
		$return = json_decode($content, true);
		if(!$return){
			throw new \Exception("Json is not valid in {$file}");
		}
		return $return;
	}
	
}