<?php
App::uses('Re', 'Icing.Lib');

/**
 * As Library Test Case
 *
 */
class AsTest extends CakeTestCase {
	public $fixtures = array();

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		//$this->User = ClassRegistry::init('User');
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown() {
		unset($this->User);
		parent::tearDown();
	}

	public function testasArray() {

	}

	public function testasString() {

	}

	public function testasArrayCSV() {
		$input = array('a', 'b', 'c', 'd' => 'four', 'e' => 5, 'f' => null, 'g' => false, 'h' => true, 'i' => '');
		$result = Re::arrayCSV($input);
		$this->assertEquals($result, $input);
		$input = 'a,b,c,d,four,e,0,1,2,3,[NULL],asd;asd|asd"asd\'asd[asd]asd\\asd';
		$expect = array('a', 'b', 'c', 'd', 'four', 'e', '0', '1', '2', '3', '[NULL]', 'asd;asd|asd"asd\'asd[asd]asd\\asd');
		$result = Re::arrayCSV($input);
		$this->assertEquals($result, $expect);
		$expect = array('a', 'b', 'c', 'd', 'four', 'e', '0', '1', '2', '3', '[NULL]', 'asd', 'asd', 'asd"asd\'asd[asd]asd\\asd');
		$result = Re::arrayCSV($input, ';,|');
		$this->assertEquals($result, $expect);
		$expect = array('a', 'b', 'c', 'd', 'four', 'e', '0', '1', '2', '3', '[NULL]', 'asd', 'asd', 'asd', 'asd', 'asd[asd]asd', 'asd');
		$result = Re::arrayCSV($input, ';,|,",\',\\');
		$this->assertEquals($result, $expect);
	}

	public function testStringCSV() {
		$input = 'a,b,c,d,four,e,0,1,2,3,[NULL],asd;asd|asd"asd\'asd[asd]asd\\asd';
		$result = Re::stringCSV($input);
		$this->assertEquals($result, $input);
		$input = array('a', 'b', 'c', 'd' => 'four', 'e' => 5, 'f' => null, 'g' => false, 'h' => true, 'i' => '');
		$expect = 'a,b,c,four,5,,,1,';
		$result = Re::stringCSV($input);
		$this->assertEquals($result, $expect);
		$expect = 'a,b,c,four,5,1';
		$result = Re::stringCSV(Re::cleanArray($input));
		$this->assertEquals($result, $expect);
	}

	public function testCleanArray() {
		$input = array('a', 'b', 'c', 'd' => 'four', 'e' => 5, 'f' => null, 'g' => false, 'h' => true, 'i' => '', 'j' => 0, 'k' => '"');
		$expect = array('a', 'b', 'c', 'd' => 'four', 'e' => 5, 'h' => true, 'j' => 0, 'k' => '"');

		$result = Re::cleanArray(Re::cleanArray($input));
		$this->assertEquals($result, $expect);
		$result = Re::cleanArray(Re::cleanArray($result));
		$this->assertEquals($result, $expect);
	}

}
