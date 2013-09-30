<?php
/**
 * Fixtures suck.
 *
 * So we can build some tools to facilitate MUCH simpler setups for fixtures
 *
 * @author alan blount <alan@zeroasterisk.com>
 *
 * USAGE: on your fixture, have it extend AppTestFixture instead of CakeTestFixture
 *

App::uses('AppTestFixture', 'Icing.Lib');
class UserFixture extends AppTestFixture {

 *
 * Want partial functionality?  Set an options property
 *

$this->options = array('dates' => false);

 *
 *
 * Want to extend it?  no problem
 * it triggers callbacks: beforeRecords() and afterRecords()
 *
 *

public function afterRecords() {
	foreach($this->records as $i => $record) {
		...
	}
}

 *
 */

App::uses('CakeTestFixture', 'TestSuite/Fixture');

// setup the class
class AppTestFixture extends CakeTestFixture {

	public $options = array(
		// fix records to have all the known fields, and only known fields
		'fix' => true,
		// reparse entered dates = date($format, strtotime($value))
		'dates' => true,
	);
	// placeholder
	public $fields = array();
	// placeholder
	public $records = array();

	/**
	 * This is how we initialize this class
	 * note that we pass in the fixture by reference
	 * so all modifications done to it are automatically done
	 * outside of the scope of this class (sweet, no returning)
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
		if (is_subclass_of($this, 'AppTestFixture')) {
			$parentClass = get_parent_class($this);
			$parentVars = get_class_vars($parentClass);
			if (isset($parentVars['fields'])) {
				$this->fields = array_merge($this->fields, $parentVars['fields']);
			}
			if (isset($parentVars['records'])) {
				$this->records = array_merge($this->records, $parentVars['records']);
			}
			if (isset($parentVars['options'])) {
				$this->options = array_merge($this->options, $parentVars['options']);
			}
		}
		// trigger callbacks: before
		if (method_exists($this, 'beforeRecords')) {
			$this->beforeRecords();
		}
		// do stuff
		if ($this->options['fix']) {
			$this->fixFields();
		}
		if ($this->options['dates']) {
			$this->cleanupDates();
		}
		// trigger callbacks: after
		if (method_exists($this, 'afterRecords')) {
			$this->afterRecords();
		}
	}

	/**
	 * Fixtures crap their pants if records don't have all the fields (matched)
	 * which exist in the fields
	 * easy enough to fix
	 */
	public function fixFields() {
		foreach (array_keys($this->records) as $key) {
			$init = $this->records[$key];
			$new = array();
			foreach ($this->fields as $field => $data) {
				if (array_key_exists($field, $init)) {
					$new[$field] = $init[$field];
				} else {
					// caught a missing field... default it to empty
					if (empty($data['type']) || $field == 'indexes') {
						continue; // not a valid field (index/tableParameters)
					} elseif ($this->isValidDefault($data)) {
						// if we have default defined, assign it.
						$new[$field] = $data['default'];
					} elseif ($data['type'] == 'boolean') {
						$new[$field] = 0;
					} elseif ($data['type'] == 'number' || $data['type'] == 'float' || $data['type'] == 'integer' || $data['type'] == 'biginteger') {
						$new[$field] = 0;
					} elseif ($data['type'] == 'date' || $data['type'] == 'datetime') {
						$new[$field] = 'now'; // cleanupDates will fix
					} else {
						$new[$field] = '';
					}
				}
				// doublecheck does this field support null?
				if ($new[$field] === null) {
					if (empty($data['null'])) {
						$new[$field] = '';
					}
				}
			}
			$this->records[$key] = $new;
		}
	}

	/**
	 * you can set the value of any date field to anything strtotime() would
	 * parse
	 *
	 * so 'expires' => '+30days',
	 * or 'expires' => 'yesterday',
	 *
	 * and since it parses dates/times correctly, it just works for them
	 * transparently
	 */
	public function cleanupDates() {
		// setup records to support strtotime() relative date/time values
		$dateFields = $dateTimeFields = array();
		foreach ($this->fields as $field => $data) {
			if (empty($data['type'])) {
				continue;
			} elseif ($data['type'] == 'datetime') {
				$dateTimeFields[] = $field;
			} elseif ($data['type'] == 'date') {
				$dateTimeFields[] = $field;
			}
		}
		foreach (array_keys($this->records) as $key) {
			foreach ($dateFields as $field) {
				if (!empty($this->records[$key][$field])) {
					$this->records[$key][$field] = date('Y-m-d', strtotime($this->records[$key][$field]));
				}
			}
			foreach ($dateTimeFields as $field) {
				if (!empty($this->records[$key][$field])) {
					$this->records[$key][$field] = date('Y-m-d H:i:s', strtotime($this->records[$key][$field]));
				}
			}
		}
	}

	/**
	 * Validate the default field in the column data is a valid default
	 *
	 * @param array of data column data
	 * @return boolean if valid.
	 */
	private function isValidDefault($data){
		if (!array_key_exists('default', $data)) {
			return false;
		}
		if ($data['default'] === null) {
			// we want to "allow" a null default only if the field supports null values
			return (!empty($data['null']));
		}
		return true;
	}
}

