<?php
/**
 * Wrapper class for the Configure class built into the CakePHP Core
 *
 * The benifits of this class is that it only loads your configurations when
 * needed, and you can load configurations from a variety of places, included
 * from a database table.
 *
 * This is useful if you have a lot of configurations, or if you need to have
 * configurations editable by users of the application.
 *
 * To enable database configurations copy the example config file and edit
 * cp app/plugins/icing/config/conf.php app/config/
 *
 * You are free to use the DatabaseConfiguration model included in the Icing
 * plugin (default).  The included schema would work fine for it:
 * cake schema create -p icing
 *
 * Also, "Conf" is easier to type in than Configure :)
 *
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
Class Conf extends Configure {
	function __construct() {
		Confgure::load('Conf');
	}

	/**
	 * This is a wrapper for Configure::write() which only writes a key/value
	 * if there isn't already a value set for that key.  This is useful for
	 * loading a configuration set from a file or db an not over-writting any
	 * of the existing/explicit configurations which might already exist
	 *
	 * Used to store a dynamic variable in the Configure instance.
	 *
	 * Usage:
	 * {{{
	 * Conf::writeIfUnset('One.key1', 'value of the Conf::One[key1]');
	 * Conf::writeIfUnset(array('One.key1' => 'value of the Conf::One[key1]'));
	 * Conf::writeIfUnset('One', array(
	 *     'key1' => 'value of the Conf::One[key1]',
	 *     'key2' => 'value of the Conf::One[key2]'
	 * );
	 *
	 * Conf::writeIfUnset(array(
	 *     'One.key1' => 'value of the Conf::One[key1]',
	 *     'One.key2' => 'value of the Conf::One[key2]'
	 * ));
	 * }}}
	 *
	 * @link http://book.cakephp.org/view/926/write
	 * @param array $config Name of var to write
	 * @param mixed $value Value to set for var
	 * @return boolean True if write was successful
	 * @access public
	 */
	public function writeIfUnset($key, $value) {
		$_this =& Configure::getInstance();
		if (!is_array($config)) {
			$config = array($config => $value);
		}
		foreach ($config as $name => $value) {
			if (strpos($name, '.') === false) {
				if (isset($_this->{$name})) {
					return true; // already set
				}
			} else {
				$names = explode('.', $name, 4);
				switch (count($names)) {
					case 2:
						if (isset($_this->{$names[0]}[$names[1]])) {
							return true;
						}
						$_this->{$names[0]}[$names[1]] = $value;
					break;
					case 3:
						if (isset($_this->{$names[0]}[$names[1]][$names[2]])) {
							return true;
						}
						$_this->{$names[0]}[$names[1]][$names[2]] = $value;
						break;
					case 4:
						if (isset($_this->{$names[0]}[$names[1]][$names[2]][$names[3]])) {
							return true;
						}
						$_this->{$names[0]}[$names[1]][$names[2]][$names[3]] = $value;
						break;
					case 5:
						// sorry -- nesting too deep... now overwritting... :(
						$names = explode('.', $name, 2);
						if (!isset($_this->{$names[0]})) {
							$_this->{$names[0]} = array();
						}
						$_this->{$names[0]} = Set::insert($_this->{$names[0]}, $names[1], $value);
					break;
				}
			}
		}
		return true;
	}

	/**
	 * this will simply use configure::read($var)
	 * but if the value isn't set yet, it will auto-load
	 * based on the prefix information
	 *
	 * eg: Conf::read('Email.bcc');
	 *     ^ if that doesn't exist, it will execute
	 *       Conf::load('Email');
	 *
	 * @param string $var Variable to obtain.  Use '.' to access array elements.
	 * @return string value of Configure::$var
	 */
	public function read($var, $from=null) {
		// do we already have this variable set?
		$retval = Configure::read($var);
		if ($retval!==null) {
			return $retval;
		}
		// we don't?  ok, do we have a prefix?
		if (strpos($var, '.') !== false) {
			$prefix = array_shift(explode('.', $var));
			if (!empty($prefix)) {
				if (Conf::load($prefix, $from)) {
					return Configure::read($var);
				}
				// no?  perhaps the first dot was a plugin
				if (substr_count($var, '.') > 1) {
					$split = explode('.', $var);
					$plugin = array_shift($split);
					$prefix = array_shift($split);
					if (Conf::load("{$plugin}.{$prefix}", $from)) {
						return Configure::read($var);
					}
				}
			}
		}
		if (empty($from) || $from == 'db') {
			// still no return?  try to load just this key from the database
			$retval = Conf::loadFromDb($var, null);
			if ($retval!==null) {
				return $retval;
			}
		}
		return null;
	}

	/**
	 * Load a configuration file or a set of configurations from a DB query
	 *
	 * @param string $key configurations prefix or a $filename
	 * @param string $from null for both, 'file' or 'db'
	 * @return bool
	 */
	public function load($key, $from=null) {
		// try to get from configure::load() a file configuration
		if (empty($from) || $from == 'file') {
			$loaded = Configure::load($key);
			if ($loaded!==false) {
				return true;
			}
		}
		// try to get from database configuration table
		if (empty($from) || $from == 'db') {
			$laoded = Conf::loadFromDb($key.'%', false);
			if ($loaded!==false) {
				// $loaded should be an array of known keys/values... set 'em
				if (is_array($loaded)) {
					foreach ($loaded as $_key => $_value) {
						Conf::writeIfUnset($key, $value);
					}
				}
				return true;
			}
		}
		return false;
	}

	/**
	 * Load a key or a set of keys from a database query
	 * requires certain configurations to be set
	 * Configure::write('Conf', array(
	 *                                 'model' => 'Icing.DatabaseConfiguration',
	 *                                 'fieldKey' => 'key',
	 *                                 'fieldValue' => 'val',
	 *                                 'fieldActive' => 'is_active',
	 *                               ));
	 * Note: the above settings can be set in app/config/conf.php as
	 * $config = array('Conf' => array(
	 *                                 'model' => 'Icing.DatabaseConfiguration',
	 *                                 'fieldKey' => 'key',
	 *                                 'fieldValue' => 'val',
	 *                                 'fieldActive' => 'is_active',
	 *                               ));
	 *
	 * @param string $key
	 * @param mixed $notFoundDefaultRetVal false or null or whatever
	 *   if we don't have a valid result
	 * @return mixed $val or $notFoundDefaultRetVal
	 */
	public function loadFromDb($key, $notFoundDefaultRetVal=false) {
		$model = Conf::read('Conf.model', 'file');
		if (empty($model)) {
			return $notFoundDefaultRetVal;
		}
		// initailze model
		App::import('Model', $model);
		$Model = ClassRegistry::init($model);
		if (empty($Model)) {
			return $notFoundDefaultRetVal;
		}
		// get other settings
		$settings = array_merge(array(
			'fieldKey' => 'key',
			'fieldValue' => 'val',
			'fieldActive' => 'is_active',
		), array(
			'fieldKey' => Conf::read('Conf.fieldKey', 'file'),
			'fieldValue' => Conf::read('Conf.fieldValue', 'file'),
			'fieldActive' => Conf::read('Conf.fieldActive', 'file'),
		));
		// setup conditions for this key
		$conditions = array("{$Model->alias}.{$settings['fieldKey']} LIKE" => $key);
		if (!empty($settings['fieldActive'])) {
			$conditions["{$Model->alias}.{$settings['fieldActive']}"] = true;
		}
		// do we have any results?
		$count = $Model->find('count', compact('conditions'));
		if (empty($count)) {
			return $notFoundDefaultRetVal; // not found
		}
		// are we talking about a single result or multiple?
		if (substr($key, -1)=='%') {
			// key ends in '%' so we anticipate multiple values
			$retval = $Model->find('list', array('fields' => array($settings['fieldKey'], $settings['fieldValue']), 'conditions' => $conditions));
		} else {
			// we only want a single value returned
			$retval = $Model->field($settings['fieldValue'], compact('conditions'));
		}
		return $retval;
	}
}
