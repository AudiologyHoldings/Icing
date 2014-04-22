<?php
/**
 * DatabaseCacheFixture
 *
 */
class DatabaseCacheFixture extends CakeTestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = array(
		'key' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 128, 'key' => 'primary', 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'value' => array('type' => 'text', 'null' => false, 'default' => null, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'duration' => array('type' => 'integer', 'null' => false, 'default' => null),
		'indexes' => array(
			'PRIMARY' => array('column' => 'key', 'unique' => 1)
		),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci')
	);

	/**
	 * Records
	 *
	 * @var array
	 */
	public $records = array(
		array(
			'key' => 'mykey1',
			'value' => 'here is my value',
			'duration' => 99999999999999
		),
		array(
			'key' => 'mykey2',
			'value' => '[1,2,3,4]',
			'duration' => 99999999999999
		),
		array(
			'key' => 'mykey3',
			'value' => '{"a":"b"}',
			'duration' => 99999999999999
		),
	);

}
