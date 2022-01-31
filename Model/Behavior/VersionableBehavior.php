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
 *
 * @example
 *
 * public $actsAs = array('Icing.Versionable');
 *
 * public $actsAs = array('Icing.Versionable' => array(
 * 	'contain'          => array('Hour'), // contains for relative model to be saved.
 * 	'versions'         => '5',           // how many version to save at any given time (false by default unlimited)
 * 	'minor_timeframe'  => '10',          // Mark as minor_version if saved within 10 seconds of last version.  Easily cleanup minor_versions
 * 	'bind'             => false,         // if true, attach IcingVersionable as HasMany relationship for you onFind and if contained
 * 	'check_identical'  => false,         // if true, version is marked as minor, if the data is identical to last version
 * 	'ignore_identical' => false,         // if true, no version is created, if the data is identical to last version
 * 	'useDbConfig'      => null,          // if not null, we switch the IcingVersion Model to this DB config key ('default' inherited from AppModel)
 * 	'soft_delete'      => false,         // If true, will soft delete instead of delete on run.
 * ));
 *
 * Or for a global Configure::read() solution
 *
 * Configure::write('Icing.Versionable.config', ['useDbConfig' => 'something']);
 *
 * Restore from Previous Version
 * @example
 *
 * $this->Model->restoreVersion('50537471-ba08-44ae-a606-24e5e017215a'); // restores version id 50537471-ba08-44ae-a606-24e5e017215a
 * $this->Model->restoreVersion('50537471-ba08-44ae-a606-24e5e017215a', false); // restores version id 50537471-ba08-44ae-a606-24e5e017215a and won't create a new version before restoring.
 * $this->Model->restoreVersion(2, 3); // restores the second version back from most recent on Model id 3
 * $this->Model->restoreVersion(2, 3, false); // restores the second version back from most recent on Model id 3 and doesn't create a new version before saving
 *
 * Diffs from a version
 * @example
 *
 * $result = $this->Model->diffVersion('50537471-ba08-44ae-a606-24e5e017215a'); // Gets the diff between version id and the curent state of the record.
 * $result = $this->Model->diffVersion('50537471-ba08-44ae-a606-24e5e017215a', '501234121-ba08-44ae-a606-2asdf767a'); // Gets the diff between two different versions.
 *
 * Saving without making a version
 * @example
 * $this->Model->save($data, array('create_version' => false));
 *
 *
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
	 *
	 * @param Model model
	 * @param array $settings
	 * @return void
	 */
	public function setUp(Model $Model, $settings = array()) {
		$defaults = array(
			'disabled'         => false,
			'contain'          => array(),
			'versions'         => false,
			'minor_timeframe'  => false,
			'bind'             => false,
			'check_identical'  => false,
			'ignore_identical' => false,
			'useDbConfig'      => null,
			'soft_delete'      => false,
		);
		$config = Configure::read('Icing.Versionable.config');
		if (!empty($config) && is_array($config)) {
			$defaults = array_merge($defaults, $config);
		}
		$this->settings[$Model->alias] = array_merge($defaults, (array)$settings);

		// Check if enabled from setup, shortcut loading anything we don't need.
		if ($this->isDisabled($Model)) {
			return;
		}

		// initialize the IcingVersion model onto this Behavior for easy access
		$this->IcingVersion = ClassRegistry::init('Icing.IcingVersion');

		// modify this instance of the IcingVersion Model if useDbConfig is set & not = test
		if (!empty($settings['useDbConfig'])) {
			// if we are testing... we want to "stay in test"
			//   done this way to aid in unit testing
			if ($this->IcingVersion->useDbConfig == 'test') {
				// $Model->wouldHaveSetIcingVersion = $settings['useDbConfig'];
				$settings['useDbConfig'] = 'test';
			}
			$this->IcingVersion->setDataSource($settings['useDbConfig']);
		}

		// auto attach Containable
		if (!$Model->Behaviors->attached('Containable')) {
			$Model->Behaviors->attach('Containable');
		}
	}

	/**
	 * On save will version the current state of the model with containable settings.
	 *
	 * @param Model model
	 * @param array $options
	 * @return boolean
	 */
	public function beforeSave(Model $Model, $options = array()) {
		if ($this->isDisabled($Model)) {
			// skipping not enabled.
			return true;
		}
		if (array_key_exists('create_version', $options) && empty($options['create_version'])) {
			// skipping
			return true;
		}
		if (!$this->saveVersion($Model)) {
			// error?
			//throw new OutOfBoundsException('IcingVersionable unable to save version');
			//return false; // stops save
		}
		return true;
	}

	/**
	 * Add the association on find if they turn on the association in settings
	 * Only adds the bind if contained in the query
	 *
	 * @param Model model
	 * @param array query
	 * @return array $query
	 */
	public function beforeFind(Model $Model, $query = array()) {
		if ($this->isDisabled($Model)) {
			return $Model->beforeFind($query);
		}
		if (!$this->settings[$Model->alias]['bind']) {
			return $Model->beforeFind($query);
		}

		//Only contain if we have it contained, or if no contain is specified
		if (isset($query['contain']) && !in_array('IcingVersion', $query['contain']) && !key_exists('IcingVersion', $query['contain'])) {
			return $Model->beforeFind($query);
		}

		$bind_options = array();
		if (isset($query['contain']['IcingVersion'])) {
			$bind_options = $query['contain']['IcingVersion'];
		}
		$this->bindIcingVersion($Model, $bind_options);

		return $Model->beforeFind($query);
	}

	/**
	 * Bind the icing model on demand, this is useful right before a call in which you want to contain
	 * but don't have the association.
	 *
	 * @param Model model
	 * @param array $options
	 * @return boolean
	 */
	public function bindIcingVersion(Model $Model, $options = array()) {
		$defaults = array(
			'className' => 'Icing.IcingVersion',
			'foreignKey' => 'model_id',
			'conditions' => array("IcingVersion.model" => $Model->alias)
		);
		if (isset($this->settings[$Model->alias]['soft_delete']) && $this->settings[$Model->alias]['soft_delete']) {
			$defaults['conditions']['IcingVersion.is_soft_delete'] = false;
		}

		$options = array_merge($defaults, (array) $options);

		return $Model->bindModel(array('hasMany' => array('IcingVersion' => $options)));
	}

	/**
	 * Version the delete, mark as deleted in Versionable
	 *
	 * @param Model model
	 * @param boolean cascade
	 * @return boolean
	 */
	public function beforeDelete(Model $Model, $cascade = true) {
		if ($this->isDisabled($Model)) {
			return $Model->beforeDelete($cascade);
		}
		$this->saveVersion($Model, $delete = true);
		return $Model->beforeDelete($cascade);
	}

	/**
	 * Restore data from a version_id
	 *
	 * @example
	 *  $this->Model->restoreVersion('50537471-ba08-44ae-a606-24e5e017215a'); //restores version id 50537471-ba08-44ae-a606-24e5e017215a
	 *  $this->Model->restoreVersion('50537471-ba08-44ae-a606-24e5e017215a', false); //restores version id 50537471-ba08-44ae-a606-24e5e017215a and won't create a new version before restoring.
	 *  $this->Model->restoreVersion(2, 3); //restores the second version back from most recent on Model id 3
	 *  $this->Model->restoreVersion(2, 3, false); //restores the second version back from most recent on Model id 3 and doesn't create a new version before saving
	 *
	 * @param int $version_id or the $count_of_versions_ago
	 * @param model_id of the id to reversion if version is an int.
	 * @param boolean create a new version on restore. boolean true
	 * @return boolean result of saveAll on model
	 */
	public function restoreVersion(Model $Model, $version_id, $model_id = 0, $create_new_version = true) {
		if ($model_id === false) {
			$create_new_version = false;
			$model_id = 0;
		}
		$restore = false;
		if (is_numeric($version_id) && !empty($model_id)) {
			$restore = $this->IcingVersion->findVersionBack($Model->alias, $version_id, $model_id);
		} else {
			$restore = $this->IcingVersion->findById($version_id);
		}

		if (!empty($restore)) {
			$model_data = json_decode($restore['IcingVersion']['json'], true);
			if ($Model->alias == $restore['IcingVersion']['model']) {
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
	 *
	 * @param Model model
	 * @param uuid version to diff against
	 * @param mixed
	 * @return array $diff
	 */
	public function diffVersion(Model $Model, $one_version_id, $two_version_id = null) {
		return $this->IcingVersion->diff($Model->alias, $one_version_id, $two_version_id, $this->settings[$Model->alias]);
	}

	/**
	 * Get the version data from the Model based on settings and deleting
	 * this is used in beforeDelete and beforeSave
	 *
	 * @param Model model
	 * @param boolean deleted
	 * @return boolean
	 */
	private function saveVersion(Model $Model, $delete = false) {
		$model_id = 0;
		$Model->oldData = null;
		$Model->versionData = null;
		if ($Model->id) {
			$model_id = $Model->id;
		}
		if (isset($Model->data[$Model->alias][$Model->primaryKey]) && !empty($Model->data[$Model->alias][$Model->primaryKey])) {
			$model_id = $Model->data[$Model->alias][$Model->primaryKey];
		}
		if (empty($model_id)) {
			return false;
		}
		if (isset($Model->data['create_version'])) {
			return false;
		}

		// cache the data in case the model has some afterfind stuff that sets data
		$data = $Model->data;

		// find the oldData / the Current Data before this version is saved
		$Model->oldData = $Model->find('first', array(
			'conditions' => array("{$Model->alias}.{$Model->primaryKey}" => $model_id),
			'contain' => $this->settings[$Model->alias]['contain']
		));
		$Model->versionData = array(
			'user_id' => 0,
			'model_id' => $model_id,
			'model' => $Model->alias,
			'json' => json_encode($Model->oldData),
			'is_delete' => $delete,
			'url' => IcingUtil::getUrl(),
			'ip' => IcingUtil::getIP(),
			'is_minor_version' => false,
			'is_soft_delete' => false,
		);

		if (AuthComponent::user('is_clinic')) {
			// Do nothing.  User ID stays as 0.
		} else if (method_exists($Model, 'getUserId')) {
			$Model->versionData['user_id'] = $Model->getUserId();
		} elseif (class_exists('AuthComponent') && class_exists('CakeSession') && CakeSession::started()) {
			$Model->versionData['user_id'] = AuthComponent::user('id');
		}

		// save the version record
		$output = $this->IcingVersion->saveVersion($Model->versionData, $this->settings[$Model->alias]);
		$Model->versionData['id'] = $this->IcingVersion->id;

		// reset data to the initial value (just in case it got reset somehow)
		$Model->data = $data;

		// return the boolean output (saved version)
		return $output;
	}

	/**
	 * Adds error text to errors array
	 *
	 * @param Model model
	 * @param string message to append
	 * @return void
	 */
	private function addError(Model $Model, $message) {
		if (!isset($this->errors[$Model->alias])) {
			$this->errors[$Model->alias] = array();
		}
		$this->errors[$Model->alias][] = $message;
	}

	/**
	 * Get old data, get the last copy of the data before it was saved
	 * (only works for the last save, subsequent saves will overwrite)
	 *
	 * TODO: may implement as full getter/setter pattern
	 *   (but it's not close to stateless right now anyway)
	 *
	 * @param Model model
	 * @return array $oldData
	 */
	public function getDataBeforeSave(Model $Model) {
		return $Model->oldData;
	}

	/**
	 * Simple access to the IcingVersionable->settings for this model, from the model
	 *
	 * @param Model model
	 * @return array $settings
	 */
	public function getIcingVersionSettings(Model $Model) {
		return $this->settings[$Model->alias];
	}

	/**
	 * Simple access to the IcingVersionable->IcingVersion Model, from the model
	 *
	 * @param Model model
	 * @return Model $IcingVersion
	 */
	public function getIcingVersion(Model $Model) {
		return $this->IcingVersion;
	}

	/**
	 * Simple boolean check if behavior is enabled
	 *
	 * @param  Model   $model [description]
	 * @return boolean        [description]
	 */
	public function isDisabled(Model $Model) {
		return !! $this->settings[$Model->alias]['disabled'];
	}

}
