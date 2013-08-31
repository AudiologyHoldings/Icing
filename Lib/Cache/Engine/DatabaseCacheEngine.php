<?php
/**
* DatabaseCacheEngine
* @author Nick Baker
* @version 1.0

Setup:

app/Config/bootstrap.php
CakePlugin::load('Icing');
Cache::config('database', array(
	'engine' => 'Icing.DatabaseCache',
	'duration' => '+1 day',
));

Usage:

Cache::write('somekey', 'somevalue', 'database');
Cache::read('somekey', 'database');

*/
App::uses('CacheEngine', 'Cache');
class DatabaseCacheEngine extends CacheEngine {
	public $CacheModel = null;
	
	/**
	* Writing key to database
	* @param string key
	* @param mixed value
	* @param strtotime friendly duration
	* @return boolean success
	*/
	public function write($key, $value, $duration){
		$this->loadCacheModel();
		return $this->CacheModel->writeValueByKey($key, $value, $duration); 
	}
	
	/**
	* Read key from database
	* @param string key
	* @return boolean success
	*/
	public function read($key){
		$this->loadCacheModel();
		return $this->CacheModel->findValueByKey($key);
	}
	
	/**
	* Delete key from database
	* @param string key
	* @return boolean success
	*/
	public function delete($key){
		$this->loadCacheModel();
		return $this->CacheModel->deleteByKey($key);
	}
	
	/**
	* Clear the database
	* @param boolean check if true only delete expired
	* @return boolean success
	*/
	public function clear($check){
		$this->loadCacheModel();
		return $check ? $this->gc() : $this->CacheModel->clearAll();
	}
	
	/**
	* Incriment (not implemented)
	* @param string key
	* @param int offset
	* @return boolean false
	*/
	public function increment($key, $offset = 1){
		return false;
	}
	
	/**
	* Decrement (not implemented)
	* @param string key
	* @param int offset
	* @return boolean false
	*/
	public function decrement($key, $offset = 1){
		return false;
	}
	
	/**
	* Garbage Collection
	* @param string key
	* @param int offset
	* @return boolean false
	*/
	public function gc($expires = null){
		$this->loadCacheModel();
		$this->CacheModel->clearExpired();
	}
	
	/**
	* Incriment (not implemented)
	* @param string key
	* @param int offset
	* @return boolean false
	*/
	public function loadCacheModel(){
		if($this->CacheModel === null){
			$this->CacheModel = ClassRegistry::init('Icing.DatabaseCache');
		}
	}
}
?>
