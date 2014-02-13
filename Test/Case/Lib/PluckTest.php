<?php
App::uses('Pluck', 'Icing.Lib');

/**
 * Pluck Library Test Case
 *
 */
class PluckTest extends CakeTestCase {
	public $fixtures = array();
	// mock mixed data, nested and non-nested, alpha and numeric keys
	public $inputMixed = array(
		'a' => 'A',
		'b' => 'B',
		0 => 'zero',
		1 => 'one',
		'null' => null,
		'true' => true,
		'false' => false,
		'zero' => 0,
		'nest' => array(
			'c' => 'C',
			'd' => 'D',
			2 => 'two',
			3 => 'three',
			'nest' => array(
				'e' => 'E',
				'd' => 'D again',
				0 => 'zero again',
				5 => 'five',
			)
		),
	);
	// mock user data, single and multiple record, inconsistancies
	public $inputUser = array(
		'User' => array(
			'id' => 123,
			'name' => 'john doe',
			'email' => 'user@example.com',
		),
		'Post' => array(
			array(
				'id' => 1,
				'name' => 'my first post',
				'body' => 'blah blah',
				'Author' => array(
					'id' => null,
					'name' => null,
					'email' => null,
				),
				'Comment' => array(
					array(
						'id' => '0000-0000-0000-0000-0001',
						'text' => 'my first comment',
					)
				),
			),
			array(
				'id' => 2,
				'name' => 'my second post',
				'body' => 'blah blah',
				'Author' => array(
					'id' => 9999,
					'name' => 'Cool Guy',
					'email' => null,
				),
				'Comment' => array(
					array(
						'id' => '0000-0000-0000-0000-0002',
						'text' => 'my second comment',
					),
					array(
						'id' => '0000-0000-0000-0000-0003',
						'text' => 'my third comment',
					)
				),
			),
			array(
				'id' => 3,
				'name' => 'my third post',
				'body' => 'blah blah',
				'Author' => array(
					'id' => 8888,
					'name' => 'Email Haver',
					'email' => 'emailhaver@example.com',
				),
			),
		),
	);

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

	public function testAllNonArray() {
		foreach (array(0, 1, 2, true, false, null, 'a', $this) as $input) {
			$this->assertEquals(Pluck::all($input), $input);
		}
	}

	public function testAllNoPath() {
		$data = $this->inputMixed;
		$this->assertEquals(Pluck::all($data), array());
		$this->assertEquals(Pluck::all($data, null), array());
		$this->assertEquals(Pluck::all($data, false), array());
		// bad paths too
		$this->assertEquals(Pluck::all($data, 'bad.path'), array());
		$this->assertEquals(Pluck::all($data, '{n}.bad.path'), array());
	}

	public function testAllSimple() {
		$data = $this->inputMixed;
		$this->assertEquals(Pluck::all($data, 'a'), array('A'));
		$this->assertEquals(Pluck::all($data, '1'), array('one'));
		// NOTE: fails --> debug(Hash::extract($data, '0'))
		// $this->assertEquals(Pluck::all($data, '0'), array('zero'));
		$this->assertEquals(Pluck::all($data, 'nest.nest.0'), array('zero again'));
		// path ends at an array
		//   you might think it should be a "nested/wrapped" array
		//   but since all() merges... it's not :/
		$this->assertEquals(Pluck::all($data, 'nest'), $data['nest']);
		$this->assertEquals(Pluck::all($data, 'nest.nest'), $data['nest']['nest']);
	}

	public function testAllUser() {
		$data = $this->inputUser;
		// single path results
		$this->assertEquals(Pluck::all($data, 'User.id'), array(123));
		$this->assertEquals(Pluck::all($data, 'User.name'), array('john doe'));
		// single path multi-results
		$this->assertEquals(Pluck::all($data, 'Post.{n}.id'), array(1, 2, 3));
		$this->assertEquals(Pluck::all($data, 'Post.{n}.Author.id'), array(9999, 8888));
		$this->assertEquals(Pluck::all($data, 'Post.{n}.Comment.{n}.text'), array(
			'my first comment',
			'my second comment',
			'my third comment',
		));
		// multiple paths, single-results (merged)
		$paths = array('User.id', 'User.name', 'User.missing_field', 'Bad.path');
		$expect = array(123, 'john doe');
		$this->assertEquals(Pluck::all($data, $paths), $expect);
		// multiple paths, multiple-results (merged)
		$paths = array('Post.{n}.id', 'Post.{n}.Author.id');
		$expect = array(1, 2, 3, 9999, 8888);
		$this->assertEquals(Pluck::all($data, $paths), $expect);
		// multiple paths, mixed-results (merged)
		$paths = array('User.id', 'Post.{n}.id', 'Post.{n}.Author.id');
		$expect = array(123, 1, 2, 3, 9999, 8888);
		$paths = array('Post.{n}.id', 'User.id', 'Post.{n}.Author.id');
		$expect = array(1, 2, 3, 123, 9999, 8888);
		$this->assertEquals(Pluck::all($data, $paths), $expect);
		// path ends at an array
		//   merge shouldn't break this
		$expect = array(
			array(
				'id' => 9999,
				'name' => 'Cool Guy'
			),
			array(
				'id' => 8888,
				'name' => 'Email Haver',
				'email' => 'emailhaver@example.com'
			)
		);
		$this->assertEquals(Pluck::all($data, 'Post.{n}.Author'), $expect);
		// path ends at an array (list of nodes)
		//   merge DOES break this, kinda
		$expect = array(
			array(
				array(
					'id' => '0000-0000-0000-0000-0001',
					'text' => 'my first comment'
				)
			),
			array(
				array(
					'id' => '0000-0000-0000-0000-0002',
					'text' => 'my second comment'
				),
				array(
					'id' => '0000-0000-0000-0000-0003',
					'text' => 'my third comment'
				)
			)
		);
		$this->assertEquals(Pluck::all($data, 'Post.{n}.Comment'), $expect);
		// path ends at an array (list of nodes, with extra iterator)
		//   merge NO LONGER break this, because we are now merging in all the actual nodes
		$expect = array(
			array(
				'id' => '0000-0000-0000-0000-0001',
				'text' => 'my first comment'
			),
			array(
				'id' => '0000-0000-0000-0000-0002',
				'text' => 'my second comment'
			),
			array(
				'id' => '0000-0000-0000-0000-0003',
				'text' => 'my third comment'
			),
		);
		$this->assertEquals(Pluck::all($data, 'Post.{n}.Comment.{n}'), $expect);
	}



	public function testFirstNonArray() {
		foreach (array(0, 1, 2, true, false, null, 'a', $this) as $input) {
			$this->assertEquals(Pluck::firstPath($input), $input);
		}
	}

	public function testFirstNoPath() {
		$data = $this->inputMixed;
		$this->assertEquals(Pluck::firstPath($data), array());
		$this->assertEquals(Pluck::firstPath($data, null), array());
		$this->assertEquals(Pluck::firstPath($data, false), array());
		// bad paths too
		$this->assertEquals(Pluck::firstPath($data, 'bad.path'), array());
		$this->assertEquals(Pluck::firstPath($data, '{n}.bad.path'), array());
	}

	public function testFirstSimple() {
		$data = $this->inputMixed;
		$this->assertEquals(Pluck::firstPath($data, 'a'), array('A'));
		$this->assertEquals(Pluck::firstPath($data, '1'), array('one'));
		// NOTE: fails --> debug(Hash::extract($data, '0'))
		// $this->assertEquals(Pluck::firstPath($data, '0'), array('zero'));
		$this->assertEquals(Pluck::firstPath($data, 'nest.nest.0'), array('zero again'));
		// path ends at an array
		//   you might think it should be a "nested/wrapped" array
		//   but since firstPath() merges... it's not :/
		$this->assertEquals(Pluck::firstPath($data, 'nest'), $data['nest']);
		$this->assertEquals(Pluck::firstPath($data, 'nest.nest'), $data['nest']['nest']);
	}

	public function testFirstUser() {
		$data = $this->inputUser;
		// single path results
		$this->assertEquals(Pluck::firstPath($data, 'User.id'), array(123));
		$this->assertEquals(Pluck::firstPath($data, 'User.name'), array('john doe'));
		// single path multi-results
		$this->assertEquals(Pluck::firstPath($data, 'Post.{n}.id'), array(1, 2, 3));
		$this->assertEquals(Pluck::firstPath($data, 'Post.{n}.Author.id'), array(9999, 8888));
		$this->assertEquals(Pluck::firstPath($data, 'Post.{n}.Comment.{n}.text'), array(
			'my first comment',
			'my second comment',
			'my third comment',
		));
		// --------------------
		// (this is where first differs, it only returns the first good path)
		// --------------------
		// multiple paths, single-results
		$paths = array('User.id', 'User.name', 'User.missing_field', 'Bad.path');
		$expect = array(123);
		$this->assertEquals(Pluck::firstPath($data, $paths), $expect);
		// multiple paths, multiple-results (merged)
		$paths = array('Post.{n}.id', 'Post.{n}.Author.id');
		$expect = array(1, 2, 3);
		$this->assertEquals(Pluck::firstPath($data, $paths), $expect);
		// multiple paths, mixed-results (merged)
		$paths = array('User.id', 'Post.{n}.id', 'Post.{n}.Author.id');
		$expect = array(123);
		$paths = array('Post.{n}.id', 'User.id', 'Post.{n}.Author.id');
		$expect = array(1, 2, 3);
		$this->assertEquals(Pluck::firstPath($data, $paths), $expect);
		// path ends at an array
		//   merge shouldn't break this
		$expect = array(
			array(
				'id' => 9999,
				'name' => 'Cool Guy'
			),
			array(
				'id' => 8888,
				'name' => 'Email Haver',
				'email' => 'emailhaver@example.com'
			)
		);
		$this->assertEquals(Pluck::firstPath($data, 'Post.{n}.Author'), $expect);
		// path ends at an array (list of nodes)
		//   merge DOES break this, kinda
		$expect = array(
			array(
				array(
					'id' => '0000-0000-0000-0000-0001',
					'text' => 'my first comment'
				)
			),
			array(
				array(
					'id' => '0000-0000-0000-0000-0002',
					'text' => 'my second comment'
				),
				array(
					'id' => '0000-0000-0000-0000-0003',
					'text' => 'my third comment'
				)
			)
		);
		$this->assertEquals(Pluck::firstPath($data, 'Post.{n}.Comment'), $expect);
		// path ends at an array (list of nodes, with extra iterator)
		//   merge NO LONGER break this, because we are now merging in first the actual nodes
		$expect = array(
			array(
				'id' => '0000-0000-0000-0000-0001',
				'text' => 'my first comment'
			),
			array(
				'id' => '0000-0000-0000-0000-0002',
				'text' => 'my second comment'
			),
			array(
				'id' => '0000-0000-0000-0000-0003',
				'text' => 'my third comment'
			),
		);
		$this->assertEquals(Pluck::firstPath($data, 'Post.{n}.Comment.{n}'), $expect);
	}

	public function testFirstOrDefault() {
		$data = $this->inputUser;
		$paths = array('Bad.path', 'User.id');
		$this->assertEquals(Pluck::firstPathOrDefault($data, $paths), array(123));
		$paths = array('Bad.path', 'BadPath.2');
		$this->assertNull(Pluck::firstPathOrDefault($data, $paths));
		foreach (array(true, false, null, 'a', 1, 0, 99, array(), array(1, 3, 9)) as $default) {
			$this->assertEquals(Pluck::firstPathOrDefault($data, $paths, $default), $default);
		}
	}

	public function testOneBasic() {
		$data = $this->inputUser;
		$this->assertEquals(Pluck::one($data, 'User.id'), 123);
		$paths = array('Bad.path', 'User.id', 'BadPath.2');
		$this->assertEquals(Pluck::one($data, $paths), 123);
		$paths = array('Bad.path', 'BadPath.2');
		$this->assertNull(Pluck::one($data, $paths));
	}

	public function testOneDefaulting() {
		$data = $this->inputUser;
		$paths = array('Bad.path', 'User.id');
		$this->assertEquals(Pluck::one($data, $paths), 123);
		$paths = array('Bad.path', 'BadPath.2');
		$this->assertNull(Pluck::one($data, $paths));
		foreach (array(true, false, null, 'a', 1, 0, 99, array(), array(1, 3, 9)) as $default) {
			$this->assertEquals(Pluck::one($data, $paths, $default), $default);
		}
	}

	public function testOneMultipleResponses() {
		// if you have multiple possible results which should return...
		//   only the "first" or "current" will show up
		$data = $this->inputUser;
		$expect = array(
				'id' => 9999,
				'name' => 'Cool Guy'
		);
		$this->assertEquals(Pluck::one($data, 'Post.{n}.Author'), $expect);
		$expect = array(
			'id' => '0000-0000-0000-0000-0001',
			'text' => 'my first comment'
		);
		$this->assertEquals(Pluck::one($data, 'Post.{n}.Comment.{n}'), $expect);
	}

	public function testComments() {
		// gotta ensure our comments make sense
		$user = $this->inputUser;
		$this->assertEquals(Pluck::all($user, 'User.id'),  array(123));
		$this->assertEquals(Pluck::all($user, array('Bad.path', 'User.id', 'User.name')),  array(123, 'john doe'));
		$this->assertEquals(Pluck::firstPath($user, 'User.id'),  array(123));
		$this->assertEquals(Pluck::firstPath($user, array('Bad.path', 'User.id', 'User.name')),  array(123));
		$this->assertEquals(Pluck::firstPathOrDefault($user, 'Bad.path', 'default text'),  'default text');
		$this->assertEquals(Pluck::firstPathOrDefault($user, 'Bad.path', array('default', 'array')),  array('default', 'array'));
		$this->assertEquals(Pluck::one($user, 'User.id'),  123);
		$this->assertEquals(Pluck::one($user, array('Bad.path', 'User.id', 'User.name')),  123);
		$this->assertEquals(Pluck::one($user, 'Bad.path', 'non-user'),  'non-user');
	}

	public function testAllEmpty() {
		$user = $this->inputUser;
		$this->assertFalse(Pluck::allEmpty($user, 'User.id'));
		$this->assertFalse(Pluck::allEmpty($user, 'Post.{n}.Comment.{n}'));
		$this->assertFalse(Pluck::allEmpty($user, 'Post.{n}.Comment.{n}.id'));
		$this->assertTrue(Pluck::allEmpty($user, 'Bad.path'));
		$this->assertTrue(Pluck::allEmpty($user, array('Bad.path', 'Also.{n}.bad')));
		$this->assertFalse(Pluck::allEmpty($user, array('Bad.path', 'Also.{n}.bad', 'User.name')));
		// paths matching empty values = filtered out... still empty
		$data = $this->inputMixed;
		$this->assertFalse(Pluck::allEmpty($data, 'true'));
		$this->assertTrue(Pluck::allEmpty($data, 'false'));
		$this->assertTrue(Pluck::allEmpty($data, 'null'));
		// paths matching 0 is technically empty value, but it doesn't get
		// filtered out, and thus, exists, and thus, is not empty
		//   GOTCHA!
		$this->assertFalse(Pluck::allEmpty($data, 'zero'));
		$this->assertFalse(Pluck::allEmpty($data, 'zero', false));
		// solution...
		$this->assertTrue(Pluck::allEmpty($data, 'zero', true));
	}

	public function testFilter() {
		$data = array(0, 1, 'a', true, false, null, '');
		// default = Hash::filter
		$this->assertEquals(array_values(Pluck::filter($data)), array(0, 1, 'a', true));
		$this->assertEquals(array_values(Pluck::filter($data, null)), array(0, 1, 'a', true));
		$this->assertEquals(array_values(Pluck::filter($data, 0)), array(0, 1, 'a', true));
		// true = strip 0
		$this->assertEquals(array_values(Pluck::filter($data, true)), array(1, 'a', true));
		// false = strip nothing
		$this->assertEquals(Pluck::filter($data, false), $data);
		// custom = strip 0
		$this->assertEquals(array_values(Pluck::filter($data, array('Pluck', '_filterExcludeNull'))), array(0, 1, 'a', true, false, ''));
	}

}

