<?php
/**
 * Convenience wrapper for phptidy.php script in app/Plugin/Icing/Vendor/phptidy.php
 *
 *
 */
Class PhpTidy {

	/**
	 *
	 */
	static function script() {
		$phptidypath = dirname(__DIR__) . DS . 'Vendor' . DS . 'phptidy.php';
		if (!is_file($phptidypath)) {
			throw new OutOfBoundsException('PhpTidy:setup() unable to find `Vendor/phptidy.php`');
		}
		return $phptidypath;
	}

	/**
	 * Tidy up some source code as a string
	 * - make a temp file
	 * - run PhpTidy::file() on the temp file
	 * - capture output
	 * - remove temp file
	 *
	 * @param string $input
	 * @return string $output formatted
	 */
	public static function string($input) {
		$path = TMP . 'phptidy-' . md5($input) . '.php';
		file_put_contents($path, $input);
		PhpTidy::file($path);
		$output = file_get_contents($path);
		unlink($path);
		return $output;
	}

	/**
	 * Tidy up source code as a file
	 * - executes the phptidy.php script via php CLI functionality
	 *
	 * @param string $path to file
	 * @return void;
	 * @throws OutOfBoundsException
	 */
	public static function file($path) {
		if (is_array($path)) {
			foreach ($path as $_path) {
				PhpTidy::file($_path);
			}
			return;
		}
		if (!is_file($path)) {
			throw new OutOfBoundsException('PhpTidy::file() unable to find file (should be a full path) ' . $path);
		}
		$phpexe = exec('which php');
		if (empty($phpexe)) {
			$phpexe = 'php';
		}
		$cmd = sprintf('%s %s replace %s',
			$phpexe,
			escapeshellarg(PhpTidy::script()),
			escapeshellarg($path)
		);
		return exec($cmd);
	}

}
