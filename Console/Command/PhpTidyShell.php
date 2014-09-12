<?php
/**
 * PhpTidy Shell
 *
 * CLI interface to standarized PhpTidy functionality
 *
 *
 *
 * @package default
 */
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');
App::uses('PhpTidy', 'Icing.Lib');
class PhpTidyShell extends Shell {
	public $uses = array();

	/**
	 * get the option parser.
	 *
	 * @return void
	 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		return $parser->description(
			__d('cake_console', 'Php Tidy')
		)->addOption('help', array(
				'help' => __d('cake_console', 'Help'),
				'short' => 'h'
			));
	}

	/**
	 * Help info
	 *
	 * @return void
	 */
	public function help() {
		$this->out('Php Tidy');
		$this->hr();
		$this->out();
		$this->out('Run Php Tidy against any file by full path or app relative path');
		$this->out('  Ensure your files are backed up first!');
		$this->out();
		$this->out('  ./cake Icing.PhpTidy <filename> [<filename> ...]');
		$this->out('    (full path to file)');
		$this->out('  ./cake Icing.PhpTidy /var/www/example/app/View/Helper/MyFancyHelper.php');
		$this->out('    (relative to APP paths)');
		$this->out('  ./cake Icing.PhpTidy View/Helper/MyFancyHelper.php');
		$this->out('    (file extension optional)');
		$this->out('  ./cake Icing.PhpTidy View/Helper/MyFancyHelper');
		$this->out('    (directory paths are ok, does everything in the path)');
		$this->out('  ./cake Icing.PhpTidy <dir> [<filename> ...]');
		$this->out();
	}

	/**
	 * Main worker
	 *
	 * @return void
	 */
	public function main() {
		if (empty($this->args)) {
			$this->error("Require at least one file path");
			die();

		}
		foreach ($this->args as $input) {
			$path = $this->getPath($input);
			if (empty($path)) {
				$this->error("Unable to find $input");
				die();
			}
			if (is_file($path)) {
				$files = array($path);
			}
			if (is_dir($path)) {
				$dir = new Folder($path);
				$files = $dir->findRecursive('*.php');
			}
			if (empty($files)) {
				$this->error("Invalid file type $path");
				die();
			}
			foreach ($files as $file) {
				$this->out($file);
				PhpTidy::file($file);
			}
		}
	}

	/**
	 * Find a valid file/dir path or return false
	 *
	 * @param string $path
	 * @return string $path or false
	 */
	public function getPath($path) {
		if (file_exists($path)) {
			return $path;
		}
		$options = array(
			APP . $path,
		);
		foreach ($options as $option) {
			if (file_exists($option)) {
				return $option;
			}
			if (file_exists($option . '.php')) {
				return $option . '.php';
			}
		}
		return false;
	}

}
