<?php
/**
 * SummableBehavior
 * Helpful tools for
 *   finding sums
 *   calculating median
 *   calculating Standard Deviation
 *   calculating MAD
 *   etc..
 *
 * ./cake test app Model/Behavior/SummableBehavior
 *
 * these will be needed for gathering values from databases
 * in standard cake finds
 *
 *  (primary methods)
 *  find('sum', array('fields' => 'fieldname', ...));
 *
 *  (internal helpers)
 *
 */
App::uses('Model', 'Model');
App::uses('ModelBehavior', 'Model');
App::uses('SummableBehavior', 'Icing.Model/Behavior');

/**
 * Stat model
 */
class Stat extends CakeTestModel {
	public $actsAs = array('Summable');
}
/**
 * Stat model
 */
class User extends CakeTestModel {
	public $actsAs = array('Summable');
}
/**
 * SummableBehavior Test Case
 *
 */
class SummableBehaviorTest extends CakeTestCase {

	/**
	 * Fixtures
	 *
	 * @var array
	 */
	public $fixtures = array(
		'plugin.icing.stat', 'core.user'
	);

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->Summable = new SummableBehavior();
		$this->Stat = ClassRegistry::init('Stat');
		$this->User = ClassRegistry::init('User');
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown() {
		unset($this->Summable);
		unset($this->Stat);
		unset($this->User);
		parent::tearDown();
	}

	/**
	 * test findSum or really find('sum', ...)
	 * a test of the customFind method setup in the Behavior
	 */
	public function testFindSumBool() {
		$result = $this->Stat->find('sum', array( 'fields' => 'boolean',));
		$expect = $this->Stat->find('count', array('conditions' => array('boolean' => 1)));
		$this->assertEquals($result, $expect);
		$this->assertNotEmpty($result);
		$result = $this->Stat->find('sum', array( 'fields' => 'boolean', 'conditions' => array('string LIKE' => 'record 4%')));
		$expect = $this->Stat->find('count', array('conditions' => array('boolean' => 1, 'string LIKE' => 'record 4%')));
		$this->assertEquals($result, $expect);
		$this->assertNotEmpty($result);
		$result = $this->Stat->find('sum', array( 'fields' => 'boolean', 'conditions' => array('string' => 'notexist')));
		$expect = $this->Stat->find('count', array('conditions' => array('boolean' => 1, 'string' => 'notexist')));
		$this->assertEquals($result, $expect);
		$this->assertEmpty($result);
		/* */
	}

	/**
	 * test findSum or really find('sum', ...)
	 * a test of the customFind method setup in the Behavior
	 */
	public function testFindSumInt() {
		// and now to add some non-0 values
		$count = $this->Stat->find('count');
		$list = $this->Stat->find('list', array('fields' => array('id', 'integer')));
		$expect = array_sum($list);
		$result = $this->Stat->find('sum', array( 'fields' => 'integer'));
		$this->assertEquals($result, $expect);
		$this->assertNotEmpty($result);
		$this->assertTrue($result > $count); // ensures more than basic count
		// and now, confirm it works with the first of the passed in fields, only
		$result = $this->Stat->find('sum', array( 'fields' => array('integer', 'float')));
		$this->assertEquals($result, $expect);
		$this->assertNotEmpty($result);
		$this->assertTrue($result > $count); // ensures more than basic count
		// and now, confirm it works with the first of the passed in fields, only (as string)
		$result = $this->Stat->find('sum', array( 'fields' => 'integer,float'));
		$this->assertEquals($result, $expect);
		$this->assertNotEmpty($result);
		$this->assertTrue($result > $count); // ensures more than basic count
		// and now, confirm it works with the Model.field
		$result = $this->Stat->find('sum', array( 'fields' => 'Stat.integer'));
		$this->assertEquals($result, $expect);
		$this->assertNotEmpty($result);
		$this->assertTrue($result > $count); // ensures more than basic count
		// and now, confirm it works with the Model.field
		$result = $this->Stat->find('sum', array( 'fields' => 'Stat.integer,Stat.float'));
		$this->assertEquals($result, $expect);
		$this->assertNotEmpty($result);
		$this->assertTrue($result > $count); // ensures more than basic count
		/*  */
	}
	/**
	 * test findSum or really find('sum', ...)
	 * a test of the customFind method setup in the Behavior
	 */
	public function testFindSumFloat() {
		// and now to add some non-0 values
		$count = $this->Stat->find('count');
		$list = $this->Stat->find('list', array('fields' => array('id', 'float')));
		$expect = array_sum($list);
		$result = $this->Stat->find('sum', array( 'fields' => 'float'));
		$this->assertEquals($result, $expect);
		$this->assertNotEmpty($result);
		$this->assertTrue($result > $count); // ensures more than basic count
		// and now, confirm it works with the first of the passed in fields, only
		$result = $this->Stat->find('sum', array( 'fields' => array('float', 'integer')));
		$this->assertEquals($result, $expect);
		$this->assertNotEmpty($result);
		$this->assertTrue($result > $count); // ensures more than basic count
		// and now, confirm it works with the first of the passed in fields, only (as string)
		$result = $this->Stat->find('sum', array( 'fields' => 'float,integer'));
		$this->assertEquals($result, $expect);
		$this->assertNotEmpty($result);
		$this->assertTrue($result > $count); // ensures more than basic count
		// and now, confirm it works with the Model.field
		$result = $this->Stat->find('sum', array( 'fields' => 'Stat.float'));
		$this->assertEquals($result, $expect);
		$this->assertNotEmpty($result);
		$this->assertTrue($result > $count); // ensures more than basic count
		// and now, confirm it works with the Model.field
		$result = $this->Stat->find('sum', array( 'fields' => 'Stat.float,Stat.integer'));
		$this->assertEquals($result, $expect);
		$this->assertNotEmpty($result);
		$this->assertTrue($result > $count); // ensures more than basic count
		/*  */
	}
}
