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
 * TROUBLESHOOTING
 * ----------------
 *
 * Are you getting the "wrong" id back for a name input?
 *
 * Check your Searchable "args" --> "conditions" methods and filter setup.
 *
 * The easiest solution is to make a filter for the association "foreignKey"
 * field, and make that filter expect the "name" and treat it as a like or
 * value.
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
	 * Setup the behavior
	 *
	 * @param AppModel $Model Model instance
	 * @param array $settings
	 * @return boolean
	 */
	public function setUp(Model $Model, $settings = array()) {
		$this->settings[$Model->alias] = array_merge($this->_defaults, $settings);
		return parent::setUp($Model, $settings);
	}

	/**
	 * The same logic of beforeSave() should be applied beforeValidate()
	 * because the "name" may fail validation, where as the "id" wouldn't
	 *
	 * @param AppModel $Model Model instance
	 * @return boolean true to continue, false to abort the save
	 */
	public function beforeValidate(Model $Model, $options = array()) {
		return $this->beforeSave($Model, $options);
	}

	/**
	 * Before save method. Called before all saves
	 *
	 * Overriden to transparently translate any association fields name->id
	 *
	 * @param AppModel $Model Model instance
	 * @return boolean true to continue, false to abort the save
	 */
	public function beforeSave(Model $Model, $options = array()) {
		if (empty($Model->data)) {
			return true;
		}
		$resetId = $Model->id;
		extract($this->settings[$Model->alias]);
		foreach ($Model->belongsTo as $assocName => $assocConf) {
			$assocName = $assocConf['className'];
			$fkid = $this->nameToId($Model, 'belongsTo', $assocName, $assocConf['foreignKey']);
		}
		foreach ($Model->hasOne as $assocName => $assocConf) {
			$assocName = $assocConf['className'];
			$fkid = $this->nameToId($Model, 'hasOne', $assocName, $assocConf['foreignKey']);
		}
		// allow save to continue...
		$Model->id = $resetId;
		return true;
	}

	/**
	 * Returns the Associated Model based on $assocName
	 *
	 * @param AppModel $Model Model instance
	 * @param string $assocName
	 * @return AppModel $assocModel
	 */
	private function assocModel($Model, $assocName) {
		$alias = $Model->alias;
		if ($assocName == $alias) {
			return $Model;
		}
		return $Model->{$assocName};
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
	 * @param string $assocType (belongsTo or hasOne)
	 * @param string $assocName (model name/alias)
	 * @param string $foreignKey (field name to look for in data)
	 * @return mixed $id or false
	 *   if found, also sets the value onto the Model->data
	 */
	public function nameToId($Model, $assocType, $assocName, $foreignKey) {
		if ($assocType != 'belongsTo' && $assocType != 'hasOne') {
			#debug(compact('assocType', 'assocName'));
			return false;
		}
		if ($assocType == 'belongsTo') {
			if (empty($Model->data[$Model->alias][$foreignKey])) {
				// no value for the parent model
				$data = $Model->data;
				#debug(compact('assocType', 'assocName', 'foreignKey', 'data'));
				return false;
			}
			$fkname = $fkid = $Model->data[$Model->alias][$foreignKey];
		}
		if ($assocType == 'hasOne') {
			if (empty($Model->data[$assocName]['id'])) {
				// no value for the parent model
				$data = $Model->data;
				#debug(compact('assocType', 'assocName', 'foreignKey', 'data'));
				return false;
			}
			$fkname = $fkid = $Model->data[$assocName]['id'];
		}
		$assocModel = $this->assocModel($Model, $assocName);
		$assocModel->id = $fkid;
		if ($assocModel->exists($fkid)) {
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
		if (empty($assocModel->filterArgs)) {
			throw new OutOfBoundsException('TypeaheadableBehavior::nameToId() on the Model: ' . $assocName . ' the filterArgs are missing, they are needed for searchable');
		}
		$conditions = $assocModel->parseCriteria($args);
		if (empty($conditions)) {
			throw new OutOfBoundsException('TypeaheadableBehavior::nameToId() conditions are empty, configure ' . $assocName . ' filterArgs for  ' . $foreignKey);
		}
		$fkid = $assocModel->field($assocModel->primaryKey, $conditions);
		if (empty($fkid)) {
			// not found, add a new record (if we can)
			$fkid = $this->nameToNewId($Model, $assocName, $fkname);
		}
		if (!empty($fkid)) {
			// found the id, translate
			if ($assocType == 'belongsTo') {
				$Model->data[$Model->alias][$foreignKey] = $fkid;
				return $fkid;
			}
			if ($assocType == 'hasOne') {
				$Model->data[$assocType]['id'] = $fkid;
				return $fkid;
			}
		}
		// unable to save, unable to find... :(
		return false;
	}

	/**
	 * TypeaheadableBehavior can automatically create a new record from your
	 * passed in data.
	 *
	 * @param AppModel $Model
	 * @param string $assocName
	 * @param string $fkname
	 * @return mixed $fkid or false
	 */
	public function nameToNewId($Model, $assocName, $fkname) {
		$assocModel = $this->assocModel($Model, $assocName);
		$save = array($assocName => array(
			$assocModel->displayField => $fkname,
		));
		$assocModel->create(false);
		$method = (method_exists($assocModel, 'typeaheadNew') ? 'typeaheadNew' : 'save');
		if ($assocModel->{$method}($save)) {
			return $assocModel->id;
		}
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
			$record = $this->injectNameField($Model, $record, $assocName, $foreignKey);
		}
		foreach ($Model->hasOne as $assocName => $assocConf) {
			$foreignKey = $assocConf['foreignKey'];
			if (!empty($fields) && !in_array($foreignKey, $fields)) {
				// not in "allowed" fields, don't translate
				continue;
			}
			$record = $this->injectNameField($Model, $record, $assocName, $foreignKey);
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
	public function injectNameField($Model, $record, $assocName, $foreignKey) {
		if (empty($foreignKey) || empty($record[$Model->alias][$foreignKey])) {
			// unable to find the ID
			return $record;
		}
		$assocModel = $this->assocModel($Model, $assocName);
		$fkid = $record[$Model->alias][$foreignKey];
		if (!empty($record[$assocName][($assocModel->displayField)])) {
			$fkname = $record[$assocName][($assocModel->displayField)];
		}
		$fkname = $assocModel->field(
			$assocModel->displayField,
			array($assocName . '.' . $assocModel->primaryKey => $fkid)
		);
		if (!empty($fkname)) {
			$record[$Model->alias][$foreignKey . '_typeahead'] = $fkname;
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

