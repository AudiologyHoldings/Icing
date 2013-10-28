<?php
/**
 * This Behavior is a super-simple means of calling the methods on the
 * Icing.Throttle Model... this is just a shortcut Behavior for "Easy Access"
 *
 * Common Usage
 *
 *	$this->MyModel->Behaviors->load('Icing.Throttleable');
 *	if (!$this->MyModel->throttle('someKey', 2, 3600)) {
 *		throw new OutOfBoundsException('This method on MyModel has been attempted more than 2 times in 1 hour... wait.');
 *	}
 *
 */
App::uses('Throttle', 'Icing.Model');
class ThrottleableBehavior extends ModelBehavior {

	/**
	 * placeholder for the Throttle model
	 */
	public $IcingThrottle = null;

	/**
	 * Setup the behavior
	 */
	public function setUp(Model $Model, $settings = array()){
		$this->IcingThrottle = ClassRegistry::init('Icing.Throttle');
		return parent::setUp($Model, $settings);
	}

	/**
	 * Shortcut method to Throttle -> limit()
	 *
	 * NOTE: using this method DOES make the key unique to this Model's Alias
	 *
	 * If you want the key to NOT be unique to this Model's Alias,
	 * instead use `_throttle()`
	 *
	 * @param string $key
	 * @param int $allowed [1]
	 * @param int $expireInSec [1800]
	 * @return boolean
	 */
	public function throttle($Model, $key, $allowed, $expireInSec) {
		// prefix the $key with this model's alias
		$key = $Model->alias . $key;
		return $this->IcingThrottle->limit($key, $allowed, $expireInSec);
	}

	/**
	 * Shortcut method to Throttle -> limit()
	 *
	 * NOTE: using this method DOES NOT make the key unique to this Model's Alias
	 * any calls to Throttle on other models, or directly, will match on the
	 * passed in $key...
	 *
	 * If you want the key to be unique to this Model's Alias,
	 * instead use `throttle()`
	 *
	 * @param string $key
	 * @param int $allowed [1]
	 * @param int $expireInSec [1800]
	 * @return boolean
	 */
	public function _throttle($Model, $key, $allowed, $expireInSec) {
		return $this->IcingThrottle->limit($key, $allowed, $expireInSec);
	}

}
