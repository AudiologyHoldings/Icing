<?php
/**
 * Simple throttling table/toolset
 *
 * Common Usage:
 *
 *	App::uses('Throttle', 'Icing.Model');
 *	if (!ClassRegistry::init('Icing.Throttle')->checkThenRecord('myUniqueKey', 2, 3600)) {
 *		throw new OutOfBoundsException('This method has been attempted more than 2 times in 1 hour... wait.');
 *	}
 *	if (!ClassRegistry::init('Icing.Throttle')->checkThenRecord('myUniqueKey'.AuthComponent::user('id'), 1, 60)) {
 *		throw new OutOfBoundsException('A Logged In User Account has attempted more than 1 time in 60 seconds... wait.');
 *	}
 *	if (!ClassRegistry::init('Icing.Throttle')->checkThenRecord('myUniqueKey'.env('REMOTE_ADDR'), 5, 86400)) {
 *		throw new OutOfBoundsException('Your IP address has attempted more than 5 times in 1 day... wait.');
 *	}
 *
 * Also see ThrottleableBehavior:
 *
 *	$this->MyModel->Behaviors->load('Icing.Throttleable');
 *	if (!$this->MyModel->throttle('someKey', 2, 3600)) {
 *		throw new OutOfBoundsException('This method on MyModel has been attempted more than 2 times in 1 hour... wait.');
 *	}
 *
 * Main Methods:
 *
 *   checkThenRecord() - shortcut to check, and then, record for a $key
 *   limit() - alias to checkThenRecord()
 *   check() - checks to see that there are no more than $allowed records for a $key
 *   record() - saves a record for a $key (which will $expireInSec)
 *   purge() - empties all expired records from table (automatically called on check())
 *
 * Unit Tests:
 *
 *   ./cake test Icing Model/Throttle
 *
 */

App::uses('AppModel', 'Model');
class Throttle extends AppModel {

	public $name = 'Throttle';
	public $displayField = 'key';
	public $cacheQueries = false;

	/**
	 * Validation parameters - initialized in constructor
	 *
	 * @var array
	 * @access public
	 */
	public $validate = array();

	/**
	 * Constructor
	 *
	 * @param mixed $id Model ID
	 * @param string $table Table name
	 * @param string $ds Datasource
	 * @access public
	 */
	public function __construct($id = false, $table = null, $ds = null) {
		parent::__construct($id, $table, $ds);
		$this->validate = array(
			'key' => array(
				'notBlank' => array(
					'rule' => array('notEmpty'),
					'required' => true,
					'allowEmpty' => false,
					'message' => __('Please enter a key', true)
				),
			),
			'expire_epoch' => array(
				'notBlank' => array(
					'rule' => array('notEmpty'),
					'required' => true,
					'allowEmpty' => false,
					'message' => __('Please enter an Expires as an Epoch in the current server timezone', true)
				),
			),
		);
	}

	/**
	 * This is a shortcut method to check for if something is allowed
	 * and if so, add a throttle record so we know that it has been done this time
	 *
	 * @param string $key
	 * @param int $allowed [1]
	 * @param int $expireInSec [1800]
	 * @return boolean
	 */
	public function checkThenRecord($key, $allowed, $expireInSec) {
		if (!($this->check($key, $allowed))) {
			return false;
		}
		if (!$this->record($key, $expireInSec)) {
			return false;
		}
		return true;
	}

	/**
	 * Alias for checkThenRecord()
	 *
	 * @param string $key
	 * @param int $allowed [1]
	 * @param int $expireInSec [1800]
	 * @return boolean
	 */
	public function limit($key, $allowed, $expireInSec) {
		return $this->checkThenRecord($key, $allowed, $expireInSec);
	}

	/**
	 * Check to see if a $key has been tracked more than $allowed times
	 *
	 * @param string $key
	 * @param int $allowed [1]
	 * @return boolean
	 */
	public function check($key, $allowed = 1) {
		if (empty($allowed)) {
			$allowed = 1;
		}
		$this->register_shutdown_function_safe_for_tests(function() {
			$this->purge();
		});
		$count = $this->find('count', ['conditions' => [
			'key' => $key,
			'expire_epoch >=' => time(),
		]]);
		return ($count <= $allowed);
	}

	/**
	 * Save a record for this throttle entry, to know that something has been done
	 *
	 * @param string $key
	 * @param int $expireInSec [1800]
	 * @return boolean
	 * @throws OutOfBoundsException
	 */
	public function record($key, $expireInSec = 0) {
		if (!is_numeric($expireInSec) || empty($expireInSec)) {
			$expireInSec = 1800; // 30 min
		}
		$expire_epoch = time() + $expireInSec;
		$data = array('Throttle' => compact('expire_epoch', 'key'));
		$this->create(false);
		if (!$this->save($data)) {
			throw new OutOfBoundsException('Throttle::record() failed to save ' . $this->simpleErrors() );
		}
		return true;
	}

	/**
	 * Remove all "expired" records from the table
	 *
	 * NOTE: check() registers this to be run on shutdown
	 *
	 * @return bool success
	 */
	public function purge() {
		return $this->deleteAll(['expire_epoch <' => time()], true, true);
	}

	/**
	 * Register shutdown function is used to not make the user wait for '->purge()' to run.
	 * However, in unit testing, register shutdown function makes purge() run too late.
	 *
	 * This will work like register_shutdown_function unless we are in unit tests or the cli,
	 * in which case the function will just be run immediately.
	 *
	 * @param closure $function_to_run
	 * @return whatever is returned by the closure
	 **/
	public function register_shutdown_function_safe_for_tests($function) {
		if ($this->useDbConfig == 'test' || Configure::read('isshell')) {
			return $function();
		}
		return register_shutdown_function($function);
	}

}
