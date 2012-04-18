<?php
App::uses('CacheEngine', 'Cache');
class DatabaseCacheEngine extends CacheEngine {
	public $CacheModel = null;
	
	public function write($key, $value, $duration){
		$this->loadCacheModel();
		return $this->CacheModel->writeValueByKey($key, $value, $duration); 
	}
	
	public function read($key){
		$this->loadCacheModel();
		return $this->CacheModel->findValueByKey($key);
	}
	
	public function delete($key){
		$this->loadCacheModel();
		return $this->CacheModel->deleteByKey($key);
	}
	
	public function clear($check){
		$this->loadCacheModel();
		return $check ? $this->gc() : $this->CacheModel->clearAll();
	}
	
	public function increment($key, $offset = 1){
		return false;
	}
	
	public function decrement($key, $offset = 1){
		return false;
	}
	
	public function gc(){
		$this->loadCacheModel();
		$this->CacheModel->clearExpired();
	}
	
	public function loadCacheModel(){
		if($this->CacheModel === null){
			$this->CacheModel = ClassRegistry::init('Icing.DatabaseCache');
		}
	}
}
?>
