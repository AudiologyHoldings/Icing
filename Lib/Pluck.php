<?php
/**
 * Pluck
 *
 * An extension of the Hash lib and a replacement for Icing.Re pluck methods
 *
 *
 * Pluck::all() --> array()
 *   the only real benifit to this is, you can aggregate results from multiple paths
 *       Pluck::all($user, 'User.id') == array(123)
 *       Pluck::all($user, array('Bad.path', 'User.id', 'User.name')) == array(123, 'john doe')
 *
 * Pluck::firstPath() --> array()
 *   the first result which matches any path (in order) returns
 *     note: we do filter data first, so unless you disable filtering, it's the first non-empty result.
 *       Pluck::firstPath($user, 'User.id') == array(123)
 *       Pluck::firstPath($user, array('Bad.path', 'User.id', 'User.name')) == array(123)
 *
 * Pluck::firstPathOrDefault() --> array() or $default
 *   the output of Pluck::firstPath()
 *     if empty, we instead return a $default argument
 *       Pluck::firstPathOrDefault($user, 'Bad.path', 'default text') == 'default text'
 *       Pluck::firstPathOrDefault($user, 'Bad.path', array('default', 'array')) == array('default', 'array')
 *
 * Pluck::one() --> value {string or whatever} or $default
 *   the output of Pluck::firstPath()
 *     but we only return the "current" or first value...
 *     also, if empty, we instead return a $default argument
 *       Pluck::one($user, 'User.id') == 123
 *       Pluck::one($user, array('Bad.path', 'User.id', 'User.name')) == 123
 *       Pluck::one($user, 'Bad.path', 'non-user') == 'non-user'
 *
 * Pluck::oneEmpties()
 *   the same as Pluck::one() but $filterCallback=false, allowing empties
 *
 * Pluck::allEmpty()
 *   the same as (!empty(Pluck::all()))
 *
 * -----------------------
 *
 * @link       https://github.com/AudiologyHoldings/Icing
 * @license    MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Pluck {

	/**
	 * Get all records which match any of the paths specified
	 *   all non-empty result sets get merged and respond
	 *
	 * @param array $data
	 * @param mixed $paths single $path or array of $paths
	 *   These paths should be in Hash::path syntax
	 *     - Model.field
	 *     - {n}.Model.field
	 * @param mixed $filterCallback
	 * @return array
	 */
	public static function all($data, $paths = array(), $filterCallback = null) {
		if (!is_array($data)) {
			return $data;
		}
		if (!is_array($paths)) {
			$paths = array($paths);
		}
		$data = self::filter($data, $filterCallback);
		$output = array();
		foreach (Hash::filter($paths) as $path) {
			$output = Hash::merge($output, Hash::extract($data, $path));
		}
		return $output;
	}

	/**
	 * Get the first record(s) which match any of the paths specified
	 *   first non-empty result set responds, all others stop
	 *
	 * @param array $data
	 * @param mixed $paths single $path or array of $paths
	 *   These paths should be in Hash::path syntax
	 *     - Model.field
	 *     - {n}.Model.field
	 * @param mixed $filterCallback
	 * @return mixed
	 */
	public static function firstPath($data, $paths = array(), $filterCallback = null) {
		if (!is_array($data)) {
			return $data;
		}
		if (!is_array($paths)) {
			$paths = array($paths);
		}
		$data = self::filter($data, $filterCallback);
		foreach (Hash::filter($paths) as $path) {
			$output = Hash::extract($data, $path);
			if ($output !== array()) {
				return $output;
			}
		}
		return array();
	}

	/**
	 * Get the first record(s) which match any of the paths specified
	 *   first non-empty result set responds, all others stop
	 *
	 * If no valid results, return the $default value
	 *
	 * @param array $data
	 * @param mixed $paths single $path or array of $paths
	 *   These paths should be in Hash::path syntax
	 *     - Model.field
	 *     - {n}.Model.field
	 * @param mixed $default [null]
	 * @param mixed $filterCallback
	 * @return mixed
	 */
	public static function firstPathOrDefault($data, $paths = array(), $default = null, $filterCallback = null) {
		$output = self::firstPath($data, $paths, $filterCallback);
		if ($output !== array()) {
			return $output;
		}
		return $default;
	}

	/**
	 * Get the one first record which match any of the paths specified
	 *   one non-empty result set responds, all others stop
	 *   then we get the first one result from that response
	 *
	 * Basically this is the same as
	 *   current(self::firstPath(...));
	 *
	 * If no valid results, return the $default value
	 *
	 * @param array $data
	 * @param mixed $paths single $path or array of $paths
	 *   These paths should be in Hash::path syntax
	 *     - Model.field
	 *     - {n}.Model.field
	 * @param mixed $default [null]
	 * @param mixed $filterCallback
	 * @return mixed
	 */
	public static function one($data, $paths = array(), $default = null, $filterCallback = null) {
		$output = self::firstPath($data, $paths, $filterCallback);
		if (is_array($output) && $output !== array()) {
			return current($output);
		}
		return $default;
	}

	/**
	 * Convenience shortcut to Pluck::one(... $filterCallback=false)
	 *
	 * @param array $data
	 * @param mixed $paths single $path or array of $paths
	 *   These paths should be in Hash::path syntax
	 *     - Model.field
	 *     - {n}.Model.field
	 * @param mixed $default [null]
	 * @return mixed
	 */
	public static function oneEmpties($data, $paths = array(), $default = null) {
		$output = self::one($data, $paths, $default, false);
		if (is_array($output) && $output !== array()) {
			return current($output);
		}
		return $default;
	}

	/**
	 * Are all paths "empty" or "not found" within data?  if so, return true
	 *
	 * Conversly: Are there valid, non-empty results? if so, return false.
	 *
	 * NOTE: relies on filterCallback, which will "leave" 0
	 *   so if you put in paths that match "0"
	 *   this function WILL RETURN FALSE -- not empty
	 *   even though PHP would say 0 == empty
	 *     GOTCHA!
	 *
	 * solution...
	 *  $this->assertFalse(Pluck::allEmpty($data, 'Path.to.zero'));
	 *  $this->assertTrue(Pluck::allEmpty($data, 'Path.to.zero', true));
	 *
	 * @param array $data
	 * @param mixed $paths single $path or array of $paths
	 *   These paths should be in Hash::path syntax
	 *     - Model.field
	 *     - {n}.Model.field
	 * @param mixed $filterCallback
	 * @return array
	 */
	public static function allEmpty($data, $paths = array(), $filterCallback = null) {
		$output = self::all($data, $paths, $filterCallback);
		return (empty($output));
	}

	/**
	 * Filter handler for Pluck methods - strips out empty values, or anything
	 * passed in via $filterCallback
	 *
	 * See Hash::filter() for more information
	 *
	 * @param array $data
	 * @param mixed $filterCallback
	 *     - false  will not run Hash::filter()
	 *                removes noting, untouched
	 *     - true   will run Hash::filter($data, array('Pluck', '_filterExcludeZero'))
	 *                to remove all empties including 0
	 *     - null   will run Hash::filter($data)
	 *                to remove all empties except 0
	 *     - {else} will run Hash::filter($data, $filterCallback)
	 *                to remove whatever you tell it to remove
	 *                $filterCallback should be: array($className, $methodName)
	 * @return array $data
	 */
	public static function filter($data, $filterCallback = null) {
		if ($filterCallback === false) {
			// not filtering
			return $data;
		}
		if ($filterCallback === true) {
			// custom callback
			// remove all empties AND 0
			return Hash::filter($data, array('Pluck', '_filterExcludeZero'));
		}
		if (empty($filterCallback)) {
			// default Hash::filter()
			// removes all empties EXCEPT 0
			return Hash::filter($data);
		}
		// custom callback
		return Hash::filter($data, $filterCallback);
	}

	/**
	 * Callback function for filtering.
	 * (excludes all empties, including 0)
	 *
	 * @param array $var Array to filter.
	 * @return boolean
	 */
	public static function _filterExcludeZero($var) {
		return (!empty($var));
	}

	/**
	 * Callback function for filtering.
	 * (only excludes NULL)
	 *
	 * @param array $var Array to filter.
	 * @return boolean
	 */
	public static function _filterExcludeNull($var) {
		return ($var !== null);
	}

}
