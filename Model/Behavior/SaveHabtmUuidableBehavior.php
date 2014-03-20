<?php
/**
 * SaveHabtmUuidableBehavior is meants to "fix" saving HABTM with non-UUID string IDs
 *
 * ./cake test app Model/Behavior/SaveableBehavior
 *
 * The core issue is this one:
 *   UUIDs assume a standard length
 *   but if you use a string of non-standard length for a primary key
 *     everything works except for saving HABTM joins/checkboxes
 *
 * Here are some more issues and details on this:
 *  - https://github.com/cakephp/cakephp/issues/1744
 *  - https://github.com/cakephp/cakephp/issues/2054
 *
 * Since the core will likely not be changed on this, this behavior is
 * available to anyone who wants to allow non-standard-length string keys and
 * have HABTM saves work.
 *
 * Basic Workflow:
 *   If you are saving,
 *   If the HABTM data is set on $Model->data
 *   If the values are simple strings
 *     which is normal if you're using `Form->input(multiple => checkbox)`
 *   We translate the string values into arrays, which allows normal saveAll to work as expected
 *   We also lookup the keepExisting primary keys, so we don't always trash existing records first
 *
 */
class SaveHabtmUuidableBehavior extends ModelBehavior {

	/**
	 * settings indexed by model name.
	 *
	 * @var array
	 */
	public $settings = array();

	/**
	 * Default settings
	 *
	 * @var array
	 */
	protected $_defaults = array(
	);

	/**
	 * Configuration of model
	 *
	 * @param AppModel $Model
	 * @param array $config
	 */
	public function setup(Model $Model, $config = array()) {
		$this->settings[$Model->alias] = array_merge($this->_defaults, $config);
	}

	/**
	 * Standard beforeSave callback
	 *  - triggers the dataPrepUuidHABTM()
	 *
	 * @param Model $Model
	 * @param array $options
	 * @return boolean
	 */
	public function beforeSave(Model $Model, $options = array()) {
		$Model->data = $this->dataPrepUuidHABTM($Model, $Model->data);
		return parent::beforeSave($Model, $options);
	}

	/**
	 * HABTM saving is contingent on the IDs being UUIDs (exact number of characters)
	 *  so we are going to have to change the values into nested arrays
	 *
	 *   $in = [ 'AssocModel' => [ 'AssocModel' => [ 'uuid1', 'uuid2' ]]]
	 *  $out = [ 'AssocModel' => [ 'AssocModel' => [ 'assoc_model_id' => 'uuid1', 'assoc_model_id' => 'uuid2' ]]]
	 *
	 * TODO: lookup "keepExisting" records once, before the foreach, and assign in PHP
	 *   reducing the number of DB lookups = good performance
	 *
	 * @param Model $Model
	 * @param array $data saved data array (full)
	 * @return array $data
	 */
	public function dataPrepUuidHABTM(Model $Model, $data) {
		foreach (array_keys($Model->hasAndBelongsToMany) as $assoc) {
			if (empty($data[$assoc]) || !is_array($data[$assoc])) {
				unset($data[$assoc]);
				continue;
			}
			if (!array_key_exists($assoc, $data[$assoc])) {
				// not $data['AssocModel']['AssocModel']
				continue;
			}
			if (empty($data[$assoc][$assoc]) || !is_array($data[$assoc][$assoc])) {
				$data[$assoc][$assoc] = array();
				continue;
			}
			$config = $Model->hasAndBelongsToMany[$assoc];
			if (!empty($data[$Model->alias][$Model->primaryKey])) {
				$id = $data[$Model->alias][$Model->primaryKey];
			} elseif (!empty($Model->id)) {
				$id = $Model->id;
			} else {
				// new record... can not keep it
				$id = false;
			}
			foreach (array_keys($data[$assoc][$assoc]) as $i) {
				if (!is_string($data[$assoc][$assoc][$i])) {
					// skipping this record
					continue;
				}
				// change string ID into an array with the associationForeignKey as fieldname
				//   array('linked_table_id' => $string_id_passed_in)
				$data[$assoc][$assoc][$i] = array(
					$config['associationForeignKey'] => $data[$assoc][$assoc][$i],
				);
				// now look for "keepExisting" because we are breaking that functionality
				if (empty($config['unique']) || $config['unique'] != 'keepExisting') {
					continue;
				}
				// we must know "this" record to effectivly lookup the existing join records
				if (empty($id)) {
					continue;
				}
				if (empty($config['with'])) {
					throw new OutOfBoundsException("SaveableBehavior you must configure {$Model->alias} HABTM {$assoc} using a 'with' Model");
				}
				$with = $config['with'];
				$conditions = array(
					$config['associationForeignKey'] => $data[$assoc][$assoc][$i],
					$config['foreignKey'] => $id,
				);
				$joinId = $Model->{$with}->field('id', $conditions);
				if (!empty($joinId)) {
					$data[$assoc][$assoc][$i]['id'] = $joinId;
				}
			}
		}
		return $data;
	}

}
