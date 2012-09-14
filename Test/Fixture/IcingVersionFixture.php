<?php
/**
 * IcingVersionFixture
 *
 */
class IcingVersionFixture extends CakeTestFixture {

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 36, 'key' => 'primary', 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'model_id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 50, 'key' => 'index', 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'user_id' => array('type' => 'integer', 'null' => true, 'default' => null),
		'model' => array('type' => 'string', 'null' => false, 'default' => null, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'json' => array('type' => 'text', 'null' => false, 'default' => null, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'url' => array('type' => 'string', 'null' => true, 'default' => null, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'ip' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 50, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'is_delete' => array('type' => 'boolean', 'null' => false, 'default' => null),
		'is_minor_version' => array('type' => 'boolean', 'null' => false, 'default' => null, 'comment' => 'true if saved within x timeframe of previous version'),
		'indexes' => array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'model_id' => array('column' => array('model_id', 'model'), 'unique' => 0)
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
			'model_id' => 'Lorem ipsum dolor sit amet',
			'user_id' => 1,
			'model' => 'Lorem ipsum dolor sit amet',
			'json' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'created' => '2012-09-14 14:37:12',
			'url' => 'Lorem ipsum dolor sit amet',
			'ip' => 'Lorem ipsum dolor sit amet',
			'is_delete' => 1,
			'is_minor_version' => 1
		),
		array(
			'id' => '50539578-e2e0-40f3-9f74-4288e017215a',
			'model_id' => 'Lorem ipsum dolor sit amet',
			'user_id' => 2,
			'model' => 'Lorem ipsum dolor sit amet',
			'json' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'created' => '2012-09-14 14:37:12',
			'url' => 'Lorem ipsum dolor sit amet',
			'ip' => 'Lorem ipsum dolor sit amet',
			'is_delete' => 1,
			'is_minor_version' => 1
		),
		array(
			'id' => '50539578-f280-44b5-ba25-4288e017215a',
			'model_id' => 'Lorem ipsum dolor sit amet',
			'user_id' => 3,
			'model' => 'Lorem ipsum dolor sit amet',
			'json' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'created' => '2012-09-14 14:37:12',
			'url' => 'Lorem ipsum dolor sit amet',
			'ip' => 'Lorem ipsum dolor sit amet',
			'is_delete' => 1,
			'is_minor_version' => 1
		),
		array(
			'id' => '50539578-0158-4846-89f3-4288e017215a',
			'model_id' => 'Lorem ipsum dolor sit amet',
			'user_id' => 4,
			'model' => 'Lorem ipsum dolor sit amet',
			'json' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'created' => '2012-09-14 14:37:12',
			'url' => 'Lorem ipsum dolor sit amet',
			'ip' => 'Lorem ipsum dolor sit amet',
			'is_delete' => 1,
			'is_minor_version' => 1
		),
		array(
			'id' => '50539578-10f8-40cb-9a33-4288e017215a',
			'model_id' => 'Lorem ipsum dolor sit amet',
			'user_id' => 5,
			'model' => 'Lorem ipsum dolor sit amet',
			'json' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'created' => '2012-09-14 14:37:12',
			'url' => 'Lorem ipsum dolor sit amet',
			'ip' => 'Lorem ipsum dolor sit amet',
			'is_delete' => 1,
			'is_minor_version' => 1
		),
		array(
			'id' => '50539578-1f08-47a7-a71f-4288e017215a',
			'model_id' => 'Lorem ipsum dolor sit amet',
			'user_id' => 6,
			'model' => 'Lorem ipsum dolor sit amet',
			'json' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'created' => '2012-09-14 14:37:12',
			'url' => 'Lorem ipsum dolor sit amet',
			'ip' => 'Lorem ipsum dolor sit amet',
			'is_delete' => 1,
			'is_minor_version' => 1
		),
		array(
			'id' => '50539578-2d18-42c8-b2c5-4288e017215a',
			'model_id' => 'Lorem ipsum dolor sit amet',
			'user_id' => 7,
			'model' => 'Lorem ipsum dolor sit amet',
			'json' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'created' => '2012-09-14 14:37:12',
			'url' => 'Lorem ipsum dolor sit amet',
			'ip' => 'Lorem ipsum dolor sit amet',
			'is_delete' => 1,
			'is_minor_version' => 1
		),
		array(
			'id' => '50539578-3c54-4210-b5c2-4288e017215a',
			'model_id' => 'Lorem ipsum dolor sit amet',
			'user_id' => 8,
			'model' => 'Lorem ipsum dolor sit amet',
			'json' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'created' => '2012-09-14 14:37:12',
			'url' => 'Lorem ipsum dolor sit amet',
			'ip' => 'Lorem ipsum dolor sit amet',
			'is_delete' => 1,
			'is_minor_version' => 1
		),
		array(
			'id' => '50539578-499c-4cea-aed1-4288e017215a',
			'model_id' => 'Lorem ipsum dolor sit amet',
			'user_id' => 9,
			'model' => 'Lorem ipsum dolor sit amet',
			'json' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'created' => '2012-09-14 14:37:12',
			'url' => 'Lorem ipsum dolor sit amet',
			'ip' => 'Lorem ipsum dolor sit amet',
			'is_delete' => 1,
			'is_minor_version' => 1
		),
		array(
			'id' => '50539578-5874-44f3-9abe-4288e017215a',
			'model_id' => 'Lorem ipsum dolor sit amet',
			'user_id' => 10,
			'model' => 'Lorem ipsum dolor sit amet',
			'json' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'created' => '2012-09-14 14:37:12',
			'url' => 'Lorem ipsum dolor sit amet',
			'ip' => 'Lorem ipsum dolor sit amet',
			'is_delete' => 1,
			'is_minor_version' => 1
		),
	);

}
