<?php
/**
  * Attach to any model to creating versions of current state on save for later restoration
  *
  * To determine the User making the save:
  *   uses the $Model->getUserId() if the method exists (could implement in AppModel)
  *   uses the AuthComponent::user() if the class exists (App::uses('AuthComponent', 'Controller/Component'))
  *   else, user_id = 0
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
  		'contain'         => array('Hour'), //contains for relative model to be saved.
  		'versions'        => '5',           //how many version to save at any given time (false by default unlimited)
  		'minor_timeframe' => '10',          //Mark as minor_version if saved within 10 seconds of last version.  Easily cleanup minor_versions
  		'bind'            => true,          //attach Versionable as HasMany relationship for you onFind and if contained
  	));

  	Restore from Previous Version
  	@example
  	$this->Model->restoreVersion('50537471-ba08-44ae-a606-24e5e017215a'); //restores version id 50537471-ba08-44ae-a606-24e5e017215a
  	$this->Model->restoreVersion('50537471-ba08-44ae-a606-24e5e017215a', false); //restores version id 50537471-ba08-44ae-a606-24e5e017215a and won't create a new version before restoring.
  	$this->Model->restoreVersion(2, 3); //restores the second version back from most recent on Model id 3
  	$this->Model->restoreVersion(2, 3, false); //restores the second version back from most recent on Model id 3 and doesn't create a new version before saving

  	Diffs from a version
  	@example
  	$result = $this->Model->diffVersion('50537471-ba08-44ae-a606-24e5e017215a'); //Gets the diff between version id and the curent state of the record.
		$result = $this->Model->diffVersion('50537471-ba08-44ae-a606-24e5e017215a', '501234121-ba08-44ae-a606-2asdf767a'); //Gets the diff between two different versions.

		Saving without making a version
		@example
		$this->Model->save($data, array('create_version' => false));

  * @version: since 1.0
  * @author: Nick Baker
  * @link: http://www.webtechnick.com
  */
App::import('Component', 'Auth');
//App::uses('AuthComponent', 'Controller/Component');
App::uses('IcingUtil', 'Icing.Lib');
class VersionableBehavior extends ModelBehavior {
	public $IcingVersion = null;
	public $errors = array();
	/**
	* Setup the behavior
	*/
	public function setUp(Model $Model, $settings = array()){
		$settings = array_merge(array(
			'contain' => array(),
			'versions' => false,
			'minor_timeframe' => false,
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
		if(!isset($options['create_version']) || (isset($options['create_version']) && $options['create_version'])){
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
			if(!isset($query['contain']) || (isset($query['contain']) && (in_array('IcingVersion', $query['contain']) || key_exists('IcingVersion', $query['contain'])))){
				$bind_options = array(
					'className' => 'Icing.IcingVersion',
					'foreignKey' => 'model_id',
					'conditions' => array("IcingVersion.model" => $Model->alias)
				);
				if(isset($query['contain']['IcingVersion'])){
					$bind_options = array_merge($bind_options, $query['contain']['IcingVersion']);
				}
				$Model->bindModel(array(
					'hasMany' => array(
						'IcingVersion' => $bind_options
					)
				));
			}
		}
		return $Model->beforeFind($query);
	}

	/**
	* Bind the icing model on demand, this is useful right before a call in which you want to contain
	* but don't have the association.
	*/
	public function bindIcingVersion(Model $Model){
		$bind_options = array(
			'className' => 'Icing.IcingVersion',
			'foreignKey' => 'model_id',
			'conditions' => array("IcingVersion.model" => $Model->alias)
		);
		$Model->bindModel(array(
			'hasMany' => array(
				'IcingVersion' => $bind_options
			)
		));
	}

	/**
	* Version the delete, mark as deleted in Versionable
	* @param Model model
	* @param boolean cascade
	*/
	public function beforeDelete(Model $Model, $cascade = true){
		$this->saveVersion($Model, $delete = true);
		return $Model->beforeDelete($cascade);
	}

	/**
	* Restore data from a version_id
	* @param int version id
	* @param model_id of the id to reversion if version is an int.
	* @param boolean create a new version on restore. boolean true
	* @example
		$this->Model->restoreVersion('50537471-ba08-44ae-a606-24e5e017215a'); //restores version id 50537471-ba08-44ae-a606-24e5e017215a
  	$this->Model->restoreVersion('50537471-ba08-44ae-a606-24e5e017215a', false); //restores version id 50537471-ba08-44ae-a606-24e5e017215a and won't create a new version before restoring.
  	$this->Model->restoreVersion(2, 3); //restores the second version back from most recent on Model id 3
  	$this->Model->restoreVersion(2, 3, false); //restores the second version back from most recent on Model id 3 and doesn't create a new version before saving
	* @return result of saveAll on model
	*/
	public function restoreVersion(Model $Model, $version_id, $model_id = 0, $create_new_version = true){
		if($model_id === false){
			$create_new_version = false;
			$model_id = 0;
		}
		$restore = false;
		if(strlen($version_id) == 36){
			$restore = $this->IcingVersion->findById($version_id);
		} elseif($model_id) {
			$restore = $this->IcingVersion->findVersionBack($Model->alias, $version_id, $model_id);
		}

		if(!empty($restore)){
			$model_data = json_decode($restore['IcingVersion']['json'], true);
			if($Model->alias == $restore['IcingVersion']['model']){
				return $Model->saveAll($model_data, array('create_version' => $create_new_version));
			} else {
				$this->addError($Model, "Restore found was for different model. {$restore['IcingVersion']['model']} != {$Model->alias}");
			}
		} else {
			$this->addError($Model, "No restore version found for params.");
		}
		return false;
	}

	/**
	* Return the diff of two versions of a paticular version
	* @param Model model
	* @param uuid version to diff against
	* @param mixed
	*/
	public function diffVersion(Model $Model, $one_version_id, $two_version_id = null){
		return $this->IcingVersion->diff($Model->alias, $one_version_id, $two_version_id, $this->settings[$Model->alias]);
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
		if($model_id && !isset($Model->data['create_version'])){
			$data = $Model->data; //cache the data incase the model has some afterfind stuff that sets data
			$current_data = $Model->find('first', array(
				'conditions' => array("{$Model->alias}.{$Model->primaryKey}" => $model_id),
				'contain' => $this->settings[$Model->alias]['contain']
			));
			$version_data = array(
				'user_id' => 0,
				'model_id' => $model_id,
				'model' => $Model->alias,
				'json' => json_encode($current_data),
				'is_delete' => $delete,
				'url' => IcingUtil::getUrl(),
				'ip' => IcingUtil::getIP(),
				'is_minor_version' => false,
			);
			if (method_exists($Model, 'getUserId')) {
				$version_data['user_id'] = $Model->getUserId();
			} elseif (class_exists('AuthComponent') && class_exists('CakeSession') && CakeSession::started()) {
				$version_data['user_id'] = AuthComponent::user('id');
			}
			$Model->data = $data;
			return $this->IcingVersion->saveVersion($version_data, $this->settings[$Model->alias]);
		}
		return false;
	}

	/**
	* Adds error text to errors array
	* @param Model model
	* @param string message to append
	*/
	private function addError(Model $Model, $message){
		if(!isset($this->errors[$Model->alias])){
			$this->errors[$Model->alias] = array();
		}
		$this->errors[$Model->alias][] = $message;
	}
}
