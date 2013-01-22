<?php
/**
 * StatFixture
 * This is a mockup fixture for use with test cases (behaviors)
 *
 */
class StatFixture extends CakeTestFixture {

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 36, 'key' => 'primary', 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'string' => array('type' => 'string', 'null' => false, 'default' => null, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'integer' => array('type' => 'integer', 'length' => 10, 'null' => false, 'default' => null),
		'float' => array('type' => 'float', 'length' => '6,2', 'null' => false, 'default' => null),
		'boolean' => array('type' => 'boolean', 'null' => false, 'default' => null),
		'indexes' => array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
		),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'MyISAM')
	);

/**
 * Records
 *
 * @var array
 */
	public $records = array(
		array(
			'id' => '50539578-c724-4cdf-b602-4288e017215a',
			'created' => '2001-01-01 00:00:01',
			'string' => 'record 1',
			'integer' => 1,
			'float' => 0.1,
			'boolean' => 1,
		),
		array(
			'id' => '50539578-c724-4cdf-b602-4288e017215b',
			'created' => '2001-01-01 00:00:02',
			'string' => 'record 2',
			'integer' => 2,
			'float' => 2.2,
			'boolean' => 0,
		),
		array(
			'id' => '50539578-c724-4cdf-b602-4288e017215c',
			'created' => '2001-01-01 00:00:03',
			'string' => 'record 3',
			'integer' => 3,
			'float' => 0.3,
			'boolean' => 0,
		),
		array(
			'id' => '50539578-c724-4cdf-b602-4288e017215d',
			'created' => '2001-01-01 00:00:04',
			'string' => 'record 4',
			'integer' => 4,
			'float' => 4,
			'boolean' => 1,
		),
		array(
			'id' => '50539578-c724-4cdf-b602-4288e017215e',
			'created' => '2001-01-01 00:00:05',
			'string' => 'record 5',
			'integer' => 5,
			'float' => 0.5,
			'boolean' => 0,
		),
	);

}
