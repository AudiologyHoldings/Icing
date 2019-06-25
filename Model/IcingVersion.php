<?php
App::uses('IcingAppModel', 'Icing.Model');
App::uses('Set', 'Utility');
App::uses('Hash', 'Utility');
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
			'notBlank' => array(
				'rule' => array('notBlank'),
			),
		),
		'json' => array(
			'notBlank' => array(
				'rule' => array('notBlank'),
			),
		),
		'is_delete' => array(
			'boolean' => array(
				'rule' => array('boolean'),
			),
		),
	);

	public function __construct($id = false, $table = null, $ds = null)
	{
		parent::__construct($id, $table, $ds);

		if (version_compare(Configure::version(), '2.7', '>=')) {
			$this->validate['model']['notBlank']['rule'] = $this->validate['json']['notBlank']['rule'] = ['notBlank'];
		}
	}

	/**
	 * Finds the version back from curent based by number count
	 *
	 * @param string $model alias
	 * @param int $number version number 1-infinity
	 * @param string $model_id model primaryKey
	 * @return array findFirst
	 */
	public function findVersionBack($model = null, $number = 0, $model_id = 0) {
		if ($number && $model_id && $model) {
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
	 * Soft delete the IcingVersion
	 *
	 * @param  string $id UUID of version
	 * @return bool|array false on failed save, array of new version.
	 */
	public function softDelete($id) {
		$data = [
			'id' => $id,
			'is_soft_delete' => 1,
			'modified' => false, // don't update modified
		];
		return $this->save($data, [
			'callbacks' => false,
			'validate' => false,
			'counterCache' => false,
		]);
	}

	/**
	 * Run a diff between two different versions
	 * if the second version is null use the curent
	 *
	 * @param string $model alias
	 * @param string $one_version_id primaryKey
	 * @param string $second_version_id primaryKey
	 * @param array $settings
	 * @return mixed $diff or false
	 */
	public function diff($model, $one_version_id, $second_version_id = null, $settings = array()) {
		$settings = array_merge(array(
			'contain' => array()
		), (array)$settings);
		$one = $this->findById($one_version_id);
		if (empty($one)) {
			return false;
		}
		$one_data = json_decode($one['IcingVersion']['json'], true);
		if ($second_version_id) {
			// Second Version supplied. Look it up.
			$two = $this->findById($second_version_id);
			$two_data = json_decode($two['IcingVersion']['json'], true);
		} else {
			// Second version not supplied, guess.
			$conditions = array(
				"$model.id" => $one['IcingVersion']['model_id']
			);
			if (isset($settings['soft_delete']) && $settings['soft_delete']) {
				$conditions['is_soft_delete'] = false;
			}
			$two_data = ClassRegistry::init($model)->find('first',array(
				'conditions' => $conditions,
				'contain' => $settings['contain'],
			));
		}
		if (empty($one_data) || empty($two_data)) {
			return false;
		}
		if (empty($settings['contain'])) {
			return $this->_diff($two_data, $one_data);
		}
		$retval = array(
			$model => $this->_diff($two_data[$model], $one_data[$model])
		);
		// expand diff across all nested model's data, individually
		foreach($settings['contain'] as $model_key) {
			if (!array_key_exists($model_key, $two_data)) {
				$retval[$model_key] = array();
			} elseif (!array_key_exists($model_key, $one_data)) {
				$retval[$model_key] = $two_data[$model_key];
			} else {
				$retval[$model_key] = $this->_diff($two_data[$model_key], $one_data[$model_key]);
			}
		}
		return $retval;
	}

	/**
	 * Abstract for Hahs/Set/array_diff()
	 *
	 * @param array $one
	 * @param array $two
	 * @return array $diff
	 */
	private function _diff($one, $two) {
		if (class_exists('Hash')) {
			return Hash::diff($one, $two);
		}
		if (class_exists('Set')) {
			return Set::diff($one, $two);
		}
		return array_diff($one, $two);
	}

	/**
	 * Cleanup all minor versions on table
	 *
	 * @return boolean
	 */
	public function cleanUpMinorVersions() {
		return $this->deleteAll(array(
			'IcingVersion.is_minor_version' => true
		), true, true);
	}

	/**
	 * Cleanup all soft deletes on table
	 *
	 * @return boolean
	 */
	public function cleanupSoftDeleteVersions() {
		return $this->deleteAll(array(
			'IcingVersion.is_soft_delete' => true
		), false, false);
	}

	/**
	 * Save The Version data, but first check the limit, and delete the oldest based on created
	 * if limit is reached.
	 *
	 * @param array of data to save
	 * @param array of settings from VersionableBehavior
	 * - versions number of versions to save back on paticular record for model
	 * @return boolean
	 */
	public function saveVersion($data, $settings = array()) {
		$conditions = array(
			'model' => $data['model'],
			'model_id' => $data['model_id'],
		);

		// Only force extra condition if we have soft_deleting on.
		if (isset($settings['soft_delete']) && $settings['soft_delete']) {
			$conditions['is_soft_delete'] = 0;
		}

		// check if we should prune off old records for this model+id
		//   (limited by settings - versions count)
		if (!empty($settings['versions']) && is_numeric($settings['versions']) && $settings['versions'] > 0) {
			$count = $this->find('count', compact('conditions'));
			while ($count >= $settings['versions']) {
				$oldest = $this->find('first', array(
					'fields' => array('id'),
					'conditions' => $conditions,
					'order' => array("{$this->alias}.created ASC")
				));
				if (isset($settings['soft_delete']) && $settings['soft_delete']) {
					$this->softDelete($oldest[$this->alias]['id']);
				} else {
					$this->delete($oldest[$this->alias]['id']);
				}
				$count = $this->find('count', compact('conditions'));
			}
		}

		// check if this is a minor_revision based on the data.json=newest.json
		//   if this version of the data is an exact match to the most recently saved version
		if (!empty($settings['check_identical']) || !empty($settings['ignore_identical'])) {
			$newest = $this->find('first', array(
				'fields' => array('id', 'json'),
				'conditions' => $conditions,
				'order' => array("{$this->alias}.created" => "DESC")
			));
			if (!empty($newest[$this->alias]['id'])) {
				$data['is_minor_version'] = ($newest[$this->alias]['json'] == $data['json']);
			}
		}

		// check if this we should skip this version
		if (!empty($data['is_minor_version']) && !empty($settings['ignore_identical'])) {
			// skipping - the records are identical
			return true;
		}

		// check if this we should change prior version to minor_revision based on minor_timeframe from settings
		if (empty($data['is_minor_version']) && !empty($settings['minor_timeframe'])) {
			// find all the records which "should be minor"
			$versions_within_timeframe = $this->find('list', array(
				'fields' => array('id'),
				'conditions' => array_merge($conditions, array(
					'IcingVersion.created >=' => date("Y-m-d H:i:s", strtotime("-{$settings['minor_timeframe']} seconds", time())),
					'IcingVersion.is_minor_version' => false,
				)),
			));
			foreach ($versions_within_timeframe as $version_id) {
				$this->id = $version_id;
				$this->saveField('is_minor_version', true);
			}
		}

		// finally, save this record
		$this->create(false);
		if (!$this->save($data)) {
			return false;
		}
		return true;
	}
}
