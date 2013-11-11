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
				'notempty' => array(
					'rule' => array('notempty'),
					'required' => true,
					'allowEmpty' => false,
					'message' => __('Please enter a key', true)
				),
			),
			'expire_epoch' => array(
				'notempty' => array(
					'rule' => array('notempty'),
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
		$this->purge();
		$count = $this->find('count', array( 'conditions' => compact('key') ));
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
	 * (raw SQL because it's faster than deleteAll() and this is called a lot)
	 *
	 * NOTE: called in $this->check()
	 *
	 * @return int $affectedRows
	 */
	public function purge() {
		$this->query(sprintf('DELETE FROM `%s` WHERE `expire_epoch` < "%s"',
			$this->useTable,
			time()
		));
		return $this->getAffectedRows();
	}

}
