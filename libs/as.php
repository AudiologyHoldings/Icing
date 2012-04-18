<?php
/**
 * Library of helper functions
 *
 *
 * @link		  https://github.com/zeroasterisk/as-library-of-PHP-helper-functions
 * @license	   MIT License (http://www.opensource.org/licenses/mit-license.php)
 */


class As {

	/**
	 * Returns an array, derived from whatever the input was.  Optionally cleans empties from the array as well.
	 * @param mixed $input
	 * @param bool $cleanEmpties
	 * @return array $inputAsArray
	 */
	public static function array($input, $cleanEmpties=true) {
		if (!empty($input) || $input==0){
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
			if ($cleanEmpties) {
				return array_diff($input, array(null, '', ' ', '	', "\r\n", "\n", "\t", "\r", "\s"));
			} else {
				return $input;
			}
		}
		return array();
	}

	/**
	 * Returns a string, derived from whatever the input was.  Optionally trims the return as well.
	 * @param mixed $input
	 * @param bool $trimReturn
	 * @return array $inputAsArray
	 */
	public static function string($input, $trimReturn=true) {
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
		if (!empty($input) || $input==0){
			if (is_object($input)) {
				$input = get_object_vars($input);
			} elseif (!is_array($input)) {
				if (!empty($extraExploders)) {
					$input = explode(',', str_replace(as::arrayCSV($extraExploders), ',', $input));
				} else {
					$input = explode(',',$input);
				}
			}
			return asArray($input);
		}
		return array();
	}

	/**
	 * Returns a string, derived from whatever the input was.
	 * if the input was an array, it implode with ','
	 * @param mixed $input
	 * @return array $inputAsArray
	 */
	public static function stringCSV($input) {
		if (!empty($input) || $input==0){
			if (is_object($input)) {
				$input = get_object_vars($input);
			}
			if (is_array($input)) {
				foreach ( $input as $key => $val ) {
					if (is_array($val)) {
						$input[$key] = as::stringCSV($val);
					}
				}
				$input = implode(',',$input);
			}
			return strval($input);
		}
		return '';
	}

	/**
	 * slugify - makes a string a slug-friendly string
	 * (lower case, only dashes, no double-dashes)
	 * @param mixed $input
	 * @param string $splitter
	 * @param int $limit_terms
	 * @param bool $ucwords
	 * @return string $slugFormattedInput
	 */
	function slugify($input='', $splitter=null, $limit_terms=null, $ucwords=null) {
		$splitter = (!empty($splitter) && is_string($splitter) ? $splitter : '-');
		$limit_terms = (!empty($limit_terms) && is_numeric($limit_terms) ? $limit_terms : 5);
		$ucwords = (!empty($ucwords) && is_bool($ucwords) ? $ucwords : false);
		if (is_array($input)) {
			foreach ( $input as $key => $val ) {
				$input[$key] = slugify($val, $splitter, $limit_terms, $ucwords);
			}
			return $input;
		} else {
			$limit_terms = intval($limit_terms);
			$input = strtolower(trim($input));
			$input = preg_replace('/[^0-9a-z\ ]/', ' ', $input);
			$input = preg_replace('/(\s\s+)/', ' ', $input);
			$terms = explode(' ', trim($input));
			if (count($terms)>$limit_terms) {
				$terms = array_diff($terms, array('a', 'of', 'the', 'an', 'be', 'are', 'is', 'was', 'were', 'his', 'he', 'her', 'she', 'how'));
				if (count($terms)>$limit_terms) {
					$terms = array_unique($terms);
					if (count($terms)>$limit_terms) {
						$terms = array_slice($terms, 0, $limit_terms);
					}
				}
			}
			if ($ucwords) {
				foreach ( $terms as $k => $term ) {
					if (strlen($term)>2) {
						$terms[$k] = ucfirst($term);
					}
				}
			}
			return implode($splitter, $terms);
		}
	}

	/**
	 * just a simpler test function.  Tests if something is !emtpy() || == '0'
	 * @param mixed $data
	 * @param array $settings
	 *				$settings[allow] an array of values or a value which is allowed - if TRUE, we just us empty($data)
	 *				$settings[requireValuesInAllow] only allow values which are in $allow
	 *				$settings[disallow] values to disallow (if empty, we default to our known "empties")
	 * @return bool
	 */
	public static function isValid($data, $settings=array()) {
		extract(array('allow' => true, 'requireValuesInAllow' => false, 'disallow' => null));
		extract($settings);
		if (is_array($data) && count($data)==1) {
			$data = array_shift($data);
		}
		if (is_bool($allow) || is_int($allow)) {
			$allow = array($allow);
		} else {
			$allow = as::arrayCSV($allow);
		}
		if (empty($disallow)) {
			$disallow = array_diff(array('0000-00-00', '0000-00-00 00:00:00', '', null, false), $allow);
		} else {
			$disallow = as::arrayCSV($disallow);
		}
		if (is_array($data) && $requireValuesInAllow) {
			return Set::check($data, $allow);
		} else {
			if ($requireValuesInAllow) {
				return in_array($data, $allow, true);
			} elseif (!in_array($data, $disallow, true)) {
				if (!empty($data)) {
					return true;
				} else {
					return in_array($data, $allow, (is_bool($data) || $data==null));
				}
			}
		}
		return false;
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
			foreach ( asArray($input) as $val ) {
				if (as::isValid($input, $isValidSettings)) {
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
		if (as::isValid($input, $isValidSettings)) {
			return $data;
		} else {
			return $default;
		}
	}


}