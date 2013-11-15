<?php
/**
 * Tokeninput functionality is a bit tricky because
 * it sends the "name" of the * option instead of the "id"
 *
 * So on save, we have to translate the "name" to the "id" and we have to know
 * what fields to do that for (settings based whe initializing the model)
 *
 * Likewise, when loading an edit form, it'd be really nice to translate the
 * "id" into the "name" such that the display looks how you think it should
 * look.
 *
 *
 */
class TokeninputableBehavior extends ModelBehavior {

	/**
	 * settings indexed by model name.
	 *
	 * @var array
	 */
	public $settings = array();

	/**
	 * temp data placeholder, for data extracted for custom save functionality
	 *
	 * @var array
	 */
	public $data = array();

	/**
	 * Default settings
	 *
	 * @var string
	 */
	protected $_defaults = array(
		'primaryKey' => null, // will derive from model->primaryKey
		'displayField' => null, // will derive from model->displayField
	);

	/**
	 * Configuration of model
	 *
	 * @param AppModel $Model
	 * @param array $config
	 */
	public function setUp(Model $Model, $settings = array()) {
		$this->settings[$Model->alias] = array_merge($this->_defaults, $settings);
		return parent::setUp($Model, $settings);
	}

	/**
	 * Reformat data on model, as needed for this method
	 *
	 * NOTE: Needs to happen before Validate Callback
	 *   validation callback doesn't allow us to alter the data passed into saveAll() {in CakePHP 1.3}
	 *   therefore, we need to call like this:
	 *
	 *    $this->create(false);
	 *    $this->set($data);
	 *    // Tokeninputable needs to modify $Model->data, but beforeValidate
	 *    // doesn't let it happen "fast enough" (also sets to $Model->data)
	 *    $data = $this->dataCleanupTokenInput($data);
	 *    $result = $this->saveAll($data);
	 *
	 *
	 *
	 * reformat data as needed,
	 *   extract any data to be saved via TokeninputableBehavior from $Model->data
	 *   so validate() will pass (in the case of nesting and reformatting)
	 *   so saveAll() can handle it as native code
	 *
	 * HABTM = reformatting
	 * hasMany = extracted ~ done in afterSave on a custom functionality
	 *
	 * @param appmodel $model model instance
	 * @param mixed $data optionally pass in $data here, sets to model
	 * @return boolean true to continue, false to abort the save
	 * @access public
	 */
	public function dataCleanupTokenInput($Model, $data=null) {
		#extract($this->settings[$Model->alias]);
		if (!empty($data)) {
			$Model->set($data);
		}
		foreach ($Model->hasMany as $assocName => $assocConf) {
			$this->prepHasMany($Model, $assocName, $assocConf);
		}
		foreach ($Model->hasAndBelongsToMany as $assocName => $assocConf) {
			$this->prepHABTM($Model, $assocName, $assocConf);
		}
		return $Model->data;
	}

	/**
	 * Before Validate callback, ensures dataCleanupTokenInput is called
	 *
	 * @return boolean
	 */
	public function beforeValidate($Model) {
		$this->dataCleanupTokenInput($Model);
		return true;
	}

	/**
	 * Before Save callback, ensures dataCleanupTokenInput is called
	 *
	 * @return boolean
	 */
	public function beforeSave($Model) {
		$this->dataCleanupTokenInput($Model);
		return true;
	}

	/**
	 * After Save callback,
	 * actually saves all hasMany nodes, stored in $this->data
	 *
	 * NOTE: HABTM done in normal saveAll, reformatted in prepHABTM() & dataCleanupTokenInput()
	 *
	 * @param appmodel $model model instance
	 * @return boolean true to continue, false to abort the save
	 * @access public
	 */
	public function afterSave($Model) {
		#extract($this->settings[$Model->alias]);
		if (!$Model->id) {
			throw new OutOfBoundsException('TokeninputableBehavior::afterSave() missing the id');
		}
		foreach ($Model->hasMany as $assocName => $assocConf) {
			$saved = $this->saveHasMany($Model, $assocName, $assocConf['foreignKey']);
		}
		// allow save to continue...
		return true;
	}

	/**
	 * Verify save for a single model
	 *
	 * Looks for submitted data with information for the associated model
	 *   checks to see if the data is a CSV string
	 *     if it is, we clear out other records
	 *     and update/inject for these records
	 *
	 * @param AppModel $Model Model instance
	 * @param string $assocName (model name/alias)
	 * @param string $foreignKey (field name to look for in data)
	 * @return boolean
	 */
	public function saveHasMany($Model, $assocName, $foreignKey) {
		// data should have already been extracted to this storage path
		if (empty($this->data[$Model->alias]['hasManyNodes'][$assocName])) {
			// no value for this association
			return false;
		}
		// extract data from the storage container on this Behaviour
		$data = $this->data[$Model->alias]['hasManyNodes'][$assocName];
		// reset and clear hasManyNodes
		unset($this->data[$Model->alias]['hasManyNodes'][$assocName]);
		if (!is_string($data)) {
			// not a CSV string, which is what tokeninput would give you
			return false;
		}
		$assocPK = $Model->{$assocName}->primaryKey;
		$thisId = $Model->id;
		$submittedIds = array_unique(explode(',', trim($data)));
		// remove "thisId" from all records
		$removed = $Model->{$assocName}->updateAll(
			array("{$assocName}.{$foreignKey}" => '""'),
			array("{$assocName}.{$foreignKey}" => $thisId)
		);
		// now add "thisId" to all the tokenInput records
		$added = $Model->{$assocName}->updateAll(
			array("{$assocName}.{$foreignKey}" => '"' . $thisId  . '"'),
			array("{$assocName}.{$assocPK}" => $submittedIds)
		);
		//debug(compact('removed', 'added', 'thisId', 'submittedIds', 'assocName', 'foreignKey', 'assocPK'));die();
		return true;
	}

	/**
	 * HABTM associations are a bit tricky... we could do a custom save for
	 * them "aftersave"
	 * but there is no need to recreate the logic of a HABTM save
	 * (or the keep-existing variant)
	 *
	 * Instead, we just prep the data, reformat it into the expected value
	 * format and let saveAll() work
	 *
	 * NOTE: if you do a save, not a saveAll, this wouldn't work, but it also
	 * wont hurt anything.
	 *
	 * @param AppModel $Model Model instance
	 * @param string $assocName (model name/alias)
	 * @param array $assocConf
	 * @return boolean
	 */
	public function prepHABTM($Model, $assocName) {
		if (empty($Model->data[$assocName][$assocName])) {
			// no value for this association
			return false;
		}
		$data = $Model->data[$assocName][$assocName];
		if (!is_string($data)) {
			// not a CSV string, which is what tokeninput would give you
			return false;
		}
		$thisId = $Model->id;
		$submittedIds = array_unique(explode(',', trim($data)));
		// just set the data back into the expected format
		$Model->data[$assocName][$assocName] = $submittedIds;
		return true;
	}

	/**
	 * HasMany data needs to be extracted, removed from the Model->data
	 * and stored for processing in afterSave()
	 *
	 * SaveAll doesn't validate these hasOne nodes...
	 *
	 * @param AppModel $Model Model instance
	 * @param string $assocName (model name/alias)
	 * @param array $assocConf
	 * @return boolean
	 */
	public function prepHasMany($Model, $assocName, $assocConf) {
		if (empty($Model->data[$assocName][$assocName])) {
			// no value for this association
			return false;
		}
		// set this data for later use
		$this->data[$Model->alias]['hasManyNodes'][$assocName] = $Model->data[$assocName][$assocName];
		// unset this data from Model->data
		unset($Model->data[$assocName][$assocName]);
		// clean up, if this is now empty, delete completly
		if (empty($Model->data[$assocName])) {
			unset($Model->data[$assocName]);
		}
		// done
		return true;
	}

}

