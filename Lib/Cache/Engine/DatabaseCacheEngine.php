<?php
App::uses('CacheEngine', 'Cache');
class DatabaseCacheEngine extends CacheEngine {
	public static $CacheModel = null;
	
	public static function write($key, $value, $duration){
		self::loadCacheModel();
		return self::$CacheModel->writeValueByKey($key, $value, $duration); 
	}
	
	public static function read($key){
		self::loadCacheModel();
		return self::$CacheModel->findValueByKey($key);
	}
	
	public static function delete($key){
		self::loadCacheModel();
		return self::$CacheModel->deleteByKey($key);
	}
	
	public static function clear($check){
		self::loadCacheModel();
		return $check ? self::gc() : self::$CacheModel->clearAll();
	}
	
	public static function gc(){
		self::loadCacheModel();
		self::$CacheModel->clearExpired();
	}
	
	public static function loadCacheModel(){
		if(self::$CacheModel === null){
			self::$CacheModel = ClassRegistry::init('Icing.DatabaseCache');
		}
	}
}
?>
