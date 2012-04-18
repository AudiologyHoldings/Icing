<?php
class DatabaseCache extends IcingAppModel {
	
	public $primaryKey = 'key';
	
	/**
	* Find the value by key
	* @param string key
	* @return mixed value
	*/
	function findValueByKey($key){
		$retval = $this->find('first', array(
			'conditions' => array(
				'Review.key' => $key,
				'Review.durration >=' => time() 
			)
		));
		if(!empty($retval)){
			return json_decode($retval[$this->alias]['value'], true);
		}
		return false;
	}
	
	/**
	* Write the value to the database
	* @param string key
	* @param mixed value
	* @param mixed durration (strtotime friendly)
	* @return boolean success
	*/
	function writeValueByKey($key, $value, $durration){
		$this->create();
		return !!$this->save(array(
			'key' => $key,
			'value' => json_encode($value),
			'durration' => strtotime($durration)
		));
	}
	
	/**
	* Delete a record by it's key
	* @param string key
	* @return boolean success
	*/
	function deleteByKey($key){
		return $this->delete(array(
			'DatabaseCache.key' => $key
		));
	}
	
	/**
	* Clear all records
	* @return boolean success
	*/
	function clearAll(){
		return $this->deleteAll();
	}
	
	/**
	* Clear the expired cache keys
	* @return boolean success
	*/
	function clearExpired(){
		return $this->deleteAll(array(
			'DatabaseCache.durration <' => time()
		));
	}
}
?>
