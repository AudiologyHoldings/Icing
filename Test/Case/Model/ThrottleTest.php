<?php
/**
 * Throttle Test
 */
App::uses('Throttle', 'Icing.Model');
class ThrottleTest extends CakeTestCase {
	public $fixtures = array(
		'plugin.icing.throttle',
	);

	public function startTest($method) {
		parent::startTest($method);
		$this->Throttle = ClassRegistry::init('Icing.Throttle');
	}
	public function endTest($method) {
		unset($this->Throttle);
		parent::endTest($method);
	}

	public function testCheck() {
		$this->assertEqual(0, $this->Throttle->find('count'));
		$this->assertTrue($this->Throttle->check('a', 10));
		$this->assertEqual(0, $this->Throttle->find('count'));
		$this->Throttle->create(false);
		$this->Throttle->save(array('key' => 'a', 'expire_epoch' => time() - 5));
		$this->Throttle->create(false);
		$this->Throttle->save(array('key' => 'a', 'expire_epoch' => time() - 2));
		$this->Throttle->create(false);
		$this->Throttle->save(array('key' => 'a', 'expire_epoch' => time() + 2));
		$this->Throttle->create(false);
		$this->Throttle->save(array('key' => 'a', 'expire_epoch' => time() + 5));
		$this->Throttle->create(false);
		$this->Throttle->save(array('key' => 'b', 'expire_epoch' => time() + 5));
		$this->assertEqual(5, $this->Throttle->find('count'));
		$this->assertTrue($this->Throttle->check('a', 10));
		// purge was called inside check
		$this->assertEqual(3, $this->Throttle->find('count'));
		$this->assertFalse($this->Throttle->check('a', 1));
		$this->assertTrue($this->Throttle->check('b', 1));
		$this->assertTrue($this->Throttle->check('a', 2));
		$this->Throttle->create(false);
		$this->Throttle->save(array('key' => 'b', 'expire_epoch' => time() + 5));
		$this->Throttle->create(false);
		$this->Throttle->save(array('key' => 'b', 'expire_epoch' => time() + 5));
		$this->assertFalse($this->Throttle->check('b', 1));
		$this->assertFalse($this->Throttle->check('b', 2));
		$this->assertTrue($this->Throttle->check('b', 3));
		$this->assertTrue($this->Throttle->check('a', 2));
		$this->assertEqual(5, $this->Throttle->find('count'));
	}

	public function testRecord() {
		$this->assertEqual(0, $this->Throttle->find('count'));
		$this->assertTrue($this->Throttle->record('a', 5));
		$this->assertEqual(1, $this->Throttle->find('count'));
		$found = $this->Throttle->find('first');
		$compare = $found['Throttle'];
		unset($compare['id']);
		unset($compare['created']);
		$expect = array(
			'key' => 'a',
			'expire_epoch' => time() + 5,
		);
		$this->assertEqual($expect, $compare);
		$this->assertEqual(1, $this->Throttle->find('count'));
		$this->assertTrue($this->Throttle->record('a', 5));
		$this->assertTrue($this->Throttle->record('b', -5));
		$this->assertTrue($this->Throttle->record('c', 3600));
		$this->assertTrue($this->Throttle->record('c', 86400));
		$this->assertEqual(5, $this->Throttle->find('count'));
	}

	public function testCheckThenSet() {
		// most of this is tested in subfunction tests above
		$this->assertEqual(0, $this->Throttle->find('count'));
		$this->assertTrue($this->Throttle->checkThenRecord('a', 2, 5));
		$this->assertTrue($this->Throttle->checkThenRecord('a', 2, 5));
		$this->assertTrue($this->Throttle->checkThenRecord('a', 2, 5));
		$this->assertEqual(3, $this->Throttle->find('count'));
		$this->assertFalse($this->Throttle->checkThenRecord('a', 2, 5));
		$this->assertFalse($this->Throttle->checkThenRecord('a', 2, 5));
		$this->assertEqual(3, $this->Throttle->find('count'));
	}

	public function testLimit() {
		// alias of testCheckThenSet()
		$this->assertEqual(0, $this->Throttle->find('count'));
		$this->assertTrue($this->Throttle->limit(__function__, 2, 5));
		$this->assertTrue($this->Throttle->limit(__function__, 2, 5));
		$this->assertTrue($this->Throttle->limit(__function__, 2, 5));
		$this->assertEqual(3, $this->Throttle->find('count'));
		$this->assertFalse($this->Throttle->limit(__function__, 2, 5));
		$this->assertFalse($this->Throttle->limit(__function__, 2, 5));
		$this->assertEqual(3, $this->Throttle->find('count'));
	}

	public function testPurge() {
		$this->assertEqual(0, $this->Throttle->find('count'));
		$this->Throttle->create(false);
		$this->Throttle->save(array('key' => 'a', 'expire_epoch' => time() - 5));
		$this->Throttle->create(false);
		$this->Throttle->save(array('key' => 'a', 'expire_epoch' => time() - 2));
		$this->Throttle->create(false);
		$this->Throttle->save(array('key' => 'a', 'expire_epoch' => time() + 2));
		$this->Throttle->create(false);
		$this->Throttle->save(array('key' => 'a', 'expire_epoch' => time() + 5));
		$this->Throttle->create(false);
		$this->Throttle->save(array('key' => 'b', 'expire_epoch' => time() + 5));
		$this->assertEqual(5, $this->Throttle->find('count'));
		$this->assertEqual(2, $this->Throttle->purge());
		$this->assertEqual(3, $this->Throttle->find('count'));
	}


}
