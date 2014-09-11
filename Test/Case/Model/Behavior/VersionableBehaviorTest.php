<?php
/**
 * VersionableBehavior
 *
 */
App::uses('Model', 'Model');
App::uses('ModelBehavior', 'Model');
App::uses('IcingVersion', 'Icing.Model');
App::uses('VersionableBehavior', 'Icing.Model/Behavior');

/**
 * Post model (fixtures from CakePHP Core)
 */
class Post extends CakeTestModel {
	public $actsAs = array('Icing.Versionable');
}

/**
 * User model (fixtures from CakePHP Core)
 */
class User extends CakeTestModel {
	public $actsAs = array('Icing.Versionable');
	public $hasMany = array(
		'Post' => array(
			'foreignKey' => 'author_id',
		)
	);
	public $recursive = -1;
}

/**
 * User model (fixtures from CakePHP Core)
 * Not initialized so we tweak config before initialize of the Behavior
 */
class UserDefault1 extends CakeTestModel {
	public $actsAs = array('Icing.Versionable');
}

/**
 * User model (fixtures from CakePHP Core)
 * settings changed to simulate Versionable config setup
 */
class UserCustomized extends CakeTestModel {
	public $useTabel = false;
	public $actsAs = array('Icing.Versionable' => array(
		'contain'          => array('Post'),
		'versions'         => 2,
		'minor_timeframe'  => 2,
		'bind'             => true,
		'check_identical'  => true,
		'ignore_identical' => true,
		'useDbConfig' => 'test_archive',
	));
}

/**
 * VersionableBehavior Test Case
 *
 */
class VersionableBehaviorTest extends CakeTestCase {

	/**
	 * Fixtures
	 *
	 * @var array
	 */
	public $fixtures = array(
		'core.post',
		'core.user',
		'plugin.icing.icing_version',
	);

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->Versionable = new VersionableBehavior();
		$this->Post = ClassRegistry::init('Post');
		$this->User = ClassRegistry::init('User');
		$this->IcingVersion = ClassRegistry::init('IcingVersion');
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown() {
		unset($this->Versionable);
		unset($this->Post);
		unset($this->User);
		unset($this->IcingVersion);
		parent::tearDown();
	}

	/**
	 * test findSum or really find('sum', ...)
	 * a test of the customFind method setup in the Behavior
	 */
	public function testBasic() {
		$this->IcingVersion->deleteAll('1=1');
		$this->assertEqual(
			$this->IcingVersion->find('count'),
			0
		);
		$data = $this->User->find('first');
		$orig = $data;

		// change something
		$data['User']['user'] = 'changed';

		// save
		$saved = $this->User->save($data);
		$this->assertEqual(
			$this->IcingVersion->find('count'),
			1
		);
		$this->User->bindIcingVersion();
		$after = $this->User->find('first', array('contain' => array('IcingVersion')));
		$this->assertTrue(
			array_key_exists('IcingVersion', $after)
		);
		$this->assertEqual(
			$after['IcingVersion'][0]['model_id'],
			$after['User']['id']
		);
		$this->assertEqual(
			$after['IcingVersion'][0]['model'],
			'User'
		);
		$this->assertEqual(
			$after['IcingVersion'][0]['json'],
			json_encode($orig)
		);
		$this->assertFalse($after['IcingVersion'][0]['is_delete']);
		$this->assertFalse($after['IcingVersion'][0]['is_minor_version']);

		// get data before save
		$this->assertEqual(
			$orig,
			$this->User->getDataBeforeSave()
		);

		// delete
		$deleted = $this->User->delete($data['User']['id']);
		$this->assertTrue($deleted);
		$this->assertEqual(
			$this->IcingVersion->find('count', array(
				'conditions' => array(
					'model' => 'User',
					'model_id' => $data['User']['id']
				)
			)),
			2
		);
		$this->assertEqual(
			$this->IcingVersion->find('count', array(
				'conditions' => array(
					'model' => 'User',
					'model_id' => $data['User']['id'],
					'is_delete' => true
				)
			)),
			1
		);

	}

	public function testSettingsFromConfigure() {
		$orig = Configure::read('Icing.Versionable.config');

		Configure::write('Icing.Versionable.config', array(
			'contain'          => array('Foobar'),
			'versions'         => 9,
			'minor_timeframe'  => 9,
			'bind'             => true,
			'check_identical'  => true,
			'ignore_identical' => true,
			'extrasetting'     => __function__
		));

		$this->UserDefault1 = ClassRegistry::init('UserDefault1');

		$this->assertEqual(
			$this->UserDefault1->getIcingVersionSettings(),
			array(
				'contain'          => array('Foobar'),
				'versions'         => 9,
				'minor_timeframe'  => 9,
				'bind'             => true,
				'check_identical'  => true,
				'ignore_identical' => true,
				'useDbConfig'  => null,
				'extrasetting' => __function__
			)
		);

		unset($this->UserDefault1);

		Configure::write('Icing.Versionable.config', $orig);
	}

	public function testSettingsUseDbConfig() {
		$this->UserCustomized = ClassRegistry::init('UserCustomized');

		$this->assertEqual(
			$this->UserCustomized->wouldHaveSetIcingVersion,
			'test_archive'
		);

		// makes it to the settings
		$settings = $this->UserCustomized->getIcingVersionSettings();
		$this->assertEqual(
			$settings['useDbConfig'],
			'test_archive'
		);

		// doesn't overwrite "test" since we are testing
		$this->assertEqual(
			$this->UserCustomized->getIcingVersion()->useDbConfig,
			'test'
		);

		/*
			Testing anything that changes the DB to something else is tricky
			since we want the actual tests to remain on the test DB and not "mess up" any other DB
			if you want to "actually" run some tests across both DBs you can do something like this

		$configs = ConnectionManager::enumConnectionObjects();
		$configs = array_keys($configs);
		if (!in_array('test_archive', $configs)) {
			debug("Can not test useDbConfig... \ncreate a database config called 'test_archive' to run this test\nedit app/Config/database.php");
			return;
		}

		// should initalize on the DB test_archive
		$db = ConnectionManager::getDataSource('test_archive');
		$Fixture = new IcingVersionFixture();
		$Fixture->init($db);
		$Fixture->drop($db);
		$Fixture->create($db);
		$this->UserCustomized = ClassRegistry::init('UserCustomized');
		$this->UserCustomized->save(['user' => __function__]);
		 */
	}

	public function testSaveContain() {
		// default versions = false
		$settings = $this->User->getIcingVersionSettings();
		$this->assertEqual(
			$settings['contain'],
			array()
		);

		// change contain = array(Post)
		$this->User->Behaviors->load('Icing.Versionable', array(
			'contain' => array('Post'),
		));
		$settings = $this->User->getIcingVersionSettings();
		$this->assertEqual(
			$settings['contain'],
			array('Post')
		);

		$this->IcingVersion->deleteAll('1=1');
		$data = $this->User->find('first');
		$saved = $this->User->save($data);
		$version = $this->IcingVersion->find('first', array(
			'conditions' => array(
				'model' => 'User',
				'model_id' => $data['User']['id'],
			)
		));
		$versionData = json_decode($version['IcingVersion']['json'], true);
		$this->assertEqual(
			$versionData['User'],
			$data['User']
		);
		// saved data doesn't have Post
		$this->assertFalse(
			array_key_exists('Post', $data)
		);
		// versionData does have Post as a full contain call
		$this->assertTrue(
			array_key_exists('Post', $versionData)
		);
		$this->assertEqual(
			count($versionData['Post']),
			2
		);
	}

	public function testSaveVersionsLimit() {
		// default versions = false
		$settings = $this->User->getIcingVersionSettings();
		$this->assertFalse($settings['versions']);

		// save any number of versions
		$this->IcingVersion->deleteAll('1=1');
		$data = $this->User->find('first');
		for ($i = 0; $i < 20; $i++) {
			$saved = $this->User->save($data);
		}
		$this->assertEqual(
			$this->IcingVersion->find('count'),
			20
		);

		// change versions = 2
		$this->User->Behaviors->load('Icing.Versionable', array(
			'versions' => 2,
		));
		$settings = $this->User->getIcingVersionSettings();
		$this->assertEqual(
			$settings['versions'],
			2
		);

		$this->IcingVersion->deleteAll('1=1');
		$data = $this->User->find('first');
		for ($i = 0; $i < 10; $i++) {
			$saved = $this->User->save($data);
		}
		$this->assertEqual(
			$this->IcingVersion->find('count'),
			2
		);
	}

	public function testSaveVersionCheckIdentical() {
		// default check_identical = false
		$settings = $this->User->getIcingVersionSettings();
		$this->assertFalse($settings['check_identical']);

		$this->IcingVersion->deleteAll('1=1');
		$data = $this->User->find('first');
		$saved = $this->User->save($data);
		$saved = $this->User->save($data);
		$saved = $this->User->save($data);
		$saved = $this->User->save($data);
		$this->assertEqual(
			$this->IcingVersion->find('count'),
			4
		);
		$this->assertEqual(
			$this->IcingVersion->find('count', array(
				'conditions' => array('is_minor_version' => true)
			)),
			0
		);

		// change check_identical = true
		$this->User->Behaviors->load('Icing.Versionable', array(
			'check_identical' => true,
		));
		$settings = $this->User->getIcingVersionSettings();
		$this->assertTrue($settings['check_identical']);

		$this->IcingVersion->deleteAll('1=1');
		$data = $this->User->find('first');
		$saved = $this->User->save($data);
		$saved = $this->User->save($data);
		$saved = $this->User->save($data);
		$saved = $this->User->save($data);
		$this->assertEqual(
			$this->IcingVersion->find('count'),
			4
		);
		$this->assertEqual(
			$this->IcingVersion->find('count', array(
				'conditions' => array('is_minor_version' => true)
			)),
			3
		);
	}

	public function testSaveVersionIgnoreIdentical() {
		// default ignore_identical = false
		$settings = $this->User->getIcingVersionSettings();
		$this->assertFalse($settings['ignore_identical']);

		$this->IcingVersion->deleteAll('1=1');
		$data = $this->User->find('first');
		$saved = $this->User->save($data);
		$saved = $this->User->save($data);
		$saved = $this->User->save($data);
		$saved = $this->User->save($data);
		$this->assertEqual(
			$this->IcingVersion->find('count'),
			4
		);

		// change ignore_identical = true
		//   (auto-sets check_identical == true)
		$this->User->Behaviors->load('Icing.Versionable', array(
			'ignore_identical' => true,
		));
		$settings = $this->User->getIcingVersionSettings();
		$this->assertTrue($settings['ignore_identical']);

		$this->IcingVersion->deleteAll('1=1');
		$data = $this->User->find('first');
		$saved = $this->User->save($data);
		$saved = $this->User->save($data);
		$saved = $this->User->save($data);
		$saved = $this->User->save($data);
		$this->assertEqual(
			$this->IcingVersion->find('count'),
			1
		);
	}

	public function testRestoreVersionByVersionInt() {
		$data = $this->User->find('first');
		$orig = $data;

		// change something
		$data['User']['user'] = 'changed';

		// save
		$saved = $this->User->save($data);

		// confirm saved
		$data = $this->User->find('first');
		$this->assertEqual(
			$data['User']['user'],
			'changed'
		);

		// do restore
		$this->assertTrue(
			$this->User->restoreVersion(1, $data['User']['id'])
		);

		// confirm restored
		$this->assertEqual(
			$this->User->find('first'),
			$orig
		);
	}

	public function testRestoreVersionByVersionId() {
		$data = $this->User->find('first');
		$orig = $data;

		// change something
		$data['User']['user'] = 'changed';

		// save
		$saved = $this->User->save($data);

		// confirm saved
		$data = $this->User->find('first');
		$this->assertEqual(
			$data['User']['user'],
			'changed'
		);

		// do restore
		$version = $this->IcingVersion->find('first', array(
			'conditions' => array(
				'model' => 'User',
				'model_id' => $data['User']['id'],
			)
		));
		$this->assertTrue(
			$this->User->restoreVersion($version['IcingVersion']['id'])
		);

		// confirm restored
		$this->assertEqual(
			$this->User->find('first'),
			$orig
		);
	}

	public function testRestoreVersionImpossible() {
		// bad version
		$version = $this->IcingVersion->find('first');
		$version['IcingVersion']['model'] = 'BadModelNameHere';
		$this->IcingVersion->save($version);
		$this->assertFalse(
			$this->User->restoreVersion($version['IcingVersion']['id'])
		);
	}

}
