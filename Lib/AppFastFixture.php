<?php
/**
 * Fixtures suck.
 *
 * Here's a better class using some of the best of other tools for making them
 * easier and faster....
 *
 * Assumptions:
 *   - You are still going to put the fields and records onto the fixture
 *   - You make your App's Fixture extend AppFastFixture
 *   - You have a `test` config in app/Config/database.php
 *   - You have a `test_seed` config in app/Config/database.php
 *     it should NOT be the same as your production database
 *     it should NOT be the same as your test database
 *     it will be truncated and populated with fixture data
 *
 * TODO:
 *   - Validate something was inserted
 *
 * Glossary & Overview:
 * ------------------------------
 *
 * AppFastFixture:
 *   - Will cleanup records to match fields (see AppTestFixture)
 *   - Will auto-populate the `test_seed` table on initial run
 *   - Will remove fields and records at runtime, so TableCopyTestFixture uses
 *     the built in, faster "copy from MySQL" functionality
 *
 *
 * AppTestFixture:
 *   A collection of tools to facilitate MUCH simpler setups for fixtures
 *   - records do not have to include all fields (empty values based on type)
 *   - records fields do not have to be in the correct order
 *   - records date and datetime fields support any strtotime() parseable value
 *   - all prep work done at time of __construct()
 *
 * TableCopyTestFixture:
 *   We use the excellent lorenzo/cakephp-fixturize Plugin to make your fixtures fast
 *   - it supports checksum to only re-insert records if the table has changed (huge!)
 *   - it supports create from and insert from a "seed" database (much faster)
 *
 * @author alan blount <alan@zeroasterisk.com>
 *
 * REQUIRES: https://github.com/lorenzo/cakephp-fixturize
 *
 * USAGE: on your fixture, have it extend AppFastFixture instead of CakeTestFixture
 *

App::uses('AppTestFixture', 'Icing.Lib');
class UserFixture extends AppFastFixture {

$this->options = array('dates' => false);

public function afterRecords() {
	foreach($this->records as $i => $record) {
		...
	}
}

 *
 */

App::uses('CakeTestFixture', 'TestSuite/Fixture');
App::uses('AppTestFixture', 'Icing.Lib');
App::uses('TableCopyTestFixture', 'Fixturize.TestSuite/Fixture');
App::uses('FixturizeFixtureManager', 'Fixturize.TestSuite/Fixture');


// setup the class (we extend TableCopyTestFixture)
//   see: https://github.com/lorenzo/cakephp-fixturize
class AppFastFixture extends TableCopyTestFixture {

	public $options = array(
		// fix records to have all the known fields, and only known fields
		'fix' => true,
		// reparse entered dates = date($format, strtotime($value))
		'dates' => true,
		// which db config should you use?
		//   this needs to be setup in app/Config/database.php
		//   it should NOT be the same as your production database
		//   it should NOT be the same as your test database
		//   it will be truncated and populated with fixture data
		//   set to false to disable
		'sourceConfig' => 'test_seed',
		// auto-set sourceConfig to false if filtering
		'sourceConfigAutoFalseOnFilter' => true,
		// fixture name template, used for loading via FixtureManager
		//   sprintf($fixtureName, Inflector::underscore($this->name))
		//   default: sprintf("app.%s", "my_post")
		//   eg: app.my_post
		//   eg: plugin.foobar.foobar_comment
		'fixtureName' => 'app.%s',
	);

	// placeholder
	public $fields = array();
	// placeholder
	public $records = array();

	/**
	 * This is how we initialize this class
	 * note that we pass in the fixture by reference
	 * so all modifications done to it are automatically done
	 * outside of the scope of this class (sweet, no returning)
	 * @return void
	 */
	public function __construct() {
		parent::__construct();

		// Merge all the properties from the Fixture
		if (is_subclass_of($this, 'AppFastFixture')) {
			$parentClass = get_parent_class($this);
			$parentVars = get_class_vars($parentClass);
			if (isset($parentVars['fields'])) {
				$this->fields = array_merge($this->fields, $parentVars['fields']);
			}
			if (isset($parentVars['records'])) {
				$this->records = array_merge($this->records, $parentVars['records']);
			}
			if (isset($parentVars['options'])) {
				$this->options = array_merge($this->options, $parentVars['options']);
			}
		}

		// auto-set sourceConfig to false if filtering
		if ($this->options['sourceConfigAutoFalseOnFilter'] && !empty($_SERVER['argv'])) {
			if (in_array('--filter', $_SERVER['argv'])) {
				$this->options['sourceConfig'] = false;
			}
		}

		// copy over options -> sourceConfig
		$this->sourceConfig = $this->options['sourceConfig'];

		// Load in AppTestFixture for cleaning and Callbacks
		$this->_setupFixFieldsValuesAndDates();

		if (!empty($this->sourceConfig) && !$this->isSeeded()) {
			// Populate seed database from records
			$this->_setupPopulateSeedDatabase();
		}
	}

/**
 * Is this table already seeded into the test_seed database
 *
 * @param DboSource $db
 * @return boolean
 */
	public function isSeeded() {
		return (
			!empty($this->sourceConfig) &&
			Configure::read("AppFastFixture.{$this->table}")
		);
	}

/**
 * Set that this table now seeded into the test_seed database
 *
 * @param DboSource $db
 * @return boolean
 */
	public function setIsSeeded() {
		return (Configure::write("AppFastFixture.{$this->table}", true));
	}



/**
 * Initializes this fixture class
 *
 * @param DboSource $db
 * @return boolean
 */
	public function create($db) {
		if (!$this->isSeeded()) {
			// not seeded, just use parent
			return parent::create($db);
		}
		// is seeded
		$backup = $this->fields;
		$this->fields = [];
		$out = parent::create($db);
		$this->fields = $backup;
		return $out;
	}

/**
 * Inserts records in the database
 *
 * This will only happen if the underlying table is modified in any way or
 * does not exist with a hash yet.
 *
 * @param DboSource $db
 * @return boolean
 */
	public function insert($db) {
		if (!$this->isSeeded()) {
			if (empty($this->records)) {
				// no records, no need to seed
				return true;
			}
			// not seeded, just use parent
			return parent::insert($db);
		}
		// is seeded
		$backup = $this->records;
		$this->records = [];
		$out = parent::insert($db);
		$this->records = $backup;
		return $out;
	}

	/**
	 * Fixtures fields and values auto-cleanup handled with AppTestFixture
	 *
	 * Auto-load it in place and
	 */
	public function _setupFixFieldsValuesAndDates() {
		// trigger callbacks: before
		if (method_exists($this, 'beforeRecords')) {
			$this->beforeRecords();
		}

		$this->AppTestFixture = new AppTestFixture();
		$this->AppTestFixture->records = $this->records;
		$this->AppTestFixture->fields = $this->fields;
		if ($this->options['fix']) {
			$this->AppTestFixture->fixFields();
		}
		if ($this->options['dates']) {
			$this->AppTestFixture->cleanupDates();
		}
		$this->records = $this->AppTestFixture->records;
		$this->fields = $this->AppTestFixture->fields;
		unset($this->AppTestFixture->fields);

		// trigger callbacks: after
		if (method_exists($this, 'afterRecords')) {
			$this->afterRecords();
		}
	}

	/**
	 * Populate test_seed database with values from records
	 * if successful we will truncate the records array
	 * and all inserts will happen from the seed database
	 * if unuccessful, we will leave the records array
	 * and all inserts will happen from the records array like normal
	 * but it will still be a lot faster because of
	 * TableCopyTestFixture->_tableUnmodified() checksum
	 */
	public function _setupPopulateSeedDatabase() {
		// prevent nesting (infinite recursion)
		if (Configure::read('AppFastFixture.populateSeedDatabase')) {
			// skipped, we are already inside populateSeedDatabase
			return;
		}

		if (isset($this->import) && (is_string($this->import) || is_array($this->import))) {
			// skipped for imports
			return;
		}
		$source = ConnectionManager::getDataSource($this->sourceConfig);
		if (empty($source)) {
			debug('empty source, escape');
			// skipped no test_seed connection found
			return;
		}

		// populate the test_seed database from the fixture
		Configure::write('AppFastFixture.populateSeedDatabase', true);
		$this->_populateSeedDatabase();
		Configure::write('AppFastFixture.populateSeedDatabase', null);

		// TODO validate that something was inserted

		// set a configuration so we never attempt to seed it again
		//   and instead use it for all create/insert methods
		$this->setIsSeeded();

		// truncate the records array,
		//   so we start using the test_seed database
		$this->records = [];
	}

	/**
	 * Populate test_seed database with values from records
	 * if successful we will truncate the records array
	 * and all inserts will happen from the seed database
	 * if unuccessful, we will leave the records array
	 * and all inserts will happen from the records array like normal
	 * but it will still be a lot faster because of
	 * TableCopyTestFixture->_tableUnmodified() checksum
	 */
	public function _populateSeedDatabase() {
		$this->_hideLogsForSetup();
		$fixtureName = sprintf(
			$this->options['fixtureName'],
			Inflector::underscore($this->name)
		);
		$CakeFixtureManager = new FixturizeFixtureManager();
		$CakeFixtureManager->loadAllFixtures(
			$this->sourceConfig,
			[$fixtureName]
		);
		unset($CakeFixtureManager);
		$this->_restoresForSetup();
	}

	/**
	 * FixturizeFixtureManager calls CakeLog::debug()
	 * but we don't want those to show up on every test run
	 * so we will ensure we have setup a "scope" on all loggers
	 */
	public function _hideLogsForSetup() {
		foreach (CakeLog::configured() as $name) {
			$logger = CakeLog::stream($name);
			if (!method_exists($logger, 'config')) {
				continue;
			}
			$config = $logger->config();
			if (array_key_exists('scopes_backup', $config)) {
				continue;
			}
			$config['scopes_backup'] = $config['scopes'];
			$config['scopes'][] = 'scoped';
			$logger->config($config);
		}
	}

	/**
	 * Restore original scopes on all loggers
	 */
	public function _restoresForSetup() {
		foreach (CakeLog::configured() as $name) {
			$logger = CakeLog::stream($name);
			if (!method_exists($logger, 'config')) {
				continue;
			}
			$config = $logger->config();
			if (!array_key_exists('scopes_backup', $config)) {
				continue;
			}
			$config['scopes'] = $config['scopes_backup'];
			unset($config['scopes_backup']);
			$logger->config($config);
		}
	}


}

