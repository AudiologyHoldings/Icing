<?php
App::uses('PhpTidy', 'Icing.Lib');

/**
 * PhpTidy Library Test Case
 *
 * @package
 */


class PhpTidyTest extends CakeTestCase {
	public $fixtures = array();

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();
	}

	/**
	 *
	 */
	public function testPhpTidyScript() {
		$expect = dirname(dirname(dirname(__DIR__))) . DS . 'Vendor' . DS . 'phptidy.php';
		$this->assertEquals($expect, PhpTidy::script());
	}

	/**
	 *
	 */
	public function testPhpTidyString_basic() {
		// pass in a sting of PHP code to tidy up
		$basic = "<?php /* basic */ ?>\n";
		$this->assertEquals($basic, PhpTidy::string($basic));
		$noTrailingLine = '<?php /* basic */ ?>';
		$this->assertEquals($basic, PhpTidy::string($noTrailingLine));
	}

	/**
	 *
	 */
	public function testPhpTidyString_formatting() {
		// pass in a sting of PHP code to tidy up
		$input = '<?php
		Class Testerizer extends Object {
			/**
			* bad docblock
			* @param string $a
			* @return mixed $output
			*/
			public function func1 ($a = "string")    {
		return $output; ' . '
	}
				public static function func2(){}
			}
		?>';
		$expect = '<?php' . "\n" .
		'class Testerizer extends Object {' . "\n" .
		'	/**' . "\n" .
		'	 * bad docblock' . "\n" .
		'	 *' . "\n" .
		'	 * @param string  $a' . "\n" .
		'	 * @return mixed $output' . "\n" .
		'	 */' . "\n" .
		'	public function func1($a = "string") {' . "\n" .
		'		return $output;' . "\n" .
		'	}' . "\n" .
		'	/**' . "\n" .
		'	 *' . "\n" .
		'	 */' . "\n" .
		'	public static function func2() {}' . "\n" .
		'}' . "\n" .
		"?>\n";
		$this->assertEquals($expect, PhpTidy::string($input));
	}

	/**
	 *
	 */
	public function testPhpTidyFile_good_single() {
		// pass in a filename of PHP code to tidy up
		$noTrailingLine = '<?php /* basic */ ?>';
		$path = TMP . 'test-phptidytest-1.php';
		file_put_contents($path, $noTrailingLine);
		$output = PhpTidy::file($path);
		$this->assertEquals('Replaced 1 files.', $output);
		$result = file_get_contents($path);
		$expect = "<?php /* basic */ ?>\n";
		$this->assertEquals($expect, $result);
	}
}
