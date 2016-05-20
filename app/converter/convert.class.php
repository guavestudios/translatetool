<?php

namespace Guave\translatetool;

class converter{

	private $adapterPath = '/adapters';
	private $adapterStartFile = 'api.php';

	public function save($adapter, $content = array()){
		$apdaterClass = $this->loadAdapter($adapter);
		if(!method_exists($apdaterClass, 'save')){
			throw new \Exception("Adapter class does not have a save() method.");
		}
		return $apdaterClass->save($content);
	}

	public function load($adapter, $file){
		$apdaterClass = $this->loadAdapter($adapter);
		if(!method_exists($apdaterClass, 'load')){
			throw new \Exception("Adapter class does not have a load() method.");
		}
		return $apdaterClass->load($file);
	}

	public function write($filePath, $fileName, $adapterResponse){
		file_put_contents($filePath.$fileName.'.'.$adapterResponse['meta']['extension'], \utf8_encode($adapterResponse['file']));
	}

	public function getKeyFormated($adapter, $key){
		$adapterClass = $this->loadAdapter($adapter);
		if(is_callable(array($adapterClass, 'outputKey'))){
			return call_user_func_array(array($adapterClass, 'outputKey'), array($key));
		}else{
			throw new \Exception("Adapter does not have a function defined to output keys");
		}
	}

	private function loadAdapter($adapter){
		if(!file_exists(__DIR__.$this->adapterPath.'/'.$adapter.'/'.$this->adapterStartFile)){
			throw new \Exception("Adapter not found in: ".$this->adapterPath.'/'.$adapter.'/'.$this->adapterStartFile);
		}
		require_once __DIR__.$this->adapterPath.'/'.$adapter.'/'.$this->adapterStartFile;

		$qualifiedName = 'Guave\translatetool\\'.$adapter;
		if(!class_exists($qualifiedName)){
			throw new \Exception("Adapter class ({$adapter}) not found.");
		}
		return new $qualifiedName();
	}

}
