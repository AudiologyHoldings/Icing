<?php
class DatabaseCache extends IcingAppModel {
	public $name = 'DatabaseCache';
	public $primaryKey = 'key';
	public $useTable = 'database_caches';
	
	/**
	* Find the value by key
	* @param string key
	* @return mixed value
	*/
	function findValueByKey($key){
		$retval = $this->find('first', array(
			'conditions' => array(
				'DatabaseCache.key' => $key,
				'DatabaseCache.duration >=' => time() 
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
	* @param mixed duration (strtotime friendly)
	* @return boolean success
	*/
	function writeValueByKey($key, $value, $duration){
		$this->create();
		$save_data = array(
			'key' => $key,
			'value' => json_encode($value),
			'duration' => time() + $duration
		);
		return !!$this->save($save_data);
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
		return $this->deleteAll(array('1=1'));
	}
	
	/**
	* Clear the expired cache keys
	* @return boolean success
	*/
	function clearExpired(){
		return $this->deleteAll(array(
			'DatabaseCache.duration <' => time()
		));
	}
}
?>
