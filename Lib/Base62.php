<?php
/**
 * Class to aid in converting integrers to base62 strings
 * like are used in URL shorteners like bit.ly
 *
 * @link http://programanddesign.com/php/base62-encode/
 */
Class Base62 {

	/**
	 * characters to use for encoding
	 * (note, you can change this list to a different set for a different base)
	 * (or you can change the ordering of this list, for obfuscated conversion)
	 * @param $chars
	 */
	public static $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

	/**
	 * Converts a base 10 number to any other base.
	 *
	 * @param int $int   Decimal number
	 * @return string $base62
	 */
	public static function encode($int) {
		$base = strlen(Base62::$chars);
		$str = '';
		do {
			$m = bcmod($int, $base);
			$str = Base62::$chars[$m] . $str;
			$int = bcdiv(bcsub($int, $m), $base);
		} while ( bccomp($int, 0) > 0 );
		return $str;
	}

	/**
	 * Convert a number from any base to base 10
	 *
	 * @param string $base62
	 * @return int $int   Number converted to base 10
	 */
	public static function decode($base62) {
		$base = strlen(Base62::$chars);
		$len = strlen($base62);
		$val = 0;
		$arr = array_flip(str_split(Base62::$chars));
		for($i = 0; $i < $len; ++$i) {
			$val = bcadd($val, bcmul($arr[$base62[$i]], bcpow($base, $len-$i-1)));
		}
		return $val;
	}
}
