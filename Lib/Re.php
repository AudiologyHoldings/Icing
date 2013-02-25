<?php
/**
 * Library of helper functions
 *
 *
 * @link		  https://github.com/zeroasterisk/as-library-of-PHP-helper-functions
 * @license	   MIT License (http://www.opensource.org/licenses/mit-license.php)
 */


class Re {

	/**
	 * Array of "empties" to remove
	 * @var $empties
	 */
	static $empties = array(null, '', ' ', '	', "\r\n", "\n", "\t", "\r", "\s");

	/**
	 * Returns an array, derived from whatever the input was.  Optionally cleans empties from the array as well.
	 * @param mixed $input
	 * @param bool $cleanEmpties
	 * @return array $inputAsArray
	 */
	public static function asArray($input, $cleanEmpties=true) {
		if (empty($input)) {
			return array();
		}
		if (is_object($input)) {
			$input = get_object_vars($input);
		} elseif (!is_array($input)) {
			$input = array($input);
		}
		// get all first level nested objects (if any)
		foreach ( $input as $key => $val ) {
			if (is_object($val)) {
				$input[$key] = asArray($val);
			}
		}
		return $input;
	}

	/**
	 * Returns a string, derived from whatever the input was.  Optionally trims the return as well.
	 * @param mixed $input
	 * @param bool $trimReturn
	 * @return array $inputAsArray
	 */
	public static function asString($input, $trimReturn=true) {
		if (!empty($input) || $input==0){
			if (is_object($input)) {
				$input = get_object_vars($input);
			}
			while (is_array($input)) {
				if ($trimReturn) {
					$input = array_diff($input, array(null, '', ' '));
				}
				$input = current($input);
			}
			return strval($input);
		}
		return '';
	}

	/**
	 * Returns an array, derived from whatever the input was.
	 * it will split a string by ',' (and whatever is passing in extraExploders)
	 * @param mixed $input
	 * @param array $extraExploders
	 * @return array $inputAsArray
	 */
	public static function arrayCSV($input, $extraExploders=null) {
		if ($input===0 || $input==='0') {
			return array(0);
		}
		if (empty($input)) {
			return array();
		}
		if (is_object($input)) {
			$input = get_object_vars($input);
		} elseif (!is_array($input)) {
			if (!empty($extraExploders)) {
				$input = str_replace(Re::arrayCSV($extraExploders), ',', strval($input));
			}
			$input = explode(',', $input);
		}
		return Re::asArray($input);
	}

	/**
	 * Returns a string, derived from whatever the input was.
	 * if the input was an array, it implode with ','
	 * @param mixed $input
	 * @return array $inputAsArray
	 */
	public static function stringCSV($input) {
		if ($input===0 || $input==='0') {
			return 0;
		}
		if (empty($input)) {
			return '';
		}
		if (is_object($input)) {
			$input = get_object_vars($input);
		}
		if (is_array($input)) {
			foreach ( array_keys($input) as $key ) {
				if (is_array($input[$key])) {
					$input[$key] = Re::stringCSV($input[$key]);
				}
			}
			$input = implode(',', $input);
		}
		return strval($input);
	}

	/**
	 * Strip out "empty" values from array
	 * @param array $input
	 * @return array $input without empty values
	 */
	public static function cleanArray($input) {
		if (!is_array($input)) {
			$input = Re::asArray($input);
		}
		array_walk($input, 'trim');
		if (class_exists('Set')) {
			$input = Re::asArray(Set::filter($input));
		}
		$input = array_diff($input, Re::$empties);
		return $input;
	}

	/**
	 * just a simpler test function.  Tests if something is !emtpy() || == '0'
	 * @param mixed $data
	 * @param array $settings
	 *				$settings[disallow] values to disallow (if empty, we default to our known "empties")
	 * @return bool
	 */
	public static function isValid($data, $settings=array()) {
		extract(array_merge(array(
			'disallow' => null
		), $settings));
		if (is_array($data) && count($data)==1) {
			$data = array_shift($data);
		}
		if (is_bool($allow) || is_int($allow)) {
			$allow = array($allow);
		} else {
			$allow = Re::arrayCSV($allow);
		}
		$disallowDefaults = array('0000-00-00', '0000-00-00 00:00:00', '', null, false);
		$disallow = array_merge($disallowDefaults, Re::arrayCSV($disallow));
		if (is_array($data)) {
			if (Set::check($data, $disallow)) {
				return false;
			}
		} elseif (in_array($data, $disallow, true)) {
			return false;
		}
		if ($input===0 || $input==='0') {
			// allow 0 unless explicitly disallowed
			// if disallowed, it would have already returned
			return true;
		}
		return empty($input);
	}

	/**
	 * A simple funciton to return the first non-empty/valid value from $input array
	 * @param array $input
	 * @param mixed $default [null]
	 * @param array $isValidSettings
	 * @return mixed $output
	 */
	public static function firstValid($input, $default=null, $isValidSettings=array()) {
		if (is_array($input) && !empty($input)) {
			foreach ( Re::asArray($input) as $val ) {
				if (Re::isValid($val, $isValidSettings)) {
					return $val;
				}
			}
		}
		return $default;
	}

	/**
	 * returns the value if valid, otherwise, returns default.
	 * @param mixed $input
	 * @param mixed $default
	 * @param array $isValidSettings
	 * @return mixed $data or $default
	 */
	public static function reValid($input, $default='', $isValidSettings=array()) {
		if (Re::isValid($input, $isValidSettings)) {
			return $data;
		} else {
			return $default;
		}
	}


}
