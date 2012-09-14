<?php
/**
  * Attach to any model to creating versions of current state on save for later restoration
  * Uses the AuthComponent to log the user doing the save by default.
  *
  * Setup:
  *   You have to install the icing_versions table into your database.  You can do so by running:
  *   
  * cake schema create -p Icing
  *   
  * 
  * Example Usage:
  * @example 
  * public $actsAs = array('Icing.Versionable');
  *
  * @example 
  	public $actsAs = array('Icing.Versionable' => array(
  		'contain' => array('Hour'), //contains for relative model to be saved.
  		'versions' => '5', //how many version to save at any given time (false by default unlimited)
  		'bind' => true, //attach Versionable as HasMany relationship for you onFind and if contained
  	));
  	
  	Restore from Previous Version
  	@example
  	$this->Model->restoreVersion(2); //restores version id 2
  	
  * @version: since 1.0
  * @author: Nick Baker
  * @link: http://www.webtechnick.com
  */
App::uses('AuthComponent', 'Controller/Component');
class VersionableBehavior extends ModelBehavior {
	public $IcingVersion = null;
	/**
	* Setup the behavior
	*/
	public function setUp(Model $Model, $settings = array()){
		$settings = array_merge(array(
			'contain' => array(),
			'versions' => false,
			'bind' => false
		), (array)$settings);
		if(!$Model->Behaviors->attached('Containable')){
			$Model->Behaviors->attach('Containable');
		}
		$this->settings[$Model->alias] = $settings;
		$this->IcingVersion = ClassRegistry::init('Icing.IcingVersion');
	}
	
	/**
	* On save will version the current state of the model with containable settings.
	* @param Model model
	*/
	public function beforeSave(Model $Model, $options = array()){
		if(!isset($options['icing_restore']) || (isset($options['icing_restore']) && $options['icing_restore'])){
			$this->saveVersion($Model);
		}
		return $Model->beforeSave();
	}
	
	/**
	* Add the association on find if they turn on the association in settings
	* Only adds the bind if contained in the query
	* @param Model model
	* @param array query
	*/
	public function beforeFind(Model $Model, $query = array()){
		if($this->settings[$Model->alias]['bind']){
			//Only contain if we have it contained, or if no contain is specified
			if(!isset($query['contain']) || (isset($query['contain']) && in_array('IcingVersion', $query['contain']))){
				$Model->bindModel(array(
					'hasMany' => array(
						'IcingVersion' => array(
							'className' => 'Icing.IcingVersion',
							'foreignKey' => 'model_id',
							'conditions' => array("IcingVersion.model" => $Model->alias)
						)
					)
				));
			}
		}
		return $Model->beforeFind($query);
	}
	
	/**
	* Version the delete, mark as deleted in Versionable
	* @param Model model
	* @param boolean cascade
	*/
	public function beforeDelete(Model $Model, $cascade){
		$this->saveVersion($Model, $delete = true);
		return $Model->beforeDelete($cascade);
	}
	
	/**
	* Restore data from a version_id
	* @param int version id
	* @Param boolean create a new version on restore. boolean true
	* @return result of saveAll on model
	*/
	public function restoreVersion(Model $Model, $version_id, $create_new_version = true){
		$restore = $this->IcingVersion->findById($version_id);
		if(!empty($restore)){
			$model_data = json_decode($restore['IcingVersion']['json'], true);
			return ClassRegistry::init($restore['IcingVersion']['model'])->saveAll($model_data, array('icing_restore' => $create_new_version));
		}
		return false;
	}
	
	/**
	* Get the version data from the Model based on settings and deleting
	* this is used in beforeDelete and beforeSave
	* @param Model model
	* @param boolean deleted
	*/
	private function saveVersion(Model $Model, $delete = false){
		$model_id = 0;
		if($Model->id){
			$model_id = $Model->id;
		}
		if(isset($Model->data[$Model->alias][$Model->primaryKey]) && !empty($Model->data[$Model->alias][$Model->primaryKey])){
			$model_id = $Model->data[$Model->alias][$Model->primaryKey];
		}
		if($model_id && !isset($Model->data['icing_restore'])){
			$data = $Model->data; //cache the data incase the model has some afterfind stuff that sets data
			$current_data = $Model->find('first', array(
				'conditions' => array("{$Model->alias}.{$Model->primaryKey}" => $model_id),
				'contain' => $this->settings[$Model->alias]['contain']
			));
			$version_data = array(
				'user_id' => AuthComponent::user('id'),
				'model_id' => $model_id,
				'model' => $Model->alias,
				'json' => json_encode($current_data),
				'is_delete' => $delete
			);
			$Model->data = $data;
			$this->IcingVersion->create();
			return $this->IcingVersion->saveVersion($version_data, $this->settings[$Model->alias]['versions']);
		}
		return false;
	}
}