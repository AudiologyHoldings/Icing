<?php
App::uses('IcingAppModel', 'Icing.Model');
/**
 * IcingVersion Model
 *
 * @property User $User
 */
class IcingVersion extends IcingAppModel {

/**
 * Display field
 *
 * @var string
 */
	public $displayField = 'model';

/**
 * Validation rules
 *
 * @var array
 */
	public $validate = array(
		'model_id' => array(
			'numeric' => array(
				'rule' => array('numeric'),
				'message' => 'Must have a model id',
			),
		),
		'model' => array(
			'notempty' => array(
				'rule' => array('notempty'),
			),
		),
		'json' => array(
			'notempty' => array(
				'rule' => array('notempty'),
			),
		),
		'is_delete' => array(
			'boolean' => array(
				'rule' => array('boolean'),
			),
		),
	);
	
	/**
	* Finds the version back from curent based by number count
	* @param int version number 1-infinity
	*/
	public function findVersionBack($model = null, $number = 0, $model_id = 0){
		if($number && $model_id && $model){
			return $this->find('first', array(
				'conditions' => array(
					'model' => $model,
					'model_id' => $model_id
				),
				'order' => array("IcingVersion.created DESC"),
				'offset' => $number - 1
			));
		}
		return false;
	}
	
	/**
	* Cleanup all minor versions on table
	*/
	public function cleanUpMinorVersions(){
		return $this->deleteAll(array(
			'IcingVersion.is_minor_version' => true
		));
	}
	
	/**
	* Save The Version data, but first check the limit, and delete the oldest based on created
	* if limit is reached.
	* @param array of data to save
	* @param array of settings from VersionableBehavior
	* - versions number of versions to save back on paticular record for model
	*/
	public function saveVersion($data, $settings = array()){
		$conditions = array(
			'model' => $data['model'],
			'model_id' => $data['model_id'],
		);
		if(isset($settings['versions']) && $settings['versions']){
			$count = $this->find('count', array(
				'conditions' => $conditions 
			));
			if($count >= $settings['versions']){
				$last = $this->find('first', array(
					'fields' => array('id'),
					'conditions' => $conditions,
					'order' => array("{$this->alias}.created ASC")
				));
				$this->delete($last[$this->alias]['id']);
			}
		}
		//check if this is a minor_revision based on minor_timeframe from settings
		if(isset($settings['minor_timeframe']) && $settings['minor_timeframe']){
			$versions_within_timeframe = $this->find('list', array(
				'conditions' => array_merge($conditions, array(
					'IcingVersion.created >=' => date("Y-m-d H:i:s", strtotime("-{$settings['minor_timeframe']} seconds", time()))
				)),
			));
			foreach($versions_within_timeframe as $version_id => $model){
				$this->id = $version_id;
				$this->saveField('is_minor_version', true);
			}
		}
		
		$this->create();
		return $this->save($data);
	}
}
