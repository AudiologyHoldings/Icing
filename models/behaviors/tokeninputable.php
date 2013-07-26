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
	 * @param AppModel $model
	 * @param array $config
	 */
	public function setup(Model $model, $config = array()) {
		$this->settings[$model->alias] = array_merge($this->_defaults, $config);
	}

	/**
	 * Before Save Callback
	 *
	 * reformat data as needed, so saveAll() can handle it as native code
	 *
	 * HABTM only
	 * hasMany done in afterSave on a custom functionality
	 *
	 * @param appmodel $model model instance
	 * @return boolean true to continue, false to abort the save
	 * @access public
	 */
	public function beforeSave($Model) {
		#extract($this->settings[$Model->alias]);
		foreach ($Model->hasAndBelongsToMany as $assocName => $assocConf) {
			$saved = $this->prepHABTM($Model, $assocName, $assocConf);
		}
	}

	/**
	 * Before save method. Called before all saves
	 *
	 * Overriden to transparently translate any association fields name->id
	 *
	 * hasMany only
	 * HABTM done in beforeSave + saveAll
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
		// remove "thisId" from all records
		$removed = $Model->{$assocName}->updateAll(
			array("{$assocName}.{$foreignKey}" => '""'),
			array("{$assocName}.{$foreignKey}" => $thisId)
		);
		// now add "thisId" to all the tokenInput records
		$added = $Model->{$assocName}->updateAll(
			array("{$assocName}.{$foreignKey}" => '"' . $thisId  . '"'),
			array("{$assocName}.{$foreignKey}" => $submittedIds)
		);
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

}

