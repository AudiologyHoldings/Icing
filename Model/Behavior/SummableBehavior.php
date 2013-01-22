<?php
/**
 * SummableBehavior - Helpful tools for finding sums of columns in a database
 * Use as a basic find on a model,
 *   will sum the first field specifced (ignoring the rest)
 *   respects conditions and other find options
 *
 * TODO: could sum multiple columns and return named field responses (maybe)?
 *
 * ./cake test Icing Model/Behavior/SummableBehavior
 *
 * these will be needed for gathering values from databases
 * in standard cake finds
 *
 *  (primary methods)
 *  find('sum', array('fields' => 'fieldname', ...));
 *
 *  (internal helpers)
 *
 */
App::uses('ModelBehavior', 'Model');
class SummableBehavior extends ModelBehavior {

	/**
	 * extend the Model custom findMethods
	 */
	public $findMethods = array('sum' =>  true);

	/**
	 * need to specify a mapMethods too, so we map onto the model
	 */
	public $mapMethods = array('/\b_findSum\b/' => '_findSum');

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
		foreach ($this->findMethods as $key => $val) {
			$Model->findMethods[$key] = $val;
		}
	}

	/**
	 * find method - finds the sum of a single field
	 * the first value speciefied in the 'fields' paramter
	 * @param Model $Model
	 * @param string $functionCall
	 * @param string $state
	 * @param array $query
	 * @param int $sum of a field
	 */
	public function _findSum(Model $Model, $functionCall, $state, $query, $results = array()) {
		if ($state == 'before') {
			if (is_string($query['fields'])) {
				$query['fields'] = explode(',', $query['fields']);
			}
			if (is_array($query['fields']) && !empty($query['fields'])) {
				$field = trim(array_shift($query['fields']));
			}
			if (empty($field)) {
				throw new OutOfBoundsException(__('Summable: findSum: Missing "fields" paramter - should have a single field to sum', true));
				return false;
			}
			$field = trim(trim(trim($field), '`'));
			if (strpos($field, '.')!==false && strpos($field, '`')===false) {
				$field = str_replace('.', '`.`', $field);
			}
			$query['fields'] = array("SUM(`$field`)");
			return $query;
		}
		// after query, check results
		if (!is_array($results) || empty($results)) {
			return 0;
		}
		// get to the value passed back (should only be one, it'll be the first
		return array_shift(array_shift(array_shift($results)));
	}
}

