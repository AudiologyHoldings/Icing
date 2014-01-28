<?php
App::uses('ElasticSearchRequest', 'Icing.Lib');

/**
 * ElasticSearchRequest Test Case
 *
 * NOTE: you MUST have an ElasticSearch server to run these
 *   they are integration tests
 *
 * NOTE: you can config in app/Config/elastic_search_request.php 'test' array
 */
Configure::write('inUnitTest', true);
class ElasticSearchRequestTest extends CakeTestCase {

	public $fixtures = array();

	public $index = 'elastic_search_request_test';
	public $table = 'test_table';
	public $mapping = array(
		"test_table" => array(
			"properties" => array(
				"model" => array(
					"type" => "string",
					"store" => "yes",
				),
				"association_key" => array(
					"type" => "string",
					"store" => "yes",
				),
				"data" => array(
					"type" => "string",
					"store" => "yes",
				)
			)
		)
	);
	public $records = array(
		array(
			"model" => "TestTable",
			"association_key" => "1234",
			"data" => "here is my data field. it can be pretty long too",
		),
		array(
			"model" => "TestUser",
			"association_key" => "9999",
			"data" => "  abcde alan blount is here he thinks ",
		),
		array(
			"model" => "TestOther",
			"association_key" => "4",
			"data" => "elasticsearch is pretty awesome, alan thinks so",
		),
		array(
			"model" => "TestTable",
			"association_key" => "0000000000000000000000",
			"data" => "what else?",
		),
	);

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp() {
		$this->ESR = new ElasticSearchRequest();
		parent::setUp();
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown() {
		unset($this->ESR);
		parent::tearDown();
	}

	/**
	 * setUp records - index, mapping, and records
	 */
	public function setUpIndex($setUpRecords = true) {
		$this->ESR->_config['index'] = $this->index;
		$this->ESR->createIndex($this->index);
		$request = array('table' => $this->table);
		$this->ESR->createMapping($this->mapping, $request);
		if (empty($setUpRecords)) {
			return;
		}
		foreach ($this->records as $i => $data) {
			$id = $this->ESR->createRecord($data, $request);
			$this->records[$i]['_id'] = $id;
		}
		sleep(2);
	}

	/**
	 * tearDown records - delete the index
	 */
	public function tearDownIndex() {
		$this->ESR->deleteIndex($this->index);
	}

	public function testConfig() {
		$this->assertFalse(empty($this->ESR->_config));
		$this->ESR->_config = array();
		$this->assertTrue(empty($this->ESR->_config));
		$this->ESR->config();
		$this->assertFalse(empty($this->ESR->_config));
		$this->assertTrue($this->ESR->_config['verified']);
		$this->ESR->_config = array();
		$this->assertTrue(empty($this->ESR->_config));
		$this->ESR->config(array('uri' => array('host' => '1234')));
		$this->assertFalse(empty($this->ESR->_config));
		$this->assertEqual($this->ESR->_config['uri']['host'], '1234');
	}
	public function testVerifyConfig() {
		try {
			$this->ESR->verifyConfig(array());
			$this->assertEqual('should', 'have thrown ElasticSearchRequestException');
		} catch (ElasticSearchRequestException $e) {
			$this->assertEqual('thrown', 'thrown');
		}
		$configBase = $this->ESR->_config;
		$config = $configBase;
		// base config works
		$this->ESR->verifyConfig($config);
		// missing or empty index
		unset($config['index']);
		try {
			$this->ESR->verifyConfig($config);
			$this->assertEqual('should', 'have thrown ElasticSearchRequestException');
		} catch (ElasticSearchRequestException $e) {
			$this->assertEqual('thrown', 'thrown');
		}
		// invalid char in index
		$config['index'] = 'aaa$';
		try {
			$this->ESR->verifyConfig(array('index' => ''));
			$this->assertEqual('should', 'have thrown ElasticSearchRequestException');
		} catch (ElasticSearchRequestException $e) {
			$this->assertEqual('thrown', 'thrown');
		}
		// missing URI components
		$config = $configBase;
		$config['uri']['scheme'] = '';
		try {
			$this->ESR->verifyConfig(array('index' => ''));
			$this->assertEqual('should', 'have thrown ElasticSearchRequestException');
		} catch (ElasticSearchRequestException $e) {
			$this->assertEqual('thrown', 'thrown');
		}
		$config = $configBase;
		$config['uri']['host'] = '';
		try {
			$this->ESR->verifyConfig(array('index' => ''));
			$this->assertEqual('should', 'have thrown ElasticSearchRequestException');
		} catch (ElasticSearchRequestException $e) {
			$this->assertEqual('thrown', 'thrown');
		}
		$config = $configBase;
		$config['uri']['port'] = '';
		try {
			$this->ESR->verifyConfig(array('index' => ''));
			$this->assertEqual('should', 'have thrown ElasticSearchRequestException');
		} catch (ElasticSearchRequestException $e) {
			$this->assertEqual('thrown', 'thrown');
		}
	}

	public function testParseQuery() {
		$query1 = array('query' => array('query_string' => array('query' => 'a')));
		$expect = $query1;
		$this->assertEqual($this->ESR->parseQuery($query1), $expect);
		$this->assertEqual($this->ESR->parseQuery(json_encode($query1)), $expect);
		$this->assertEqual($this->ESR->parseQuery($query1['query']), $expect);

	}
	public function testAutoQuery() {
		$this->assertEqual($this->ESR->autoQuery('a'), array('query' => array('query_string' => array('query' => 'a', 'lenient' => true))));
		$this->assertEqual($this->ESR->autoQuery('abc def'), array('query' => array('query_string' => array('query' => 'abc def', 'lenient' => true))));
		$this->assertEqual($this->ESR->autoQuery('"abc def"'), array('query' => array('query_string' => array('query' => '"abc def"', 'lenient' => true))));
		$this->assertEqual($this->ESR->autoQuery('~abc'), array('query' => array('fuzzy_like_this' => array('like_text' => 'abc'))));
	}


	public function testMapping() {
		$this->ESR->deleteIndex($this->index);
		sleep(3);
		$this->ESR->createIndex($this->index);
		sleep(3);
		$request = array('table' => 'test_table');
		$this->assertTrue($this->ESR->createMapping($this->mapping, $request));
		$expect = $this->mapping;
		foreach (array_keys($expect['test_table']['properties']) as $field) {
			$expect['test_table']['properties'][$field]['store'] = true;
		}
		$this->assertEqual($this->ESR->getMapping($request), $expect);
	}

	public function testCreateRecord() {
		$this->setUpIndex(false);
		$data = array(
			"model" => "TestCreateRecord",
			"association_key" => time(),
			"data" => "Example data",
		);
		$request = array('table' => 'test_table');
		$id = $this->ESR->createRecord($data, $request);
		$this->assertFalse(empty($id));
		$data = $this->ESR->getRecord($id, $request);
		$expect = $data;
		$expect['_id'] = $id;
		$this->assertEqual($this->ESR->getRecord($id, $request), $expect);
		$this->tearDownIndex();
	}

	public function testUpdateRecord() {
		$this->setUpIndex(true);
		$id = $this->records[0]['_id'];
		// setup
		$data = array(
			"model" => "TestUpdatedRecord",
			"association_key" => time(),
			"data" => "Example data",
		);
		$request = array('table' => 'test_table');
		$id = $this->ESR->updateRecord($id, $data, $request);
		$this->assertFalse(empty($id));
		$data = $this->ESR->getRecord($id, $request);
		$expect = $data;
		$expect['_id'] = $id;
		$this->assertEqual($this->ESR->getRecord($id, $request), $expect);
		$this->tearDownIndex();
	}

	public function testGetRecord() {
		$this->setUpIndex(true);
		$request = array('table' => 'test_table');
		$id = $this->records[0]['_id'];
		$data = $this->ESR->getRecord($id, $request);
		$expect = $this->records[0];
		$this->assertEqual($this->ESR->getRecord($id, $request), $expect);
		$this->tearDownIndex();
	}

	public function testDeleteRecord() {
		$this->setUpIndex(true);
		$id = $this->records[0]['_id'];
		$request = array('table' => 'test_table');
		$this->assertTrue($this->ESR->deleteRecord($id, $request));
		$this->assertFalse($this->ESR->exists($id, $request));
		$this->tearDownIndex();
	}

	public function testExists() {
		$this->setUpIndex(true);
		$id = $this->records[0]['_id'];
		$request = array('table' => 'test_table');
		$this->assertTrue($this->ESR->exists($id, $request));
		$this->assertFalse($this->ESR->exists('doesntexist', $request));
		$this->tearDownIndex();
	}

	public function testSearch() {
		$this->setUpIndex(true);
		sleep(2);
		// search all
		$data = $this->ESR->search();
		$this->assertFalse(empty($data));
		$models = array_unique(Hash::extract($data, '{n}.model'));
		sort($models);
		$expect = array('TestOther', 'TestTable', 'TestUser');
		$this->assertEqual($models, $expect);

		// search by one field=value
		$data = $this->ESR->search('model:TestTable');
		$models = array_unique(Hash::extract($data, '{n}.model'));
		$expect = array('TestTable');
		$this->assertEqual($models, $expect);
		$datas = array_unique(Hash::extract($data, '{n}.data'));
		sort($datas);
		$expect = array(
			"here is my data field. it can be pretty long too",
			"what else?",
		);
		$this->assertEqual($datas, $expect);

		// search by one field=value & one search term
		$data = $this->ESR->search('model:TestTable AND what');
		$models = array_unique(Hash::extract($data, '{n}.model'));
		$expect = array('TestTable');
		$this->assertEqual($models, $expect);
		$datas = array_unique(Hash::extract($data, '{n}.data'));
		$expect = array('what else?');
		$this->assertEqual($datas, $expect);

		// search only by one search term
		$data = $this->ESR->search('alan');
		$datas = array_unique(Hash::extract($data, '{n}.data'));
		sort($datas);
		$expect = array(
			"  abcde alan blount is here he thinks ",
			"elasticsearch is pretty awesome, alan thinks so",
		);
		$this->assertEqual($datas, $expect);

		// search by two terms
		$data = $this->ESR->search('alan AND thinks');
		$datas = array_unique(Hash::extract($data, '{n}.data'));
		sort($datas);
		// ^ last expects still works
		$this->assertEqual($datas, $expect);
		$data = $this->ESR->search('alan AND here');
		$datas = array_unique(Hash::extract($data, '{n}.data'));
		$expect = array("  abcde alan blount is here he thinks ");
		$this->assertEqual($datas, $expect);

		// search by phrase
		$data = $this->ESR->search('"alan thinks"');
		$datas = array_unique(Hash::extract($data, '{n}.data'));
		$expect = array("elasticsearch is pretty awesome, alan thinks so");
		$this->assertEqual($datas, $expect);

		// search by term with wildcard
		$data = $this->ESR->search('awes*');
		$datas = array_unique(Hash::extract($data, '{n}.data'));
		$expect = array("elasticsearch is pretty awesome, alan thinks so");
		$this->assertEqual($datas, $expect);

		// search by fuzzy
		//   "think" doesn't match anything
		$data = $this->ESR->search('think');
		$this->assertTrue(empty($data));
		//   "thinks" does though, and fuzzy finds that
		$data = $this->ESR->search('~think');
		$datas = array_unique(Hash::extract($data, '{n}.data'));
		sort($datas);
		$expect = array(
			"  abcde alan blount is here he thinks ",
			"elasticsearch is pretty awesome, alan thinks so",
		);
		$this->assertEqual($datas, $expect);

		// search by open phrase (controls response order)
		$data = $this->ESR->search('he thinks');
		$datas = array_unique(Hash::extract($data, '{n}.data'));
		$datas = array_values($datas);
		$expect = array(
			"  abcde alan blount is here he thinks ",
			"elasticsearch is pretty awesome, alan thinks so",
		);
		$this->assertEqual($datas, $expect);
		$data = $this->ESR->search('thinks so');
		$datas = array_unique(Hash::extract($data, '{n}.data'));
		$datas = array_values($datas);
		$expect = array(
			"elasticsearch is pretty awesome, alan thinks so",
			"  abcde alan blount is here he thinks ",
		);
		$this->assertEqual($datas, $expect);

		// -- END
		$this->tearDownIndex();
	}
	/* -- */


}
