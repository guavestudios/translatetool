<?php

namespace Guave\translatetool;

require_once 'Yaml-master/vendor/autoload.php';

use Symfony\Component\Yaml\Yaml as YamlParser;

class yaml{
	
	public function save($content){
		return array(
			'file' =>YamlParser::dump($content),
			'meta' => array(
				'extension' => 'yml'
			)
		);
	}
	
	public function load($file){
		if(!file_exists($file)){
			throw new \Exception("File {$file} not found");
		}
		$return = YamlParser::parse($file);
		return $return;
	}
	
}