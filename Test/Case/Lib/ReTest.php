<?php
App::uses('Re', 'Icing.Lib');

/**
 * As Library Test Case
 *
 */
class AsTest extends CakeTestCase {
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

	public function testPluck() {
		$input = array('User' => array('id' => 1, 'name' => 'first Name', 'empty' => '', 'null' => null, 'true' => true, 'false' => false, 'zero' => 0, 'nest' => array('id' => 2, 'name' => 'nested', 'empty' => '',)));
		$this->assertEquals(Re::pluck($input, '/User/id'), 1);
		$this->assertEquals(Re::pluck($input, '/User/name'), 'first Name');
		$this->assertEquals(Re::pluck($input, '/User/nest/id'), 2);
		$this->assertEquals(Re::pluck($input, '/User/nest/name'), 'nested');
		$this->assertEquals(Re::pluck($input, '/User/nest'), $input['User']['nest']);
		$this->assertEquals(Re::pluck($input, '/User'), $input['User']);
		$this->assertEquals(Re::pluck($input, '/bad-path'), null);
		$this->assertEquals(Re::pluck($input, '/bad-path', null), null);
		$this->assertEquals(Re::pluck($input, '/bad-path', 'default value'), 'default value');
		$this->assertEquals(Re::pluck($input, '/bad-path', $input), $input);
		$this->assertEquals(Re::pluck($input, '/nest'), null);
		$this->assertEquals(Re::pluck($input, '/User/empty'), '');
		$this->assertEquals(Re::pluck($input, '/User/null'), null);
		$this->assertEquals(Re::pluck($input, '/User/true'), true);
		$this->assertEquals(Re::pluck($input, '/User/false'), false);
		$this->assertEquals(Re::pluck($input, '/User/zero'), 0);
		$this->assertEquals(Re::pluck($input, '/User/nested/emtpy'), '');
		// now look for array of paths (first match returns)
		$this->assertEquals(Re::pluck($input, array('/User/id', '/User/id')), 1);
		$this->assertEquals(Re::pluck($input, array('/User/id', '/User/name')), 1);
		$this->assertEquals(Re::pluck($input, array('/User/name', '/User/id')), 'first Name');
		$this->assertEquals(Re::pluck($input, array('/User/id', '/User/bad-path')), 1);
		$this->assertEquals(Re::pluck($input, array('/User/bad-path', '/User/id')), 1);
		$this->assertEquals(Re::pluck($input, array('/bad-path', '/User/id')), 1);
		$this->assertEquals(Re::pluck($input, array('/User/empty', '/User/id')), '');
		$this->assertEquals(Re::pluck($input, array('/User/bad-path', '/User/empty')), '');
	}

	public function testPluckValid() {
		$input = array('User' => array('id' => 1, 'name' => 'first Name', 'empty' => '', 'null' => null, 'true' => true, 'false' => false, 'zero' => 0, 'nest' => array('id' => 2, 'name' => 'nested', 'empty' => '',)));
		$this->assertEquals(Re::pluckValid($input, '/User/id'), 1);
		$this->assertEquals(Re::pluckValid($input, '/User/name'), 'first Name');
		$this->assertEquals(Re::pluckValid($input, '/User/nest/id'), 2);
		$this->assertEquals(Re::pluckValid($input, '/User/nest/name'), 'nested');
		$this->assertEquals(Re::pluckValid($input, '/User/nest'), $input['User']['nest']);
		$this->assertEquals(Re::pluckValid($input, '/User'), $input['User']);
		$this->assertEquals(Re::pluckValid($input, '/bad-path'), null);
		$this->assertEquals(Re::pluckValid($input, '/bad-path', null), null);
		$this->assertEquals(Re::pluckValid($input, '/bad-path', 'default value'), 'default value');
		$this->assertEquals(Re::pluckValid($input, '/bad-path', $input), $input);
		$this->assertEquals(Re::pluckValid($input, '/nest'), null);
		$this->assertEquals(Re::pluckValid($input, '/User/empty'), null); // empty match = default
		$this->assertEquals(Re::pluckValid($input, '/User/null'), null);
		$this->assertEquals(Re::pluckValid($input, '/User/true'), true);
		$this->assertEquals(Re::pluckValid($input, '/User/false'), null); // empty match = default
		$this->assertEquals(Re::pluckValid($input, '/User/zero'), 0); // 0 isValid = match
		$this->assertEquals(Re::pluckValid($input, '/User/nested/emtpy'), '');
		// now look for array of paths (first match returns)
		$this->assertEquals(Re::pluckValid($input, array('/User/id', '/User/id')), 1);
		$this->assertEquals(Re::pluckValid($input, array('/User/id', '/User/name')), 1);
		$this->assertEquals(Re::pluckValid($input, array('/User/name', '/User/id')), 'first Name');
		$this->assertEquals(Re::pluckValid($input, array('/User/id', '/User/bad-path')), 1);
		$this->assertEquals(Re::pluckValid($input, array('/User/bad-path', '/User/id')), 1);
		$this->assertEquals(Re::pluckValid($input, array('/bad-path', '/User/id')), 1);
		$this->assertEquals(Re::pluckValid($input, array('/bad-path', '/User/false')), null);
		$this->assertEquals(Re::pluckValid($input, array('/User/false', '/bad-path')), null);
		$this->assertEquals(Re::pluckValid($input, array('/bad-path', '/User/true')), true);
		$this->assertEquals(Re::pluckValid($input, array('/User/true', '/bad-path')), true);
		$this->assertEquals(Re::pluckValid($input, array('/User/empty', '/User/id')), 1); // /User/empty not valid, so we jump to second path
		$this->assertEquals(Re::pluckValid($input, array('/User/bad-path', '/User/empty')), null); // empty match = default
	}


	public function testPluckIsValid() {
		$input = array('User' => array('id' => 1, 'name' => 'first Name', 'empty' => '', 'null' => null, 'true' => true, 'false' => false, 'zero' => 0, 'nest' => array('id' => 2, 'name' => 'nested', 'empty' => '',)));
		$this->assertTrue(Re::pluckIsValid($input, '/User/id'));
		$this->assertTrue(Re::pluckIsValid($input, '/User/name'));
		$this->assertTrue(Re::pluckIsValid($input, '/User/nest/id'));
		$this->assertTrue(Re::pluckIsValid($input, '/User/nest/name'));
		$this->assertTrue(Re::pluckIsValid($input, '/User/nest'));
		$this->assertTrue(Re::pluckIsValid($input, '/User'));
		$this->assertFalse(Re::pluckIsValid($input, '/bad-path'));
		$this->assertFalse(Re::pluckIsValid($input, '/nest'));
		$this->assertFalse(Re::pluckIsValid($input, '/User/empty'));
		$this->assertFalse(Re::pluckIsValid($input, '/User/null'));
		$this->assertTrue(Re::pluckIsValid($input, '/User/true'));
		$this->assertFalse(Re::pluckIsValid($input, '/User/false'));
		$this->assertTrue(Re::pluckIsValid($input, '/User/zero'));
		$this->assertFalse(Re::pluckIsValid($input, '/User/nested/emtpy'));
		// now look for array of paths (first match returns)
		$this->assertTrue(Re::pluckIsValid($input, array('/User/id', '/User/id')));
		$this->assertTrue(Re::pluckIsValid($input, array('/User/id', '/User/name')));
		$this->assertTrue(Re::pluckIsValid($input, array('/User/name', '/User/id')));
		$this->assertTrue(Re::pluckIsValid($input, array('/User/id', '/User/bad-path')));
		$this->assertTrue(Re::pluckIsValid($input, array('/User/bad-path', '/User/id')));
		$this->assertTrue(Re::pluckIsValid($input, array('/bad-path', '/User/id')));
		$this->assertTrue(Re::pluckIsValid($input, array('/User/empty', '/User/id')));
		$this->assertTrue(Re::pluckIsValid($input, array('/User/empty', '/User/true')));
		$this->assertTrue(Re::pluckIsValid($input, array('/User/true', '/User/empty')));
		$this->assertFalse(Re::pluckIsValid($input, array('/User/bad-path', '/User/empty')));
		$this->assertFalse(Re::pluckIsValid($input, array('/User/bad-path', '/User/alt-bad-path')));
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
