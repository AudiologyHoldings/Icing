<?php
App::uses('Base62', 'Icing.Lib');

/**
 * Base62 Library Test Case
 *
 */
class Base62Test extends CakeTestCase {
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

	public function testEncode() {
		$input = 1234567890;
		$result = Base62::encode($input);
		$expect = '1ly7vk';
		$this->assertEquals($result, $expect);
		$input = 1234567891;
		$result = Base62::encode($input);
		$expect = '1ly7vl';
		$this->assertEquals($result, $expect);
		$input = 1234567893;
		$result = Base62::encode($input);
		$expect = '1ly7vn';
		$this->assertEquals($result, $expect);
		$input = 1234567901;
		$result = Base62::encode($input);
		$expect = '1ly7vv';
		$this->assertEquals($result, $expect);
	}

	public function testDecode() {
		$input = '1ly7vk';
		$result = Base62::decode($input);
		$expect = 1234567890;
		$this->assertEquals($result, $expect);
		$input = '1ly7vl';
		$result = Base62::decode($input);
		$expect = 1234567891;
		$this->assertEquals($result, $expect);
		$input = '1ly7vn';
		$result = Base62::decode($input);
		$expect = 1234567893;
		$this->assertEquals($result, $expect);
		$input = '1ly7vv';
		$result = Base62::decode($input);
		$expect = 1234567901;
		$this->assertEquals($result, $expect);
	}

}
