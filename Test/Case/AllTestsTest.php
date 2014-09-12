<?php

Configure::write('inUnitTest', true);

class AllTestsTest extends PHPUnit_Framework_TestSuite {

/**
 * suite method, defines tests for this suite.
 *
 * @return void
 */
	public static function suite() {
		$suite = new CakeTestSuite('All Tests');
		$suite->addTestDirectoryRecursive(App::pluginPath('Icing') . 'Test' . DS . 'Case' . DS);

		return $suite;
	}
}
