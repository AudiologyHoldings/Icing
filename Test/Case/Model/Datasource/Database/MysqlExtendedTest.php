<?php

App::uses('Model', 'Model');
App::uses('AppModel', 'Model');
App::uses('Mysql', 'Model/Datasource/Database');
App::uses('MysqlExtended', 'Icing.Model/Datasource/Database');
App::uses('CakeSchema', 'Model');


class Article extends AppModel {
	public $useTable = 'articles';
}


class MysqlExtendedTest extends CakeTestCase {

	public $autoFixtures = true;
	public $fixtures = array(
		'core.article',
	);
	public $Dbo = null;
	public $Article = null;

	/**
	 * Sets up a Dbo class instance for testing
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->Dbo = ConnectionManager::getDataSource('test');
		if (!($this->Dbo instanceof MysqlExtended)) {
			$this->markTestSkipped('The MySQL Datasource MysqlExtended extension is not available.');
		}
		$this->_debug = Configure::read('debug');
		Configure::write('debug', 1);
		$this->Article = ClassRegistry::init('Article');

		// Mock it all up
		$this->DboMock = $this->getMock('MysqlExtended', [
			'connect',
			'describe',
			'executeOnParent',
		]);

		// mock the connection, so it hits the real DB
		$this->DboMock
			->expects($this->any())
			->method('connect')
			->will($this->returnValue($this->Dbo->connect()));

		$this->DboMock
			->expects($this->any())
			->method('describe')
			->will($this->returnValue($this->Dbo->describe($this->Article)));

		// mock the exception
		$this->e = new PDOException('SQLSTATE[HY000]: General error: 1205 Lock wait timeout exceeded; try restarting transaction');
		$this->e->queryString = 'DELETE `IcingVersion` WHERE `id` = 1';

		$this->DboMock
			->expects($this->any())
			->method('executeOnParent')
			->will($this->throwException($this->e));

		$this->ArticleMock = $this->getMockForModel('Article', array('getDataSource'));
		$this->ArticleMock
			->expects($this->any())
			->method('getDataSource')
			->will($this->returnValue($this->DboMock));
	}

	/**
	 * Sets up a Dbo class instance for testing
	 *
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Article);
		unset($this->Dbo);
		unset($this->e);
		unset($this->DboMock);
		unset($this->ArticleMock);
		ClassRegistry::flush();
		Configure::write('debug', $this->_debug);
	}

	public function testSetup() {

		// unmocked, working...
		$article = $this->Article->find('first');
		$this->assertEqual(
			$article['Article']['id'],
			1
		);

		// mocked, throws exception
		try {
			$this->ArticleMock->find('first');
			$this->assertEqual(
				'Expected Exception',
				'!!got none!!'
			);
		} catch (PDOException $e) {
			// mock gave us the right message
			$this->assertEqual(
				$e->getMessage(),
				'SQLSTATE[HY000]: General error: 1205 Lock wait timeout exceeded; try restarting transaction'
			);
			// mock gave us the "extra" info we need for the query...
			$this->assertEqual(
				$e->queryString,
				'DELETE `IcingVersion` WHERE `id` = 1'
			);
		}
	}

	public function testExecuteMockedOnceThenWorked() {
		$DboMock = $this->getMock('MysqlExtended', [
			'connect',
			'describe',
			'executeOnParent',
		]);
		$DboMock
			->expects($this->any())
			->method('connect')
			->will($this->returnValue($this->Dbo->connect()));
		$DboMock
			->expects($this->any())
			->method('describe')
			->will($this->returnValue($this->Dbo->describe($this->Article)));

		// core execute: first time w/ Exception
		$DboMock
			->expects($this->at(0))
			->method('executeOnParent')
			->will($this->throwException($this->e));

		// core execute: seconds time = result
		$i = rand(99, 99999);
		$DboMock
			->expects($this->at(1))
			->method('executeOnParent')
			->will($this->returnValue($i));

		// mocked, first time throws exception, then parent works
		$this->assertEqual(
			$DboMock->execute('SHOW STATUS'),
			$i
		);
	}

	public function testExecuteStuckLoop() {
		$DboMock = $this->getMock('MysqlExtended', [
			'connect',
			'describe',
			'executeOnParent',
		]);

		// parent wrapper for mocking
		$DboMock
			->expects($this->any())
			->method('connect')
			->will($this->returnValue($this->Dbo->connect()));
		$DboMock
			->expects($this->any())
			->method('describe')
			->will($this->returnValue($this->Dbo->describe($this->Article)));

		$DboMock
			->expects($this->at(0))
			->method('executeOnParent')
			->will($this->throwException($this->e));
		$DboMock
			->expects($this->at(1))
			->method('executeOnParent')
			->will($this->throwException($this->e));
		$DboMock
			->expects($this->at(2))
			->method('executeOnParent')
			->will($this->throwException($this->e));
		$DboMock
			->expects($this->at(3))
			->method('executeOnParent')
			->will($this->throwException($this->e));

		// mocked, always throws exception
		try {
			$DboMock->execute('SHOW STATUS');
			$this->assertEqual(
				'Expected Exception',
				'!!got none!!'
			);
		} catch (PDOException $e) {
			// mock gave us the right message
			$this->assertEqual(
				$e->getMessage(),
				'SQLSTATE[HY000]: General error: 1205 Lock wait timeout exceeded; try restarting transaction'
			);
			// mock gave us the "extra" info we need for the query...
			$this->assertEqual(
				$e->queryString,
				'DELETE `IcingVersion` WHERE `id` = 1'
			);
		}

		// After query1 gets stuck in a loop of retrying and throwing an exception,
		// Check to make sure query2 will still retry the appropriate amount of times
		// and not immediately throw an exception.
		// Exception -> Exception -> return $i,  This query should work.
		$i = rand(99, 99999);
		$DboMock
			->expects($this->at(0))
			->method('executeOnParent')
			->will($this->throwException($this->e));
		$DboMock
			->expects($this->at(1))
			->method('executeOnParent')
			->will($this->throwException($this->e));
		$DboMock
			->expects($this->at(2))
			->method('executeOnParent')
			->will($this->returnValue($i));
		try {
			$this->assertEqual(
				$DboMock->execute('SHOW STATUS'),
				$i
			);
		} catch (PDOException $e) {
			$this->assertEqual(
				'Expected this query to work',
				'!!got Exception!!'
			);
		}
	}


	public function testTryExecuteAgain() {
		// Mock it all up
		$this->DboMock = $this->getMock('MysqlExtended', [
			'connect',
			'shouldWeExecuteAgain',
			'tryExecuteAgainSleep',
			'tryExecuteAgainAddRepeat',
			'execute',
		]);

		$i = rand(99,99999);

		$this->DboMock
			->expects($this->once())
			->method('shouldWeExecuteAgain')
			->will($this->returnValue(true));
		$this->DboMock
			->expects($this->once())
			->method('tryExecuteAgainSleep')
			->will($this->returnValue(true));
		$this->DboMock
			->expects($this->once())
			->method('tryExecuteAgainAddRepeat')
			->will($this->returnValue(true));
		$this->DboMock
			->expects($this->once())
			->method('execute')
			->will($this->returnValue($i));

		$this->assertEqual(
			$this->DboMock->tryExecuteAgain($this->e, '', '', ''),
			$i
		);

		// Mock it all up
		$this->DboMock = $this->getMock('MysqlExtended', [
			'connect',
			'shouldWeExecuteAgain',
			'tryExecuteAgainSleep',
			'tryExecuteAgainAddRepeat',
			'execute',
		]);
		$this->DboMock
			->expects($this->once())
			->method('shouldWeExecuteAgain')
			->will($this->returnValue(false));

		try {
			$this->DboMock->tryExecuteAgain($this->e, '', '', '');
			$this->assertEqual(
				'Expected Exception',
				'!!got none!!'
			);
		} catch (PDOException $e) {
			// mock gave us the right message
			$this->assertEqual(
				$e->getMessage(),
				'SQLSTATE[HY000]: General error: 1205 Lock wait timeout exceeded; try restarting transaction'
			);
			// mock gave us the "extra" info we need for the query...
			$this->assertEqual(
				$e->queryString,
				'DELETE `IcingVersion` WHERE `id` = 1'
			);
		}

	}

	public function testShouldWeExecuteAgain() {
		$this->assertTrue(
			$this->Dbo->shouldWeExecuteAgain($this->e)
		);
		$this->assertFalse(
			$this->Dbo->shouldWeExecuteAgain(true)
		);
		$this->assertFalse(
			$this->Dbo->shouldWeExecuteAgain([])
		);
		$this->assertFalse(
			$this->Dbo->shouldWeExecuteAgain(new PDOException('junk and stuff'))
		);

		// should not repeat more than 3 times
		$this->assertTrue(
			$this->Dbo->shouldWeExecuteAgain($this->e)
		);
		$this->Dbo->tryExecuteAgainAddRepeat($this->e);
		$this->Dbo->tryExecuteAgainAddRepeat($this->e);
		$this->Dbo->tryExecuteAgainAddRepeat($this->e);
		$this->assertFalse(
			$this->Dbo->shouldWeExecuteAgain($this->e)
		);
	}

	public function testTryExecuteAgainAddRepeat() {
		if (isset($this->Dbo->_tryExecuteAgain[$this->e->queryString])) {
			unset($this->Dbo->_tryExecuteAgain[$this->e->queryString]);
		}
		$this->assertFalse(
			isset($this->Dbo->_tryExecuteAgain[$this->e->queryString])
		);

		$this->Dbo->tryExecuteAgainAddRepeat($this->e);

		$this->assertTrue(
			isset($this->Dbo->_tryExecuteAgain[$this->e->queryString])
		);
		$this->assertEqual(
			$this->Dbo->_tryExecuteAgain[$this->e->queryString],
			1
		);

		$this->Dbo->tryExecuteAgainAddRepeat($this->e);
		$this->Dbo->tryExecuteAgainAddRepeat($this->e);
		$this->Dbo->tryExecuteAgainAddRepeat($this->e);
		$this->Dbo->tryExecuteAgainAddRepeat($this->e);

		$this->assertEqual(
			$this->Dbo->_tryExecuteAgain[$this->e->queryString],
			5
		);
	}

	public function testTryExecuteAgainUnsetRepeat() {
		$this->Dbo->_tryExecuteAgain[$this->e->queryString] = 2;

		$this->Dbo->tryExecuteAgainUnsetRepeat();

		$this->assertFalse(
			isset($this->Dbo->_tryExecuteAgain[$this->e->queryString])
		);
		$this->assertFalse(
			isset($this->Dbo->_tryExecuteAgain)
		);
	}

	public function testTryExecuteAgainSleep() {
		$this->Dbo->_tryExecuteAgain[$this->e->queryString] = 2;

		// 1205 = 100 ms
		$start = microtime(true);
		$this->assertTrue(
			$this->Dbo->tryExecuteAgainSleep($this->e)
		);
		$stop = microtime(true);
		$delta = ($stop - $start);
		$this->assertTrue($delta > 0.01);
		$this->assertTrue($delta < 0.02);

		// else, 1 ms
		$this->e = new PDOException('blah blah');
		$start = microtime(true);
		$this->assertTrue(
			$this->Dbo->tryExecuteAgainSleep($this->e)
		);
		$stop = microtime(true);
		$delta = ($stop - $start);
		$this->assertTrue($delta > 0.001);
		$this->assertTrue($delta < 0.002);
	}


}

