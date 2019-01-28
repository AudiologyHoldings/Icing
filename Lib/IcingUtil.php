<?php
App::uses('Xml', 'Utility');
class IcingUtil extends CakeObject {

	/**
	 * Returns the server URL
	 *
	 * @return string of server url
	 */
	static function getUrl() {
		$host = env('HTTP_HOST');
		$script_filename = env('SCRIPT_FILENAME');
		$isshell = (strpos($script_filename, 'console')!==false);
		$envExtended = "";
		if ($isshell) {
			$envExtended = preg_replace('#[^a-zA-Z0-9]#', '', env('SCRIPT_FILENAME'));
		}
		$check_order = array(
			'REQUEST_URI',
			'QUERY_STRING'
		);
		foreach ($check_order as $key) {
			if (isset($_SERVER[$key]) && !empty($_SERVER[$key])) {
				return $_SERVER[$key] . $envExtended;
			}
		}
		return null;
	}

	/**
	 * Returns the server IP
	 *
	 * @return string of incoming IP
	 */
	static function getIp(){
		$check_order = array(
			'HTTP_CLIENT_IP', //shared client
			'HTTP_X_FORWARDED_FOR', //proxy address
			'HTTP_X_CLUSTER_CLIENT_IP', //proxy address
			'REMOTE_ADDR', //fail safe
		);

		foreach ($check_order as $key) {
			if (isset($_SERVER[$key]) && !empty($_SERVER[$key])) {
				return $_SERVER[$key];
			}
		}
		return null;
	}

	/**
	 * Return upload limit on configurations
	 *
	 * @return int $uploadLimit
	 */
	static function uploadLimit() {
		$max_upload = (int)(ini_get('upload_max_filesize'));
		$max_post = (int)(ini_get('post_max_size'));
		$memory_limit = (int)(ini_get('memory_limit'));
		return min($max_upload, $max_post, $memory_limit);
	}

	/**
	 * Returns if request is HTTPS or not.
	 *
	 * @return boolean true if HTTPS, false if not.
	 */
	public static function isHttps() {
		return (
			(isset($_SERVER['HTTP_X_REMOTE_PROTOCOL']) && !empty($_SERVER['HTTP_X_REMOTE_PROTOCOL'])) ||
			(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') ||
			(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') ||
			(isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT']==443));
	}

	/**
	 * this function takes any number as input and returns a padded/grouped result
	 * 	eg: 666 -> 00600/00666
	 * 	eg: abcdefg -> abcde00/abcdefg
	 * @param mixed $input
	 * @param int $stringLength
	 * @param int $parentFolderStringPos
	 * @param int $parentFoldersCount
	 * @param int $parentFoldersIncrement
	 * @param string $padding
	 * @return mixed $parentSlashInput
	 */
	static function numberParent($input,$stringLength=null,$parentFolderStringPos=null,$parentFoldersCount=null,$parentFoldersIncrement=null,$padding='0') {
		$init = $input = trim(strval($input));
		$stringLength = (!empty($stringLength) ? intval($stringLength) : (strlen($input) > 5 ? strlen($input) : 5));
		$parentFolderStringPos = (!empty($parentFolderStringPos) ? intval($parentFolderStringPos) : 3);
		$parentFoldersCount = intval($parentFoldersCount);
		$parentFoldersIncrement = (!empty($parentFoldersIncrement) ? intval($parentFoldersIncrement) : 1);
		// get parent folder from $input
		$input = str_pad($input,$stringLength,$padding,STR_PAD_LEFT);
		$parent = str_pad(
			substr(
				substr($input,(-1*$stringLength)),
				0,(-1*$parentFolderStringPos)+1
			),$stringLength,$padding,STR_PAD_RIGHT);
		//echo "<hr>$parent/$input ($init) [$parentFolderStringPos/$parentFoldersCount/$parentFoldersIncrement]";
		if ($parentFoldersCount > 1) {
			$parent = self::numberParent($parent,$stringLength,($parentFolderStringPos+$parentFoldersIncrement),($parentFoldersCount-1),$parentFoldersIncrement,$padding);
		}
		return "$parent/$input";
	}

	/**
	 * removes empty elements from an array except for 0
	 *
	 * @param array $array
	 * @return array $arrayWithoutEmpties
	 */
	static function array_clean($array) {
		return array_diff($array,array(null,'',"\n","\s","\r","\r\n","\n\r"));
	}

	/**
	 * randomize an array and return it with the same keys, different order
	 *
	 * @param array $array
	 * @return array $array
	 */
	static function shuffle_assoc( $array ){
		$keys = array_keys( $array );
		shuffle( $keys );
		return array_merge( array_flip( $keys ) , $array );
	}

	/**
	 * Returns a string, derived from whatever the input was.
	 * if the input was an array, it implode with ','
	 *
	 * @param mixed $input
	 * @return array $inputAsArray
	 */
	static function asStringCSV($input) {
		return Re::stringCSV($input);
	}

	/**
	 * Parses urls better than the built in PHP function
	 *
	 * @param string $url
	 * @return array $url_parts
	 */
	static function parseUrl($url) {
		$r  = '!(?:(\w+)://)?(?:(\w+)\:(\w+)@)?([^/:]+)?(?:\:(\d*))?([^#?]+)?(?:\?([^#]+))?(?:#(.+$))?!i';
		preg_match ( $r, $url, $out );
		return array(
			'full' => $out[0],
			'scheme' => $out[1],
			'username' => $out[2],
			'password' => $out[3],
			'domain' => $out[4],
			'host' => $out[4],
			'port' => $out[5],
			'path' => $out[6],
			'query' => $out[7],
			'fragment' => $out[8],
		);
	}

	/**
	 *
	 */
	static function detectUTF8($string) {
		// FIX FOR WHEN STR IS OVER 5000 CHARS
		$_is_utf8_split = 5000;
		if (strlen($string) > $_is_utf8_split) {
			for ($i=0,$s=$_is_utf8_split,$j=ceil(strlen($string)/$_is_utf8_split);$i < $j;$i++,$s+=$_is_utf8_split) {
				if (self::detectUTF8(substr($string,$s,$_is_utf8_split))) {
					return true;
				}
			}
			return false;
		} else {
			// DETECT UTF8
			return preg_match('%^(?:
				[\x09\x0A\x0D\x20-\x7E]				# ASCII
				| [\xC2-\xDF][\x80-\xBF]			# non-overlong 2-byte
				|  \xE0[\xA0-\xBF][\x80-\xBF]		# excluding overlongs
				| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}	# straight 3-byte
				|  \xED[\x80-\x9F][\x80-\xBF]		# excluding surrogates
				|  \xF0[\x90-\xBF][\x80-\xBF]{2}	# planes 1-3
				| [\xF1-\xF3][\x80-\xBF]{3}			# planes 4-15
				|  \xF4[\x80-\x8F][\x80-\xBF]{2}	# plane 16
				)*$%xs', $string);
		}
	}

	/**
	 *
	 */
	static function isUTF8($str) {
		if ($str === mb_convert_encoding(mb_convert_encoding($str, "UTF-32", "UTF-8"), "UTF-8", "UTF-32")) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 *
	 */
	static function makeUTF8($str) {
		if (is_array($str)) {
			$r = array();
			foreach ($str as $k => $v) {
				$r[$k] = self::makeUTF8($str);
			}
			return $r;
		} else {
			if (self::detectUTF8($str)) {
				$str = utf8_encode($str);
			}
			return $str;
		}
	}

	/**
	 * Reduces an array to a the JS nomenclature for an object, useful for passing into JS configurations
	 */
	static function arrayFlatten($arrayNested) {
		$returnArray = array();
		foreach ( $arrayNested as $key => $val ) {
			if (is_array($val)) {
				$returnArray = set::merge($returnArray,$val);
			} else {
				$returnArray[$key] = $val;
			}
		}
		return $returnArray;
	}

	/**
	 * A handy shortcut function to allow specifying an array of replacements as key=>value
	 *
	 * @param array $replace
	 * @param string $string
	 * @return string $stringReplaced
	 */
	static function str_replace_array($replace,$string) {
		return str_replace(array_keys($replace),array_values($replace),$string);
	}

	/**
	 * fixes special character issues for XML data
	 * also trims extra tabs and linebreaks and whatnot from XML data
	 *
	 * @link http://www.google.com/support/webmasters/bin/answer.py?answer=35653
	 * @param string $xml
	 */
	static function clean4xml($xml) {
		$xml = self::makeUTF8($xml);
		$xml = self::str_replace_array(array(
			'&' => '&amp;',
			'<' => '&lt;',
			'>' => '&gt;',
			'"' => '&quot;',
			"'" => '&apos;',
			),$xml);
		$xml = self::str_replace_array(array(
			'&amp;amp;' => '&amp;',
			),$xml);
		$xml = preg_replace('`\n\t+`',"\n",$xml);
		$xml = preg_replace('`>\t+`',">\n",$xml);
		$xml = preg_replace('`>\s?\t?<`',">\n<",$xml);
		$xml = preg_replace('`\n\n++`',"\n",$xml);
		return $xml;
	}

	/**
	 * Simple clean address function
	 */
	static function cleanaddress($address) {
		$address = trim($address,',');
		// remove suite suffix
		$address = preg_replace('/((Ste|Suite|PO|POBOX|PO Box|ll|office|room|floor)([ \-\.\:]+)([0-9]+))/ie','',$address);
		// remove any prefix, before the numbers (like the mall name
		$address = preg_replace('/^([a-z ]+)([0-9]+)/i','${2}',$address);
		// clean whitespace
		$address = preg_replace('/\t/', ' ',$address);
		$address = str_replace("\n",' ',$address);
		$address = preg_replace('/\s\s+/', ' ',$address);
		// clean double-commas
		$address = str_replace(',,', ', ',$address);
		$address = str_replace(', ,', ', ',$address);
		// clean whitespace (again)
		$address = preg_replace('/\s\s+/', ' ',$address);
		return trim($address);
	}

	/**
	 * removes characters which would cause a problem with outputting as a JS string.
	 * @param string $string
	 * @param string $escapeQuote [",'] (which quotes to escape)
	 * @return string $stringEscapedAndClean
	 */
	static function clean4js($string,$escapeQuote='"') {
		$string = str_replace("\n",' ',$string);
		$string = str_replace($escapeQuote,'\\'.$escapeQuote,$string);
		return $string;
	}

	/**
	 * cleans a domain only, strips http:// and https:// prefixes and anything after a slash...
	 * @param string $http [http|https] if set, add a https?:// beginning of the domain
	 * @param string $www [www] if set, add a www.prefix to the domain (if there is only one . left)
	 * @param string $domain
	 * @return string $domain
	 */
	static function cleandomain() {
		$args = func_get_args();
		$protocol = $www = null;
		$allowed_protocols = array('http','https','ftp');
		$allowed_www = array('www','www.');
		foreach ( $args as $arg ) {
			if (in_array($arg,$allowed_protocols)) {
				$protocol=trim($arg,'.');
			} elseif (in_array($arg,$allowed_www)) {
				$www=trim($arg,'.');
			}
		}
		$args = array_diff($args,$allowed_protocols,$allowed_www);
		$domain = implode('/',$args);
		$domain = str_replace(array('http://','https://','ftp://'),'',$domain);
		$domain_parts = explode('/',$domain);
		$domain = $domain_parts[0];
		if (!empty($www)) {
			$domain_parts = explode('.',$domain);
			if (count($domain_parts)==2) {
				$domain = $www.'.'.$domain;
			}
		}
		if (!empty($protocol)) {
			return $protocol.'://'.$domain;
		} else {
			return $domain;
		}
	}

	/**
	 * cleans a link/url to not have multiple slashes in it.
	 * @param string $link url or url-part to be cleaned
	 * @param mixed $settings array of settings -or- if true, trim_slashes=true
	 * 					trim_slashes bool
	 *					ensure_slashes bool
	 * 					www bool
	 * 					strip_www bool
	 * 					add_www bool
	 * 					add_http bool
	 */
	static function cleanlink($link,$settings=null) {
		if (is_bool($settings)) {
			$settings=array('trim_slashes'=>$settings);
		} elseif (is_string($settings)) {
			$settings=array($settings=>1);
		}
		// slashes
		$link = preg_replace('/\/\/+/','/',$link);
		$link = str_replace(':/','://',$link);
		if (isset($settings['ensure_slashes']) && !empty($settings['ensure_slashes'])) {
			$link = '/'.trim($link,'/').'/';
		} elseif (isset($settings['trim_slashes']) && !empty($settings['trim_slashes'])) {
			$link = trim($link,'/');
		}
		// www prefix/subdomain
		if (isset($settings['www']) && !empty($settings['www'])) {
			$settings['strip_www'] = $settings['add_www'] = $settings['www'];
		}
		if (isset($settings['strip_www']) && !empty($settings['strip_www'])) {
			$link = str_replace('www.','',$link);
		}
		if (isset($settings['add_www']) && !empty($settings['add_www'])) {
			if (strpos('www.',$link)===false) {
				$parts = self::parseUrl($link);
				if (self::vn($parts,'host')) {
					$link = str_replace($parts['host'],'www.'.$parts['host'],$link);
				} elseif (strpos('://',$link)!==false) {
					$link = str_replace('://','://www.',$link);
				} else {
					$link = 'www.'.$link;
				}
			}
		}
		if (isset($settings['add_http']) && !empty($settings['add_http'])) {
			if (strpos('://',$link)===false) {
				$link = 'http://'.$link;
			}
		}
		$link = str_replace('www.www.','www.',$link);
		if (strpos($link,'/http://') !== false) {
			// url like: http://www.healthyhearing.com/http://www.healthyhearing.com/something
			$link = substr($link,strpos($link,'/http://')+1);
		}
		// done
		return $link;
	}

	public static function xmlElem($name, $attributes = array(), $content = null){
		$attributes['@'] = $content;
		$data = array(
			$name => $attributes
		);
		$retval = Xml::fromArray($data, array('format' => 'attribute', 'encoding' => null))->asXML();
		$retval = str_replace(array("<?xml version=\"1.0\"?>","\n"), "", $retval);
		return html_entity_decode($retval);
	}

	/**
	 * Return the very basic XML header
	 *
	 * @return string $xmlHeader
	 */
	public static function xmlHeader(){
		return '<?xml version="1.0" encoding="UTF-8" ?>';
	}

	/**
	 * Array Keys - Recursvie against all nested arrays - makes a flat list
	 *
	 * @param array
	 * @return array A list of all keys in that array, recursively found
	 */
	public static function arrayKeysRecursive($array = array()) {
		$return = array();
		foreach (array_keys($array) as $key) {
			$return[] = $key;
			if (is_array($array[$key])) {
				$return = array_merge($return, self::arrayKeysRecursive($array[$key]));
			}
		}
		return $return;
	}
}
