<?php
/**
 * Fixture Update Shell
 *
 * Attempts to intelligently update your fixtures to
 * - force it to use Icing.AppFastFixture
 * '- update the $fields to always match the current database schema
 *      (doesn't touch records, or any other config)
 * - run Icing.PhpTidy against the fixutre, to correct formatting
 *
 * Bonus: it will also verify all fixtures can be found in the database table
 *
 *
 *
 *
 */
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');
App::uses('CakeSchema', 'Model');
class FixtureUpdateShell extends Shell {
	public $uses = array();
	protected $_schemaData = array();
	protected $_Schema = array();


	/**
	 * get the option parser.
	 *
	 * @return void
	 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		return $parser->description(
			__d('cake_console', 'Application Setup & Deployment.')
		)->addOption('connection', array(
			'help' => __d('cake_console', 'Which database configuration to use for baking.'),
			'short' => 'c',
			'default' => 'default'
		))->addOption('plugin', array(
			'help' => __d('cake_console', 'CamelCased name of the plugin to bake fixtures for.'),
			'short' => 'p',
		))->addOption('help', array(
			'help' => __d('cake_console', 'Help'),
			'short' => 'h'
		));
	}

	/**
	 * Help info
	 */
	public function help() {
		$this->out('Fixture Update');
		$this->hr();
		$this->out();
		$this->out('Attempts to intelligently update your fixtures to');
		$this->out('- force it to use Icing.AppFastFixture');
		$this->out('- update the $fields to always match the current database schema');
		$this->out('  (doesn\'t touch records, or any other config)');
		$this->out('- run Icing.PhpTidy against the fixutre, to correct formatting');
		$this->out();
		$this->out('Bonus: it will also verify all fixtures can be found in the database table');
		$this->out();
		$this->out('  update fixtures for the core app/Test/Fixture');
		$this->out('    ./cake Icing.FixtureUpdate');
		$this->out('  update fixtures for the plugin app/Plugin/MyPlugin/Test/Fixture');
		$this->out('    ./cake Icing.FixtureUpdate --plugin MyPlugin');
		$this->out('  same as above, on a customized connection');
		$this->out('    ./cake Icing.FixtureUpdate --plugin MyPlugin --connection my_plugin');
		$this->out('  same as above, short syntax');
		$this->out('    ./cake Icing.FixtureUpdate -p MyPlugin -c my_plugin');
		$this->out();
		$this->out('  custom command, runs on all plugins (default connection)');
		$this->out('    ./cake Icing.FixtureUpdate --plugin all');
		$this->out();
	}

	/**
	 * Main worker
	 */
	public function main() {
		foreach ($this->basePaths() as $path) {
			$this->out("Fixtures in $path");
			$F = new Folder($path);
			$files = $F->findRecursive('.*Fixture.php');
			if (empty($files)) {
				$this->out("  no fixutres to cleanup");
				continue;
			}
			foreach ($files as $file) {
				$this->out("  $file");
				$this->processFixture($file);
			}
		}
		$this->out('done');
	}

	/**
	 * Determine the base paths, where to start searching for fixtures
	 *
	 */
	private function basePaths() {
		$path = '';
		if (!empty($this->params['custom'])) {
			$this->params['plugin'] = '';
			return array($this->params['custom']);
		}
		if (empty($this->params['plugin'])) {
			$this->params['plugin'] = '';
		}
		if ($this->params['plugin'] === '*' || $this->params['plugin'] === 'all') {
			$plugins = App::objects('plugins');
			$plugins = array_unique($plugins);
			$paths = array();
			foreach ($plugins as $plugin) {
				$paths[] = App::pluginPath($plugin);
			}
		}
		if ($this->params['plugin'] == 'root') {
			// slow!
			return array(App);
		}
		if (!empty($this->params['plugin'])) {
			return array(App::pluginPath($this->params['plugin']));
		}
		return array(APP . 'Test' . DS . 'Fixture');
	}

	/**
	 * Read in a fixture file and update it
	 * - force it to use Icing.AppFastFixture
	 * - update the $fields to always match what's on the current database
	 * - run Icing.PhpTidy against it
	 *
	 * @param string $path
	 * @return boolean
	 */
	public function processFixture($path) {
		//Skip fixture update if we don't have a table definaiton, custom dbconfig
		if ($this->getUseTable($path) === false) {
			return true;
		}
		// cleanup the basics
		$appUses = "App::uses('AppFastFixture', 'Icing.Lib');";
		$body = file_get_contents($path);
		// replace old approaches
		$body = preg_replace('#App::[^\)]*AppFastFixture[^\)]*\);#', $appUses, $body);
		// inject App::uses()
		if (strpos($body, $appUses) === false) {
			$body = preg_replace('#(class .* extends )#', $appUses . "\n$1", $body);
		}
		// replace CakeTestFixture
		$body = str_replace('CakeTestFixture', 'AppFastFixture', $body);
		// remove closing php
		$body = str_replace('?>' , '', $body);
		// TODO: replace the $fields array with details from the "current" schema
		try {
			$body = $this->processFixtureFields($path, $body);
		} catch (Exception $e) {
			$this->error('processFixtureFields Exception: ' . $e->getMessage());
		}
		// cleanup up formatting
		App::uses('PhpTidy', 'Icing.Lib');
		$body = PhpTidy::string($body);
		// write out the file
		if (!file_put_contents($path, $body)) {
			$this->error('Unable to write to ' . $path);
		}
		return true;
	}

	/**
	 * Replace the $fields array of a fixutre with the "current" fields
	 */
	protected function processFixtureFields($path, $body) {
		$new = $this->processFixtureFieldsNew($path, $body);
		return $this->processFixtureFieldsInject($path, $body, $new);
	}
	
	/**
	* Get the model from the path
	*/
	protected function getModelName($path) {
		return str_replace(array('Fixture', '.php'), '', basename($path));
	}
	
	protected function getUseTable($path) {
		$model = $this->getModelName($path);
		return ClassRegistry::init($model)->useTable;
	}

	/**
	 * Fixture Fields - get new fields text
	 */
	protected function processFixtureFieldsNew($path, $body) {
		$useTable = $this->getUseTable($path);
		$useTableSingular = Inflector::singularize($useTable);
		$data = $this->getSchema();
		if (!isset($data['tables'][$useTable]) && isset($data['tables'][$useTableSingular])) {
			$useTable = $useTableSingular;
		}
		if (!isset($data['tables'][$useTable])) {
			debug(array_keys($data['tables']));
			debug($useTable);
			$this->error('Could not find your selected table ' . $useTable);
			return false;
		}
		$tableInfo = $data['tables'][$useTable];
		$schema = $this->_generateSchema($tableInfo);
		$schema = trim($schema);
		$schema = trim(trim($schema, ';')) .';';
		return $schema;
	}

	/**
	 * Get the schema for a DB connection (stashed)
	 */
	protected function getSchema() {
		if (!empty($this->_schemaData)) {
			return $this->_schemaData;
		}
		$this->_Schema = new CakeSchema();
		$this->out("Schema for ".$this->params['connection'], 0);
		$data = $this->_Schema->read(array('models' => false, 'connection' => $this->params['connection']));
		$this->_schemaData = $data;
		$this->out('  ('.count($data['tables']).')');
		return $data;
	}

	/**
	 * Generates a string representation of a schema.
	 *
	 * @param array $tableInfo Table schema array
	 * @return string fields definitions
	 */
	protected function _generateSchema($tableInfo) {
		$schema = trim($this->_Schema->generateTable('f', $tableInfo), "\n");
		return substr($schema, 13, -1);
	}

	/**
	 * Fixture Fields - find old fields text
	 */
	protected function processFixtureFieldsInject($path, $body, $new) {
		$paren = 0;
		$capture = false;
		$tokens = token_get_all($body);
		$keep = $remove = array();
		foreach ($tokens as $i => $token) {
			if (!is_array($token)) {
				// get prev token and set values
				$prev = ($i == 0 ? array() : $tokens[($i-1)]);
				$prev[0] = -1;
				$prev[1] = $token;
				$token = $prev;
				$tokens[$i] = $token;
			}
			if (!$capture &&
				$token[1] == '=' &&
				$tokens[($i-2)][1] == '$fields' &&
				$tokens[($i-4)][1] == 'public'
				) {
				$capture = true;
				$keep[] = array(-1, '= {{fields}}');
				continue;
			}
			// if we are not in capture mode, keep everything
			if (!$capture) {
				$keep[] = $token;
				continue;
			}
			// if we are in capture mode, and we get an open paren
			if ($token[1] == '(') {
				$paren++;
				continue;
			}
			// if we are in capture mode, and we get an closing paren
			if ($token[1] == ')') {
				$paren--;
				continue;
			}
			// if we are in capture mode, and $paren == 0, and we are on ";"
			if ($token[1] == ';' && $paren == 0) {
				$capture = false;
				continue;
			}
			// capture enabled, no special char, /dev/null
		}
		// rebuild from tokens
		$body = $this->token_put_string($keep);
		$body = str_replace('{{fields}}', $new, $body);
		return $body;
	}

	/**
	 * render a string output from a bunch of tokens
	 * (ugly, but works)
	 *
	 * @param array $tokens
	 * @return string $out
	 */
	protected function token_put_string($tokens) {
		$line = 0;
		$out = '';
		foreach ($tokens as $i => $token) {
			if (!is_array($token)) {
				// get prev token and set values
				$prev = ($i == 0 ? array() : $tokens[($i-1)]);
				$prev[0] = -1;
				$prev[1] = $token;
				$token = $prev;
			}
			$out .= $token[1];
		}
		return $out;
	}

}

