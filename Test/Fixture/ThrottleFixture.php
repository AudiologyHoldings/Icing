<?php
/**
 * ThrottleFixture
 */
class ThrottleFixture extends CakeTestFixture {

	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 36, 'key' => 'primary', 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'key' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 512, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'expire_epoch' => array('type' => 'integer', 'null' => false, 'default' => '0', 'length' => 12),
		'indexes' => array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'type' => array('column' => array('key', 'expire_epoch'), 'unique' => 0),
			'type' => array('column' => array('expire_epoch'), 'unique' => 0),
		),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'MEMORY')
	);

	public $records = array(
	);

}
