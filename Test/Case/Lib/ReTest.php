<?php
App::uses('Re', 'Icing.Lib');

/**
 * Re Library Test Case
 *
 */
class ReTest extends CakeTestCase {
	public $fixtures = array();
	public $previousConfig = null;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp() {
		$this->previousConfig = Re::$config;
		Re::$config = Re::$defaultConfig;
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
		Re::$config = $this->previousConfig;
		parent::tearDown();
	}

	public function testIsValid() {
		// true == non-empties and 0
		$this->assertTrue(Re::isValid(true));
		$this->assertTrue(Re::isValid(1));
		$this->assertTrue(Re::isValid(0)); // zero is allowed
		$this->assertTrue(Re::isValid('something'));
		$this->assertTrue(Re::isValid(array(1)));
		$this->assertTrue(Re::isValid(array(0)));
		// false == most empties
		$this->assertFalse(Re::isValid(''));
		$this->assertFalse(Re::isValid(false));
		$this->assertFalse(Re::isValid(null));
		$this->assertFalse(Re::isValid(array()));
	}

	public function testasArray() {
		// empties
		$this->assertEquals(Re::asArray(array()), array());
		$this->assertEquals(Re::asArray(''), array());
		$this->assertEquals(Re::asArray(null), array());
		$this->assertEquals(Re::asArray(false), array());
		$this->assertEquals(Re::asArray(0), array());
		// arrays = passthrough
		$input = array(1,2,3 => array(4,5));
		$this->assertEquals(Re::asArray($input), $input);
		// serialize
		$this->assertEquals(Re::asArray(serialize($input)), $input);
		// JSON
		$this->assertEquals(Re::asArray(json_encode($input)), $input);
		// string/int... ends up inside a new array
		$this->assertEquals(Re::asArray('foobar'), array('foobar'));
		$this->assertEquals(Re::asArray(555), array(555));
		// object.. walks the properties
		//  - Re has none..
		$obj = new Re();
		$this->assertEquals(Re::asArray($obj), array());
		//  - $this has many properties...
		$this->TestingReAsArray = time();
		$expect = array(
			'fixtures',
			'previousConfig',
			'fixtureManager',
			'autoFixtures',
			'dropTables',
			'db',
			'TestingReAsArray',
		);
		$result = Re::asArray($this);
		$this->assertEquals(array_keys($result), $expect);
		$this->assertEquals($result['TestingReAsArray'], $this->TestingReAsArray);
		unset($this->TestingReAsArray);
	}

	public function testasString() {
		// strings = passthrough
		$this->assertEquals(Re::asString('foobar'), 'foobar');
		$this->assertEquals(Re::asString(555), 555);
		// arrays = JANKY = just gives you a JSON dump (see stringCSV())
		$input = array(1,2,3 => array(4,5));
		$this->assertEquals(Re::asString($input), json_encode($input));

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

	public function testBefore() {
		$this->assertEquals('valid', Re::before('valid,invalid'));
		$this->assertEquals('valid', Re::before('valid,inv,alid'));
		$this->assertEquals('valid', Re::before('valid[spliter]invalid', '[spliter]'));
	}

	public function testAfter() {
		$this->assertEquals('valid', Re::after('invalid,valid'));
		$this->assertEquals('valid', Re::after('inva,lid,valid'));
		$this->assertEquals('valid', Re::after('invalid[spliter]valid', '[spliter]'));
	}

	public function testMergeIfEmpty() {
		$data = array(
			'id' => 1,
			'name' => 'first Name',
			'empty' => '',
			'null' => null,
			'true' => true,
			'false' => false,
			'zero' => 0,
		);
		$defaults = array(
			'new' => 'sss',
			'id' => 'ttt',
			'empty' => 'uuu',
			'null' => 'vvv',
			'name' => 'wwww',
			'true' => 'xxx',
			'false' => 'yyy',
			'zero' => 'zzz',
		);
		$expect = array(
			'new' => 'sss',
			'id' => 1,
			'name' => 'first Name',
			'empty' => 'uuu',
			'null' => 'vvv',
			'true' => true,
			'false' => 'yyy',
			'zero' => 0,
		);
		$this->assertEquals($expect, Re::mergeIfEmpty($defaults, $data));
		// try with nested arrays, default un-nested == simple merge, no overwrites
		$data = array('User' => $data);
		$data['Alt'] = array('id' => 5, 'zero' => 0);
		$data['User']['Nest'] = array('id' => 5, 'zero' => 0);
		$expect = $defaults;
		$expect['Alt'] = $data['Alt'];
		$expect['User'] = $data['User'];
		$this->assertEquals($expect, Re::mergeIfEmpty($defaults, $data));
		// try with nested defaults/data
		$defaults = array('User' => $defaults);
		$expect = array(
			'User' => array(
				'new' => 'sss',
				'id' => 1,
				'name' => 'first Name',
				'empty' => 'uuu',
				'null' => 'vvv',
				'true' => true,
				'false' => 'yyy',
				'zero' => 0,
				'Nest' => $data['User']['Nest'],
			),
			'Alt' => $data['Alt'],
		);
		$this->assertEquals($expect, Re::mergeIfEmpty($defaults, $data));
	}
}
