<?php
App::uses('Set2', 'Utility');

class Set2Test extends CakeTestCase {

	public static function articleData() {
		return  array(
			array(
				'Article' => array(
					'id' => '1',
					'user_id' => '1',
					'title' => 'First Article',
					'body' => 'First Article Body'
				),
				'User' => array(
					'id' => '1',
					'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
				),
				'Comment' => array(
					array(
						'id' => '1',
						'article_id' => '1',
						'user_id' => '2',
						'comment' => 'First Comment for First Article',
					),
					array(
						'id' => '2',
						'article_id' => '1',
						'user_id' => '4',
						'comment' => 'Second Comment for First Article',
					),
				),
				'Tag' => array(
					array(
						'id' => '1',
						'tag' => 'tag1',
					),
					array(
						'id' => '2',
						'tag' => 'tag2',
					)
				),
				'Deep' => array(
					'Nesting' => array(
						'test' => array(
							1 => 'foo',
							2 => array(
								'and' => array('more' => 'stuff')
							)
						)
					)
				)
			),
			array(
				'Article' => array(
					'id' => '2',
					'user_id' => '1',
					'title' => 'Second Article',
					'body' => 'Second Article Body',
					'published' => 'Y',
				),
				'User' => array(
					'id' => '2',
					'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
				),
				'Comment' => array(),
				'Tag' => array()
			),
			array(
				'Article' => array(
					'id' => '3',
					'user_id' => '1',
					'title' => 'Third Article',
					'body' => 'Third Article Body',
				),
				'User' => array(
					'id' => '3',
					'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
				),
				'Comment' => array(),
				'Tag' => array()
			),
			array(
				'Article' => array(
					'id' => '4',
					'user_id' => '1',
					'title' => 'Fourth Article',
					'body' => 'Fourth Article Body',
				),
				'User' => array(
					'id' => '4',
					'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
				),
				'Comment' => array(),
				'Tag' => array()
			),
			array(
				'Article' => array(
					'id' => '5',
					'user_id' => '1',
					'title' => 'Fifth Article',
					'body' => 'Fifth Article Body',
				),
				'User' => array(
					'id' => '5',
					'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					),
				'Comment' => array(),
				'Tag' => array()
			)
		);
	}

/**
 * Test get()
 *
 * return void
 */
	public function testGet() {
		$data = self::articleData();

		$result = Set2::get(array(), '1.Article.title');
		$this->assertNull($result);

		$result = Set2::get($data, '');
		$this->assertNull($result);

		$result = Set2::get($data, '0.Article.title');
		$this->assertEquals('First Article', $result);

		$result = Set2::get($data, '1.Article.title');
		$this->assertEquals('Second Article', $result);

		$result = Set2::get($data, '5.Article.title');
		$this->assertNull($result);

		$result = Set2::get($data, '1.Article.title.not_there');
		$this->assertNull($result);

		$result = Set2::get($data, '1.Article');
		$this->assertEquals($data[1]['Article'], $result);
	}

/**
 * Test dimensions.
 *
 * @return void
 */
	public function testDimensions() {
		$result = Set2::dimensions(array());
		$this->assertEquals($result, 0);

		$data = array('one', '2', 'three');
		$result = Set2::dimensions($data);
		$this->assertEquals($result, 1);

		$data = array('1' => '1.1', '2', '3');
		$result = Set2::dimensions($data);
		$this->assertEquals($result, 1);

		$data = array('1' => array('1.1' => '1.1.1'), '2', '3' => array('3.1' => '3.1.1'));
		$result = Set2::dimensions($data);
		$this->assertEquals($result, 2);

		$data = array('1' => '1.1', '2', '3' => array('3.1' => '3.1.1'));
		$result = Set2::dimensions($data);
		$this->assertEquals($result, 1);

		$data = array('1' => array('1.1' => '1.1.1'), '2', '3' => array('3.1' => array('3.1.1' => '3.1.1.1')));
		$result = Set2::dimensions($data);
		$this->assertEquals($result, 2);

	}

/**
 * Test maxDimensions
 *
 * @return void
 */
	public function testMaxDimensions() {
		$data = array('1' => '1.1', '2', '3' => array('3.1' => '3.1.1'));
		$result = Set2::maxDimensions($data);
		$this->assertEquals($result, 2);

		$data = array('1' => array('1.1' => '1.1.1'), '2', '3' => array('3.1' => array('3.1.1' => '3.1.1.1')));
		$result = Set2::maxDimensions($data);
		$this->assertEquals($result, 3);

		$data = array(
			'1' => array('1.1' => '1.1.1'),
			array('2' => array('2.1' => array('2.1.1' => '2.1.1.1'))),
			'3' => array('3.1' => array('3.1.1' => '3.1.1.1'))
		);
		$result = Set2::maxDimensions($data);
		$this->assertEquals($result, 4);

		$data = array(
			'1' => array('1.1' => '1.1.1'),
			array('2' => array('2.1' => array('2.1.1' => array('2.1.1.1')))),
			'3' => array('3.1' => array('3.1.1' => '3.1.1.1'))
		);
		$result = Set2::maxDimensions($data);
		$this->assertEquals($result, 5);

		$data = array(
			'1' => array('1.1' => '1.1.1'),
			array('2' => array('2.1' => array('2.1.1' => array('2.1.1.1' => '2.1.1.1.1')))),
			'3' => array('3.1' => array('3.1.1' => '3.1.1.1'))
		);
		$result = Set2::maxDimensions($data);
		$this->assertEquals($result, 5);

		$data = array(
			'1' => array('1.1' => '1.1.1'),
			array('2' => array('2.1' => array('2.1.1' => array('2.1.1.1' => '2.1.1.1.1')))),
			'3' => array('3.1' => array('3.1.1' => '3.1.1.1'))
		);
		$result = Set2::maxDimensions($data);
		$this->assertEquals($result, 5);
	}

/**
 * Tests Set::flatten
 *
 * @return void
 */
	public function testFlatten() {
		$data = array('Larry', 'Curly', 'Moe');
		$result = Set2::flatten($data);
		$this->assertEquals($result, $data);

		$data[9] = 'Shemp';
		$result = Set2::flatten($data);
		$this->assertEquals($result, $data);

		$data = array(
			array(
				'Post' => array('id' => '1', 'author_id' => '1', 'title' => 'First Post'),
				'Author' => array('id' => '1', 'user' => 'nate', 'password' => 'foo'),
			),
			array(
				'Post' => array('id' => '2', 'author_id' => '3', 'title' => 'Second Post', 'body' => 'Second Post Body'),
				'Author' => array('id' => '3', 'user' => 'larry', 'password' => null),
			)
		);

		$result = Set2::flatten($data);
		$expected = array(
			'0.Post.id' => '1',
			'0.Post.author_id' => '1',
			'0.Post.title' => 'First Post',
			'0.Author.id' => '1',
			'0.Author.user' => 'nate',
			'0.Author.password' => 'foo',
			'1.Post.id' => '2',
			'1.Post.author_id' => '3',
			'1.Post.title' => 'Second Post',
			'1.Post.body' => 'Second Post Body',
			'1.Author.id' => '3',
			'1.Author.user' => 'larry',
			'1.Author.password' => null
		);
		$this->assertEquals($expected, $result);

		$data = array(
			array('Post' => array('id' => 1)),
			array('Post' => array('id' => 2)),
		);
		$result = Set2::flatten($data, '/');
		$expected = array(
			'0/Post/id' => '1',
			'1/Post/id' => '2',
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test diff();
 *
 * @return void
 */
	public function testDiff() {
		$a = array(
			0 => array('name' => 'main'),
			1 => array('name' => 'about')
		);
		$b = array(
			0 => array('name' => 'main'),
			1 => array('name' => 'about'),
			2 => array('name' => 'contact')
		);

		$result = Set2::diff($a, array());
		$expected = $a;
		$this->assertEquals($expected, $result);

		$result = Set2::diff(array(), $b);
		$expected = $b;
		$this->assertEquals($expected, $result);

		$result = Set2::diff($a, $b);
		$expected = array(
			2 => array('name' => 'contact')
		);
		$this->assertEquals($expected, $result);


		$b = array(
			0 => array('name' => 'me'),
			1 => array('name' => 'about')
		);

		$result = Set2::diff($a, $b);
		$expected = array(
			0 => array('name' => 'main')
		);
		$this->assertEquals($expected, $result);

		$a = array();
		$b = array('name' => 'bob', 'address' => 'home');
		$result = Set2::diff($a, $b);
		$this->assertEquals($result, $b);


		$a = array('name' => 'bob', 'address' => 'home');
		$b = array();
		$result = Set2::diff($a, $b);
		$this->assertEquals($result, $a);

		$a = array('key' => true, 'another' => false, 'name' => 'me');
		$b = array('key' => 1, 'another' => 0);
		$expected = array('name' => 'me');
		$result = Set2::diff($a, $b);
		$this->assertEquals($expected, $result);

		$a = array('key' => 'value', 'another' => null, 'name' => 'me');
		$b = array('key' => 'differentValue', 'another' => null);
		$expected = array('key' => 'value', 'name' => 'me');
		$result = Set2::diff($a, $b);
		$this->assertEquals($expected, $result);

		$a = array('key' => 'value', 'another' => null, 'name' => 'me');
		$b = array('key' => 'differentValue', 'another' => 'value');
		$expected = array('key' => 'value', 'another' => null, 'name' => 'me');
		$result = Set2::diff($a, $b);
		$this->assertEquals($expected, $result);

		$a = array('key' => 'value', 'another' => null, 'name' => 'me');
		$b = array('key' => 'differentValue', 'another' => 'value');
		$expected = array('key' => 'differentValue', 'another' => 'value', 'name' => 'me');
		$result = Set2::diff($b, $a);
		$this->assertEquals($expected, $result);

		$a = array('key' => 'value', 'another' => null, 'name' => 'me');
		$b = array(0 => 'differentValue', 1 => 'value');
		$expected = $a + $b;
		$result = Set2::diff($a, $b);
		$this->assertEquals($expected, $result);
	}

/**
 * Test merge()
 *
 * @return void
 */
	public function testMerge() {
		$result = Set2::merge(array('foo'), array('bar'));
		$this->assertEquals($result, array('foo', 'bar'));

		$result = Set2::merge(array('foo'), array('user' => 'bob', 'no-bar'), 'bar');
		$this->assertEquals($result, array('foo', 'user' => 'bob', 'no-bar', 'bar'));

		$a = array('foo', 'foo2');
		$b = array('bar', 'bar2');
		$expected = array('foo', 'foo2', 'bar', 'bar2');
		$this->assertEquals($expected, Set2::merge($a, $b));

		$a = array('foo' => 'bar', 'bar' => 'foo');
		$b = array('foo' => 'no-bar', 'bar' => 'no-foo');
		$expected = array('foo' => 'no-bar', 'bar' => 'no-foo');
		$this->assertEquals($expected, Set2::merge($a, $b));

		$a = array('users' => array('bob', 'jim'));
		$b = array('users' => array('lisa', 'tina'));
		$expected = array('users' => array('bob', 'jim', 'lisa', 'tina'));
		$this->assertEquals($expected, Set2::merge($a, $b));

		$a = array('users' => array('jim', 'bob'));
		$b = array('users' => 'none');
		$expected = array('users' => 'none');
		$this->assertEquals($expected, Set2::merge($a, $b));

		$a = array('users' => array('lisa' => array('id' => 5, 'pw' => 'secret')), 'cakephp');
		$b = array('users' => array('lisa' => array('pw' => 'new-pass', 'age' => 23)), 'ice-cream');
		$expected = array(
			'users' => array('lisa' => array('id' => 5, 'pw' => 'new-pass', 'age' => 23)),
			'cakephp',
			'ice-cream'
		);
		$result = Set2::merge($a, $b); 
		$this->assertEquals($expected, $result);

		$c = array(
			'users' => array('lisa' => array('pw' => 'you-will-never-guess', 'age' => 25, 'pet' => 'dog')),
			'chocolate'
		);
		$expected = array(
			'users' => array('lisa' => array('id' => 5, 'pw' => 'you-will-never-guess', 'age' => 25, 'pet' => 'dog')),
			'cakephp',
			'ice-cream',
			'chocolate'
		);
		$this->assertEquals($expected, Set2::merge($a, $b, $c));

		$this->assertEquals($expected, Set2::merge($a, $b, array(), $c));

		$a = array(
			'Tree',
			'CounterCache',
			'Upload' => array(
				'folder' => 'products',
				'fields' => array('image_1_id', 'image_2_id', 'image_3_id', 'image_4_id', 'image_5_id')
			)
		);
		$b =  array(
			'Cacheable' => array('enabled' => false),
			'Limit',
			'Bindable',
			'Validator',
			'Transactional'
		);
		$expected = array(
			'Tree',
			'CounterCache',
			'Upload' => array(
				'folder' => 'products',
				'fields' => array('image_1_id', 'image_2_id', 'image_3_id', 'image_4_id', 'image_5_id')
			),
			'Cacheable' => array('enabled' => false),
			'Limit',
			'Bindable',
			'Validator',
			'Transactional'
		);
		$this->assertEquals(Set2::merge($a, $b), $expected);
	}

/**
 * test normalizing arrays
 *
 * @return void
 */
	public function testNormalize() {
		$result = Set2::normalize(array('one', 'two', 'three'));
		$expected = array('one' => null, 'two' => null, 'three' => null);
		$this->assertEquals($expected, $result);

		$result = Set2::normalize(array('one', 'two', 'three'), false);
		$expected = array('one', 'two', 'three');
		$this->assertEquals($expected, $result);

		$result = Set2::normalize(array('one' => 1, 'two' => 2, 'three' => 3, 'four'), false);
		$expected = array('one' => 1, 'two' => 2, 'three' => 3, 'four' => null);
		$this->assertEquals($expected, $result);

		$result = Set2::normalize(array('one' => 1, 'two' => 2, 'three' => 3, 'four'));
		$expected = array('one' => 1, 'two' => 2, 'three' => 3, 'four' => null);
		$this->assertEquals($expected, $result);

		$result = Set2::normalize(array('one' => array('a', 'b', 'c' => 'cee'), 'two' => 2, 'three'));
		$expected = array('one' => array('a', 'b', 'c' => 'cee'), 'two' => 2, 'three' => null);
		$this->assertEquals($expected, $result);
	}

/**
 * testContains method
 *
 * @return void
 */
	public function testContains() {
		$data = array('apple', 'bee', 'cyclops');
		$this->assertTrue(Set2::contains($data, array('apple')));
		$this->assertFalse(Set2::contains($data, array('data')));

		$a = array(
			0 => array('name' => 'main'),
			1 => array('name' => 'about')
		);
		$b = array(
			0 => array('name' => 'main'),
			1 => array('name' => 'about'),
			2 => array('name' => 'contact'),
			'a' => 'b'
		);

		$this->assertTrue(Set2::contains($a, $a));
		$this->assertFalse(Set2::contains($a, $b));
		$this->assertTrue(Set2::contains($b, $a));

		$a = array(
			array('User' => array('id' => 1)),
			array('User' => array('id' => 2)),
		);
		$b = array(
			array('User' => array('id' => 1)),
			array('User' => array('id' => 2)),
			array('User' => array('id' => 3))
		);
		$this->assertTrue(Set2::contains($b, $a));
		$this->assertFalse(Set2::contains($a, $b));
	}

/**
 * testFilter method
 *
 * @return void
 */
	public function testFilter() {
		$result = Set2::filter(array('0', false, true, 0, array('one thing', 'I can tell you', 'is you got to be', false)));
		$expected = array('0', 2 => true, 3 => 0, 4 => array('one thing', 'I can tell you', 'is you got to be'));
		$this->assertSame($expected, $result);

		$result = Set2::filter(array(1, array(false)));
		$expected = array(1);
		$this->assertEquals($expected, $result);

		$result = Set2::filter(array(1, array(false, false)));
		$expected = array(1);
		$this->assertEquals($expected, $result);

		$result = Set2::filter(array(1, array('empty', false)));
		$expected = array(1, array('empty'));
		$this->assertEquals($expected, $result);

		$result = Set2::filter(array(1, array('2', false, array(3, null))));
		$expected = array(1, array('2', 2 => array(3)));
		$this->assertEquals($expected, $result);

		$this->assertSame(array(), Set2::filter(array()));
	}

/**
 * testNumericArrayCheck method
 *
 * @return void
 */
	public function testNumeric() {
		$data = array('one');
		$this->assertTrue(Set::numeric(array_keys($data)));

		$data = array(1 => 'one');
		$this->assertFalse(Set::numeric($data));

		$data = array('one');
		$this->assertFalse(Set::numeric($data));

		$data = array('one' => 'two');
		$this->assertFalse(Set::numeric($data));

		$data = array('one' => 1);
		$this->assertTrue(Set::numeric($data));

		$data = array(0);
		$this->assertTrue(Set::numeric($data));

		$data = array('one', 'two', 'three', 'four', 'five');
		$this->assertTrue(Set::numeric(array_keys($data)));

		$data = array(1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five');
		$this->assertTrue(Set::numeric(array_keys($data)));

		$data = array('1' => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five');
		$this->assertTrue(Set::numeric(array_keys($data)));

		$data = array('one', 2 => 'two', 3 => 'three', 4 => 'four', 'a' => 'five');
		$this->assertFalse(Set::numeric(array_keys($data)));
	}

/**
 * Test simple paths.
 *
 * @return void
 */
	public function testExtractBasic() {
		$data = self::articleData();

		$result = Set2::extract($data, '');
		$this->assertEquals($data, $result);

		$result = Set2::extract($data, '0.Article.title');
		$this->assertEquals(array('First Article'), $result);

		$result = Set2::extract($data, '1.Article.title');
		$this->assertEquals(array('Second Article'), $result);
	}

/**
 * Test the {n} selector
 *
 * @return void
 */
	public function testExtractNumericKey() {
		$data = self::articleData();
		$result = Set2::extract($data, '{n}.Article.title');
		$expected = array(
			'First Article', 'Second Article', 
			'Third Article', 'Fourth Article',
			'Fifth Article'
		);
		$this->assertEquals($expected, $result);

		$result = Set2::extract($data, '0.Comment.{n}.user_id');
		$expected = array(
			'2', '4'
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test the {n} selector with inconsistent arrays
 *
 * @return void
 */
	public function testExtractNumbericMixedKeys() {
		$data = array(
			'User' => array(
				0 => array(
					'id' => 4,
					'name' => 'Neo'
				),
				1 => array(
					'id' => 5,
					'name' => 'Morpheus'
				),
				'stringKey' => array(
					'name' => 'Fail'
				)
			)
		);
		$result = Set2::extract($data, 'User.{n}.name');
		$expected = array('Neo', 'Morpheus');
		$this->assertEquals($expected, $result);
	}

/**
 * Test the {n} selector with non-zero based arrays
 *
 * @return void
 */
	public function testExtractNumericNonZero() {
		$data = array(
			1 => array(
				'User' => array(
					'id' => 1,
					'name' => 'John',
				)
			),
			2 => array(
				'User' => array(
					'id' => 2,
					'name' => 'Bob',
				)
			),
			3 => array(
				'User' => array(
					'id' => 3,
					'name' => 'Tony',
				)
			)
		);
		$result = Set2::extract($data, '{n}.User.name');
		$expected = array('John', 'Bob', 'Tony');
		$this->assertEquals($expected, $result);
	}
	
/**
 * Test the {s} selector.
 *
 * @return void
 */
	public function testExtractStringKey() {
		$data = self::articleData();
		$result = Set2::extract($data, '{n}.{s}.user');
		$expected = array(
			'mariano', 'mariano', 
			'mariano', 'mariano',
			'mariano'
		);
		$this->assertEquals($expected, $result);

		$result = Set2::extract($data, '{n}.{s}.Nesting.test.1');
		$this->assertEquals(array('foo'), $result);
	}

/**
 * Test the attribute presense selector.
 *
 * @return void
 */
	public function testExtractAttributePresence() {
		$data = self::articleData();

		$result = Set2::extract($data, '{n}.Article[published]');
		$expected = array($data[1]['Article']);
		$this->assertEquals($expected, $result);

		$result = Set2::extract($data, '{n}.Article[id][published]');
		$expected = array($data[1]['Article']);
		$this->assertEquals($expected, $result);
	}

/**
 * Test = and != operators.
 *
 * @return void
 */
	public function testExtractAttributeEquality() {
		$data = self::articleData();

		$result = Set2::extract($data, '{n}.Article[id=3]');
		$expected = array($data[2]['Article']);
		$this->assertEquals($expected, $result);

		$result = Set2::extract($data, '{n}.Article[id = 3]');
		$expected = array($data[2]['Article']);
		$this->assertEquals($expected, $result, 'Whitespace should not matter.');

		$result = Set2::extract($data, '{n}.Article[id!=3]');
		$this->assertEquals(1, $result[0]['id']);
		$this->assertEquals(2, $result[1]['id']);
		$this->assertEquals(4, $result[2]['id']);
		$this->assertEquals(5, $result[3]['id']);
	}

/**
 * Test comparison operators.
 *
 * @return void
 */
	public function testExtractAttributeComparison() {
		$data = self::articleData();

		$result = Set2::extract($data, '{n}.Comment.{n}[user_id > 2]');
		$expected = array($data[0]['Comment'][1]);
		$this->assertEquals($expected, $result);
		$this->assertEquals(4, $expected[0]['user_id']);

		$result = Set2::extract($data, '{n}.Comment.{n}[user_id >= 4]');
		$expected = array($data[0]['Comment'][1]);
		$this->assertEquals($expected, $result);
		$this->assertEquals(4, $expected[0]['user_id']);

		$result = Set2::extract($data, '{n}.Comment.{n}[user_id < 3]');
		$expected = array($data[0]['Comment'][0]);
		$this->assertEquals($expected, $result);
		$this->assertEquals(2, $expected[0]['user_id']);

		$result = Set2::extract($data, '{n}.Comment.{n}[user_id <= 2]');
		$expected = array($data[0]['Comment'][0]);
		$this->assertEquals($expected, $result);
		$this->assertEquals(2, $expected[0]['user_id']);
	}

/**
 * Test multiple attributes with conditions.
 *
 * @return void
 */
	public function testExtractAttributeMultiple() {
		$data = self::articleData();

		$result = Set2::extract($data, '{n}.Comment.{n}[user_id > 2][id=1]');
		$this->assertEmpty($result);

		$result = Set2::extract($data, '{n}.Comment.{n}[user_id > 2][id=2]');
		$expected = array($data[0]['Comment'][1]);
		$this->assertEquals($expected, $result);
		$this->assertEquals(4, $expected[0]['user_id']);
	}

/**
 * Test attribute pattern matching.
 *
 * @return void
 */
	public function testExtractAttributePattern() {
		$data = self::articleData();

		$result = Set2::extract($data, '{n}.Article[title=/^First/]');
		$expected = array($data[0]['Article']);
		$this->assertEquals($expected, $result);
	}

}
