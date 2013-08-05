<?php
/**
 * Typeahead functionality is a bit tricky because
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
class TypeaheadableBehavior extends ModelBehavior {

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

	public function beforeValidate($Model) {
		return $this->beforeSave($Model);
	}
	/**
	 * Before save method. Called before all saves
	 *
	 * Overriden to transparently translate any association fields name->id
	 *
	 * @param AppModel $Model Model instance
	 * @return boolean true to continue, false to abort the save
	 * @access public
	 */
	public function beforeSave($Model) {
		extract($this->settings[$Model->alias]);
		if (!$Model->id) {
			// add (new record)
		}
		foreach ($Model->belongsTo as $assocName => $assocConf) {
			$fkid = $this->nameToId($Model, $assocName, $assocConf['foreignKey']);
		}
		foreach ($Model->hasOne as $assocName => $assocConf) {
			$fkid = $this->nameToId($Model, $assocName, $Model->{$assocName}->primaryKey);
			#debug(compact('fkid', 'assocName', 'assocConf'));
		}
		// allow save to continue...
		return true;
	}

	/**
	 * Transalate a single node, from one model to another...
	 *
	 * Looks for the foreignKey value
	 *   checks if it's an id, if so, return it
	 *   checks if it's searchable to find an id, if so, return the id
	 *   attempts to create a new records, and if it does, return the (new) id
	 *     looks for a method call typeaheadNew(), if not found calls save()
	 *
	 * @param AppModel $Model Model instance
	 * @param string $assocName (model name/alias)
	 * @param string $foreignKey (field name to look for in data)
	 * @return mixed $id or false
	 *   if found, also sets the value onto the Model->data
	 */
	public function nameToId($Model, $assocName, $foreignKey) {
		if (empty($Model->data[$Model->alias][$foreignKey])) {
			// no value for this association foreignKey
			#debug(compact('assocName', 'foreignKey'));
			return false;
		}
		$fkname = $fkid = $Model->data[$Model->alias][$foreignKey];
		$Model->{$assocName}->id = $fkid;
		if ($Model->{$assocName}->exists($fkid)) {
			// record is an id and exists
			#debug(compact('assocName', 'foreignKey', 'fkid'));
			return $fkid;
		}
		// not existing, lookup the id
		//   using the CakeDC Search plugin so that you can easily
		//   customize this search on the model if you need to
		$args = array(
			$foreignKey => $fkname,
			'term' => $fkname,
		);
		// verify that we end up with conditions
		//   if not, Search plugin not setup correctly
		if (empty($Model->{$assocName}->filterArgs)) {
			die('TypeaheadableBehavior::nameToId() on the Model: ' . $assocName . ' the filterArgs are missing, they are needed for searchable');
			throw new OutOfBoundsException('TypeaheadableBehavior::nameToId() on the Model: ' . $assocName . ' the filterArgs are missing, they are needed for searchable');
		}
		$conditions = $Model->{$assocName}->parseCriteria($args);
		if (empty($conditions)) {
			die('TypeaheadableBehavior::nameToId() conditions are empty, configure ' . $assocName . ' filterArgs for  ' . $foreignKey);
			throw new OutOfBoundsException('TypeaheadableBehavior::nameToId() conditions are empty, configure ' . $assocName . ' filterArgs for  ' . $foreignKey);
		}
		$fkid = $Model->{$assocName}->field($Model->{$assocName}->primaryKey, $conditions);
		if (!empty($fkid)) {
			// found the id, translate
			$Model->data[$Model->alias][$foreignKey] = $fkid;
			return $fkid;
		}
		// not found, add a new record (if we can)
		$save = array($assocName => array(
			$Model->{$assocName}->displayField => $fkname,
		));
		$Model->{$assocName}->create(false);
		$method = (method_exists($Model->{$assocName}, 'typeaheadNew') ? 'typeaheadNew' : 'save');
		if ($Model->{$assocName}->{$method}($save)) {
			$fkid = $Model->{$assocName}->id;
		}
		if (!empty($fkid)) {
			// found the id, translate
			$Model->data[$Model->alias][$foreignKey] = $fkid;
			return $fkid;
		}
		// unable to save, unable to find... :(
		return false;
	}

	/**
	 * Translate the record's Typeaheadable fields from $id => $name
	 * this is not automatic on the find() but instead a post-processing method
	 * you'd have to call from the controller (or after the record retrieval)
	 *
	 * @param array $record
	 * @param array $fields an option list of fields to restrict to
	 * @return array $record
	 */
	public function idsToNames($Model, $record, $fields = null) {
		// is this a find all?
		if (Set::numeric(array_values($record))) {
			foreach (array_keys($record) as $i) {
				$record[$i] = $this->idsToNames($Model, $record, $fields);
			}
			return $record;
		}
		// is this not a valid find first?
		if (empty($record[$Model->alias][$Model->primaryKey])) {
			// fail fast, return input
			return $record;
		}
		// look for a "name" to replace the "id" for all "belongsTo"
		foreach ($Model->belongsTo as $assocName => $assocConf) {
			$foreignKey = $assocConf['foreignKey'];
			if (!empty($fields) && !in_array($foreignKey, $fields)) {
				// not in "allowed" fields, don't translate
				continue;
			}
			$record = $this->swapIdForNameIfFound($Model, $record, $assocName, $foreignKey);
		}
		foreach ($Model->hasOne as $assocName => $assocConf) {
			$foreignKey = $assocConf['foreignKey'];
			if (!empty($fields) && !in_array($foreignKey, $fields)) {
				// not in "allowed" fields, don't translate
				continue;
			}
			$record = $this->swapIdForNameIfFound($Model, $record, $assocName, $foreignKey);
		}
		return $record;
	}

	/**
	 * This is the individual "per field" lookup and find method for
	 * translating an "id" into a "name"
	 *
	 * @param AppModel $Model Model instance
	 * @param array $record find 'first' single record
	 * @param string $assocName (model name/alias)
	 * @param string $foreignKey (field name to look for in data)
	 * @return mixed $id or false
	 */
	public function swapIdForNameIfFound($Model, $record, $assocName, $foreignKey) {
		if (empty($foreignKey) || empty($record[$Model->alias][$foreignKey])) {
			// unable to find the ID
			return $record;
		}
		$fkid = $record[$Model->alias][$foreignKey];
		if (!empty($record[$assocName][($Model->{$assocName}->displayField)])) {
			$fkname = $record[$assocName][($Model->{$assocName}->displayField)];
		}
		$fkname = $Model->{$assocName}->field(
			$Model->{$assocName}->displayField,
			array($assocName . '.' . $Model->{$assocName}->primaryKey => $fkid)
		);
		if (!empty($fkname)) {
			$record[$Model->alias][$foreignKey . '_typeahead'] = $fkid;
			$record[$Model->alias][$foreignKey] = $fkname;
		}
		return $record;
	}

	/**
	 * Most autocompletes/typeaheads want the field "name"
	 * which may not be the same as this Model's displayField
	 *
	 * if not, this will translate
	 *
	 * @param AppModel $Model
	 * @param array $data find all or first
	 * @param boolean $unsetToo if true, will unset the displayField from results
	 */
	public function displayFieldToName($Model, $data, $unsetToo = true, $targetField = 'name') {
		if ($Model->displayField == $targetField) {
			// done already
			return $data;
		}
		// is this a find all?
		if (Set::numeric(array_keys($data))) {
			foreach (array_keys($data) as $i) {
				$data[$i] = $this->displayFieldToName($Model, $data[$i], $unsetToo, $targetField);
			}
			return $data;
		}
		// find first, single record
		if (empty($data[$Model->alias][$Model->displayField])) {
			$value = '';
		} else {
			$value = $data[$Model->alias][$Model->displayField];
		}
		$data[$Model->alias][$targetField] = $value;
		if ($unsetToo) {
			unset($data[$Model->alias][$Model->displayField]);
		}
		return $data;
	}
}

