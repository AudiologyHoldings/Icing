<?php
/**
 * Copyright 2005-2011, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2005-2011, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 *
 *
 * @link https://github.com/CakeDC/templates/blob/master/Lib/AppTestCase.php
 *
 * Usage:
 *
 *     App::uses('AppTestCase', 'Icing.Lib');
 *     Configure::load('app_test_fixtures');
 *     class WhateverTest extends AppTestCase {
 *         ...
 *     }
 *
 *
 * App Test case. Contains base set of fixtures.
 *
 * NOTE: if you want to create your own custom set of fixtures, you can copy
 *   this into your application's Lib folder, edit and run from there.
 *
 * OR: you can create a Configure::write('fixtures');
 *
 *
 * Extra Assertions
 *
 * $this->assertArrayCompare($expect, $result);
 *   This is really useful because it will only consider keys which exist in $expect
 *   So if you don't know what the 'created' or 'modified' would be and you don't care
 *   you can just pass in an $expect without those fields, and they aren't
 *   considered.  (All fields passed into $expect are considered)
 *
 * $this->assertInArray($value, $result);
 * $this->assertKeyExists($key, $result);
 * $this->assertIsEmpty($result);
 *
 * @package Icing
 */

// auto load the AppTestFixture class, just to be neighborly
App::uses('AppTestFixture', 'Icing.Lib');

class AppTestCase extends CakeTestCase {


	/**
	 * Fixtures to always include
	 *
	 * You can also specify with:
	 *   Configure::write('fixtures');
	 *
	 * @var array
	 */
	public $fixtures = array();

	/**
	 * Fixtures groups (defaults set, will get loaded on all tests)
	 *   The list of fixtureGroups to include, derived from fixtureGroupsConfig
	 *
	 * Usage from TestCases:
	 * 	$this->fixtureGroups = array('UsersToys', 'StuffForBlog', 'etc');
	 *
	 * You can also specify with:
	 *   Configure::write('fixtureGroups');
	 *
	 * @var array
	 */
	public $fixtureGroups = array(
		/*
		 * specify any group names from fixtureGroupsConfig
		 * eg:
		'UsersToys',
		 */
	);

	/**
	 * Fixtures groups config
	 *   The definition of the fixtureGroups and what fixtures they contain
	 *
	 * Extend from TestCases:
	 * 	$this->fixtureGroupsConfig = array(
	 *		'GroupName' => array(
	 *			'app.fixture_a',
	 *			'app.fixture_b',
	 *		)
	 *	);
	 *
	 * You can also specify with:
	 *   Configure::write('fixtureGroupsConfig');
	 *
	 * you'd want this to be a single configuration shared across most of your
	 * tests, otherwise, what's the point?
	 *
	 * @var array
	 */
	public $fixtureGroupsConfig = array(
		/*
		 * here's an example of how you can define "groups" of fixtures
		 * and then specity which groups you want included from the test
		 * instead of having to list each one in each place...
		 *
		 'UsersToys' => array(
			 'app.user',
			 'app.toy',
			 'app.toys_user',
		 )
		 */
	);

	/**
	 * Fixtures determined from solveDependancies() (CakeDC, plugin)
	 *
	 * @var array
	 */
	public $dependedFixtures = array();

	/**
	 * Autoload entrypoint for fixtures dependecy solver
	 *
	 * @var string
	 */
	public $plugin = null;

	/**
	 * Test to run for the test case (e.g array('testFind', 'testView'))
	 * If this attribute is not empty only the tests from the list will be executed
	 *
	 * @var array
	 */
	protected $_testsToRun = array();

	/**
	 * Constructor
	 *
	 * If a class is extending AppTestCase it will merge these with the extending classes
	 * so that you don't have to put the plugin fixtures into the AppTestCase
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
		if (is_subclass_of($this, 'AppTestCase')) {
			$parentClass = get_parent_class($this);
			$parentVars = get_class_vars($parentClass);
			if (isset($parentVars['fixtures'])) {
				$this->fixtures = array_unique(array_merge($parentVars['fixtures'], $this->fixtures));
			}
			if (isset($parentVars['fixtureGroups'])) {
				$this->fixtureGroups = array_unique(array_merge($parentVars['fixtureGroups'], $this->fixtureGroups));
			}
			if (isset($parentVars['fixtureGroupsConfig'])) {
				$this->fixtureGroupsConfig = array_unique(array_merge($parentVars['fixtureGroupsConfig'], $this->fixtureGroupsConfig));
			}
			if (!empty($this->plugin)) {
				$this->dependedFixtures = $this->solveDependancies($this->plugin);
			}
			if (!empty($this->dependedFixtures)) {
				foreach ($this->dependedFixtures as $plugin) {
					$fixtures = $this->loadConfig('fixtures', $plugin);
					if (!empty($fixtures)) {
						$this->fixtures = array_unique(array_merge($this->fixtures, $fixtures));
					}
				}
			}
			// inject Configure::read('fixtures');
			$configuredFixtures = Configure::read('fixtures');
			if (is_array($configuredFixtures) && !empty($configuredFixtures)) {
				$this->fixtures = array_unique(array_merge($this->fixtures, $configuredFixtures));
			}
			// inject Configure::read('fixtureGroups');
			$configuredFixtureGroups = Configure::read('fixtureGroups');
			if (is_array($configuredFixtureGroups) && !empty($configuredFixtureGroups)) {
				$this->fixtureGroups = array_unique(array_merge($this->fixtureGroups, $configuredFixtureGroups));
			}
			// inject Configure::read('fixtureGroups');
			$configuredFixtureGroupsConfig = Configure::read('fixtureGroupsConfig');
			if (is_array($configuredFixtureGroupsConfig) && !empty($configuredFixtureGroupsConfig)) {
				$this->fixtureGroupsConfig = Set::merge($this->fixtureGroupsConfig, $configuredFixtureGroupsConfig);
			}
			// inject the fixtureGroups
			foreach ($this->fixtureGroups as $group) {
				if (!array_key_exists($group, $this->fixtureGroupsConfig)) {
					throw new OutOfBoundsException("Icing.AppTestCase.construct() - unable to find \$group '$group' in fixtureGroupsConfig");
				}
				$this->fixtures = array_unique(array_merge($this->fixtures, $this->fixtureGroupsConfig[$group]));
			}
		}
	}

	/**
	 * Loads a file from app/tests/config/configure_file.php or app/plugins/PLUGIN/tests/config/configure_file.php
	 *
	 * Config file variables should be formated like:
	 *  $config['name'] = 'value';
	 * These will be used to create dynamic Configure vars.
	 *
	 *
	 * @param string $fileName name of file to load, extension must be .php and only the name
	 *     should be used, not the extenstion.
	 * @param string $type Type of config file being loaded. If equal to 'app' core config files will be use.
	 *    if $type == 'pluginName' that plugins tests/config files will be loaded.
	 * @return mixed false if file not found, void if load successful
	 */
	public function loadConfig($fileName, $type = 'app') {
		$found = false;
		if ($type == 'app') {
			$folder = APP . 'Test' . DS . 'Config' . DS;
		} else {
			$folder = App::pluginPath($type);
			if (!empty($folder)) {
				$folder .= 'Test' . DS . 'Config' . DS;
			} else {
				return false;
			}
		}
		if (file_exists($folder . $fileName . '.php')) {
			include($folder . $fileName . '.php');
			$found = true;
		}

		if (!$found) {
			return false;
		}

		if (!isset($config)) {
			$error = __("AppTestCase::load() - no variable \$config found in %s.php", true);
			trigger_error(sprintf($error, $fileName), E_USER_WARNING);
			return false;
		}
		return $config;
	}

	/**
	 * Solves Plugin Fixture dependancies.  Called in AppTestCase::__construct to solve
	 * fixture dependancies.  Uses a Plugins tests/config/dependent and tests/config/fixtures
	 * to load plugin fixtures. To use this feature set $plugin = 'pluginName' in your test case.
	 *
	 * @param string $plugin Name of the plugin to load
	 * @return array Array of plugins that this plugin's test cases depend on.
	 */
	public function solveDependancies($plugin) {
		$found = false;
		$result = array($plugin);
		$add = $result;
		do {
			$changed = false;
			$copy = $add;
			$add = array();
			foreach ($copy as $pluginName) {
				$dependent = $this->loadConfig('dependent', $pluginName);
				if (!empty($dependent)) {
					foreach ($dependent as $parentPlugin) {
						if (!in_array($parentPlugin, $result)) {
							$add[] = $parentPlugin;
							$result[] = $parentPlugin;
							$changed = true;
						}
					}
				}
			}
		} while ($changed);
		return $result;
	}

	/**
	 * Assertion Shortcut
	 * (depriciated?)
	 */
	public function assertIsEmpty($data, $message = 'sorry, it was not empty') {
		return $this->assertTrue(empty($data), $message);
	}

	/**
	 * Assertion Shortcut
	 * (depriciated?)
	 */
	public function assertIsNotEmpty($data, $message = 'sorry, it was empty') {
		return $this->assertFalse(empty($data), $message);
	}

	/**
	 * Assertion Shortcut
	 */
	public function assertKeyExists($key, $data, $message = 'sorry, the key "%s" was not found') {
		return $this->assertTrue(array_key_exists($key, $data), sprintf($message, $key));
	}

	/**
	 * Assertion Shortcut
	 */
	public function assertInArray($val, $data, $message = 'sorry, the value "%s" was not found') {
		return $this->assertTrue(in_array($val, $data), sprintf($message, $val));
	}

	/**
	 * Assertion Shortcut
	 *   assert that all of the keys provided in $expect
	 *   match keys/values in $result
	 *
	 * Flexible:
	 *   order doesn't matter
	 *   extra keys in $result don't matter
	 *
	 * so just pass into $expect the keys you care about, and the others will
	 *   be ignored (useful for UUIDs, autoids, timestampes, etc)
	 *
	 * @param array $expect
	 * @param array $result
	 */
	public function assertArrayCompare($expect, $result, $message = 'sorry, the arrays did not match') {
		$expect = Set::flatten($expect);
		$result = Set::flatten($result);
		$compare = array_intersect_key($result, $expect);
		asort($expect);
		asort($compare);
		return $this->assertEquals($expect, $compare, $message);
	}

	/**
	 * Asserts that data are valid given Model validation rules
	 * Calls the Model::validate() method and asserts the result
	 *
	 * @param Model $Model Model being tested
	 * @param array $data Data to validate
	 * @return void
	 */
	public function assertValid(Model $Model, $data) {
		$this->assertTrue($this->_validData($Model, $data));
	}

	/**
	 * Asserts that data are invalid given Model validation rules
	 * Calls the Model::validate() method and asserts the result
	 *
	 * @param Model $Model Model being tested
	 * @param array $data Data to validate
	 * @return void
	 */
	public function assertInvalid(Model $Model, $data) {
		$this->assertFalse($this->_validData($Model, $data));
	}

	/**
	 * Asserts that data are validation errors match an expected value when
	 * validation given data for the Model
	 * Calls the Model::validate() method and asserts validationErrors
	 *
	 * @param Model $Model Model being tested
	 * @param array $data Data to validate
	 * @param array $expectedErrors Expected errors keys
	 * @return void
	 */
	public function assertValidationErrors($Model, $data, $expectedErrors) {
		$this->_validData($Model, $data, $validationErrors);
		sort($expectedErrors);
		$this->assertEqual(array_keys($validationErrors), $expectedErrors);
	}

	/**
	 * Convenience method allowing to validate data and return the result
	 *
	 * @param Model $Model Model being tested
	 * @param array $data Profile data
	 * @param array $validationErrors Validation errors: this variable will be updated with validationErrors (sorted by key) in case of validation fail
	 * @return boolean Return value of Model::validate()
	 */
	protected function _validData(Model $Model, $data, &$validationErrors = array()) {
		$valid = true;
		$Model->create($data);
		if (!$Model->validates()) {
			$validationErrors = $Model->validationErrors;
			ksort($validationErrors);
			$valid = false;
		} else {
			$validationErrors = array();
		}
		return $valid;
	}


	public static function assertIsA($actual, $expected, $message = '') {
		self::assertType($expected, $actual, $message);
	}

	public static function assertNotA($actual, $expected, $message = '') {
		self::assertNotType($expected, $actual, $message);
	}

	public static function assertTrue($condition, $message = '') {
		parent::assertTrue((bool)$condition, $message);
	}

	public static function assertFalse($condition, $message = '') {
		parent::assertFalse((bool)$condition, $message);
	}

	public function pass($message = '') {
	}

	// public static function assertEqual($expected, $actual, $message = '') {
	// self::assertEquals($expected, $actual, $message);
	// }


	/**
	 * array `shuffle` while maintaining keys
	 * useful for testing arrays in different order
	 * @param array $array
	 * @return boolean
	 */
	public function shuffle_assoc(&$array) {
		$keys = array_keys($array);
		shuffle($keys);
		foreach($keys as $key) {
			$new[$key] = $array[$key];
		}
		$array = $new;
		return true;
	}
}


