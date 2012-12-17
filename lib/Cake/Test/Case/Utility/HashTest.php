<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Utility
 * @since         CakePHP(tm) v 2.2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('Hash', 'Utility');

class HashTest extends CakeTestCase {

	public static function articleData() {
		return array(
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

	public static function userData() {
		return array(
			array(
				'User' => array(
					'id' => 2,
					'group_id' => 1,
					'Data' => array(
						'user' => 'mariano.iglesias',
						'name' => 'Mariano Iglesias'
					)
				)
			),
			array(
				'User' => array(
					'id' => 14,
					'group_id' => 2,
					'Data' => array(
						'user' => 'phpnut',
						'name' => 'Larry E. Masters'
					)
				)
			),
			array(
				'User' => array(
					'id' => 25,
					'group_id' => 1,
					'Data' => array(
						'user' => 'gwoo',
						'name' => 'The Gwoo'
					)
				)
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

		$result = Hash::get(array(), '1.Article.title');
		$this->assertNull($result);

		$result = Hash::get($data, '');
		$this->assertNull($result);

		$result = Hash::get($data, '0.Article.title');
		$this->assertEquals('First Article', $result);

		$result = Hash::get($data, '1.Article.title');
		$this->assertEquals('Second Article', $result);

		$result = Hash::get($data, '5.Article.title');
		$this->assertNull($result);

		$result = Hash::get($data, '1.Article.title.not_there');
		$this->assertNull($result);

		$result = Hash::get($data, '1.Article');
		$this->assertEquals($data[1]['Article'], $result);

		$result = Hash::get($data, array('1', 'Article'));
		$this->assertEquals($data[1]['Article'], $result);
	}

/**
 * Test dimensions.
 *
 * @return void
 */
	public function testDimensions() {
		$result = Hash::dimensions(array());
		$this->assertEquals($result, 0);

		$data = array('one', '2', 'three');
		$result = Hash::dimensions($data);
		$this->assertEquals($result, 1);

		$data = array('1' => '1.1', '2', '3');
		$result = Hash::dimensions($data);
		$this->assertEquals($result, 1);

		$data = array('1' => array('1.1' => '1.1.1'), '2', '3' => array('3.1' => '3.1.1'));
		$result = Hash::dimensions($data);
		$this->assertEquals($result, 2);

		$data = array('1' => '1.1', '2', '3' => array('3.1' => '3.1.1'));
		$result = Hash::dimensions($data);
		$this->assertEquals($result, 1);

		$data = array('1' => array('1.1' => '1.1.1'), '2', '3' => array('3.1' => array('3.1.1' => '3.1.1.1')));
		$result = Hash::dimensions($data);
		$this->assertEquals($result, 2);
	}

/**
 * Test maxDimensions
 *
 * @return void
 */
	public function testMaxDimensions() {
		$data = array('1' => '1.1', '2', '3' => array('3.1' => '3.1.1'));
		$result = Hash::maxDimensions($data);
		$this->assertEquals($result, 2);

		$data = array('1' => array('1.1' => '1.1.1'), '2', '3' => array('3.1' => array('3.1.1' => '3.1.1.1')));
		$result = Hash::maxDimensions($data);
		$this->assertEquals($result, 3);

		$data = array(
			'1' => array('1.1' => '1.1.1'),
			array('2' => array('2.1' => array('2.1.1' => '2.1.1.1'))),
			'3' => array('3.1' => array('3.1.1' => '3.1.1.1'))
		);
		$result = Hash::maxDimensions($data);
		$this->assertEquals($result, 4);

		$data = array(
			'1' => array('1.1' => '1.1.1'),
			array('2' => array('2.1' => array('2.1.1' => array('2.1.1.1')))),
			'3' => array('3.1' => array('3.1.1' => '3.1.1.1'))
		);
		$result = Hash::maxDimensions($data);
		$this->assertEquals($result, 5);

		$data = array(
			'1' => array('1.1' => '1.1.1'),
			array('2' => array('2.1' => array('2.1.1' => array('2.1.1.1' => '2.1.1.1.1')))),
			'3' => array('3.1' => array('3.1.1' => '3.1.1.1'))
		);
		$result = Hash::maxDimensions($data);
		$this->assertEquals($result, 5);

		$data = array(
			'1' => array('1.1' => '1.1.1'),
			array('2' => array('2.1' => array('2.1.1' => array('2.1.1.1' => '2.1.1.1.1')))),
			'3' => array('3.1' => array('3.1.1' => '3.1.1.1'))
		);
		$result = Hash::maxDimensions($data);
		$this->assertEquals($result, 5);
	}

/**
 * Tests Hash::flatten
 *
 * @return void
 */
	public function testFlatten() {
		$data = array('Larry', 'Curly', 'Moe');
		$result = Hash::flatten($data);
		$this->assertEquals($result, $data);

		$data[9] = 'Shemp';
		$result = Hash::flatten($data);
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
		$result = Hash::flatten($data);
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
			array(
				'Post' => array('id' => '1', 'author_id' => null, 'title' => 'First Post'),
				'Author' => array(),
			)
		);
		$result = Hash::flatten($data);
		$expected = array(
			'0.Post.id' => '1',
			'0.Post.author_id' => null,
			'0.Post.title' => 'First Post',
			'0.Author' => array()
		);
		$this->assertEquals($expected, $result);

		$data = array(
			array('Post' => array('id' => 1)),
			array('Post' => array('id' => 2)),
		);
		$result = Hash::flatten($data, '/');
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

		$result = Hash::diff($a, array());
		$expected = $a;
		$this->assertEquals($expected, $result);

		$result = Hash::diff(array(), $b);
		$expected = $b;
		$this->assertEquals($expected, $result);

		$result = Hash::diff($a, $b);
		$expected = array(
			2 => array('name' => 'contact')
		);
		$this->assertEquals($expected, $result);

		$b = array(
			0 => array('name' => 'me'),
			1 => array('name' => 'about')
		);

		$result = Hash::diff($a, $b);
		$expected = array(
			0 => array('name' => 'main')
		);
		$this->assertEquals($expected, $result);

		$a = array();
		$b = array('name' => 'bob', 'address' => 'home');
		$result = Hash::diff($a, $b);
		$this->assertEquals($result, $b);

		$a = array('name' => 'bob', 'address' => 'home');
		$b = array();
		$result = Hash::diff($a, $b);
		$this->assertEquals($result, $a);

		$a = array('key' => true, 'another' => false, 'name' => 'me');
		$b = array('key' => 1, 'another' => 0);
		$expected = array('name' => 'me');
		$result = Hash::diff($a, $b);
		$this->assertEquals($expected, $result);

		$a = array('key' => 'value', 'another' => null, 'name' => 'me');
		$b = array('key' => 'differentValue', 'another' => null);
		$expected = array('key' => 'value', 'name' => 'me');
		$result = Hash::diff($a, $b);
		$this->assertEquals($expected, $result);

		$a = array('key' => 'value', 'another' => null, 'name' => 'me');
		$b = array('key' => 'differentValue', 'another' => 'value');
		$expected = array('key' => 'value', 'another' => null, 'name' => 'me');
		$result = Hash::diff($a, $b);
		$this->assertEquals($expected, $result);

		$a = array('key' => 'value', 'another' => null, 'name' => 'me');
		$b = array('key' => 'differentValue', 'another' => 'value');
		$expected = array('key' => 'differentValue', 'another' => 'value', 'name' => 'me');
		$result = Hash::diff($b, $a);
		$this->assertEquals($expected, $result);

		$a = array('key' => 'value', 'another' => null, 'name' => 'me');
		$b = array(0 => 'differentValue', 1 => 'value');
		$expected = $a + $b;
		$result = Hash::diff($a, $b);
		$this->assertEquals($expected, $result);
	}

/**
 * Test merge()
 *
 * @return void
 */
	public function testMerge() {
		$result = Hash::merge(array('foo'), array('bar'));
		$this->assertEquals($result, array('foo', 'bar'));

		$result = Hash::merge(array('foo'), array('user' => 'bob', 'no-bar'), 'bar');
		$this->assertEquals($result, array('foo', 'user' => 'bob', 'no-bar', 'bar'));

		$a = array('foo', 'foo2');
		$b = array('bar', 'bar2');
		$expected = array('foo', 'foo2', 'bar', 'bar2');
		$this->assertEquals($expected, Hash::merge($a, $b));

		$a = array('foo' => 'bar', 'bar' => 'foo');
		$b = array('foo' => 'no-bar', 'bar' => 'no-foo');
		$expected = array('foo' => 'no-bar', 'bar' => 'no-foo');
		$this->assertEquals($expected, Hash::merge($a, $b));

		$a = array('users' => array('bob', 'jim'));
		$b = array('users' => array('lisa', 'tina'));
		$expected = array('users' => array('bob', 'jim', 'lisa', 'tina'));
		$this->assertEquals($expected, Hash::merge($a, $b));

		$a = array('users' => array('jim', 'bob'));
		$b = array('users' => 'none');
		$expected = array('users' => 'none');
		$this->assertEquals($expected, Hash::merge($a, $b));

		$a = array('users' => array('lisa' => array('id' => 5, 'pw' => 'secret')), 'cakephp');
		$b = array('users' => array('lisa' => array('pw' => 'new-pass', 'age' => 23)), 'ice-cream');
		$expected = array(
			'users' => array('lisa' => array('id' => 5, 'pw' => 'new-pass', 'age' => 23)),
			'cakephp',
			'ice-cream'
		);
		$result = Hash::merge($a, $b);
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
		$this->assertEquals($expected, Hash::merge($a, $b, $c));

		$this->assertEquals($expected, Hash::merge($a, $b, array(), $c));

		$a = array(
			'Tree',
			'CounterCache',
			'Upload' => array(
				'folder' => 'products',
				'fields' => array('image_1_id', 'image_2_id', 'image_3_id', 'image_4_id', 'image_5_id')
			)
		);
		$b = array(
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
		$this->assertEquals(Hash::merge($a, $b), $expected);
	}

/**
 * test normalizing arrays
 *
 * @return void
 */
	public function testNormalize() {
		$result = Hash::normalize(array('one', 'two', 'three'));
		$expected = array('one' => null, 'two' => null, 'three' => null);
		$this->assertEquals($expected, $result);

		$result = Hash::normalize(array('one', 'two', 'three'), false);
		$expected = array('one', 'two', 'three');
		$this->assertEquals($expected, $result);

		$result = Hash::normalize(array('one' => 1, 'two' => 2, 'three' => 3, 'four'), false);
		$expected = array('one' => 1, 'two' => 2, 'three' => 3, 'four' => null);
		$this->assertEquals($expected, $result);

		$result = Hash::normalize(array('one' => 1, 'two' => 2, 'three' => 3, 'four'));
		$expected = array('one' => 1, 'two' => 2, 'three' => 3, 'four' => null);
		$this->assertEquals($expected, $result);

		$result = Hash::normalize(array('one' => array('a', 'b', 'c' => 'cee'), 'two' => 2, 'three'));
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
		$this->assertTrue(Hash::contains($data, array('apple')));
		$this->assertFalse(Hash::contains($data, array('data')));

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

		$this->assertTrue(Hash::contains($a, $a));
		$this->assertFalse(Hash::contains($a, $b));
		$this->assertTrue(Hash::contains($b, $a));

		$a = array(
			array('User' => array('id' => 1)),
			array('User' => array('id' => 2)),
		);
		$b = array(
			array('User' => array('id' => 1)),
			array('User' => array('id' => 2)),
			array('User' => array('id' => 3))
		);
		$this->assertTrue(Hash::contains($b, $a));
		$this->assertFalse(Hash::contains($a, $b));
	}

/**
 * testFilter method
 *
 * @return void
 */
	public function testFilter() {
		$result = Hash::filter(array('0', false, true, 0, array('one thing', 'I can tell you', 'is you got to be', false)));
		$expected = array('0', 2 => true, 3 => 0, 4 => array('one thing', 'I can tell you', 'is you got to be'));
		$this->assertSame($expected, $result);

		$result = Hash::filter(array(1, array(false)));
		$expected = array(1);
		$this->assertEquals($expected, $result);

		$result = Hash::filter(array(1, array(false, false)));
		$expected = array(1);
		$this->assertEquals($expected, $result);

		$result = Hash::filter(array(1, array('empty', false)));
		$expected = array(1, array('empty'));
		$this->assertEquals($expected, $result);

		$result = Hash::filter(array(1, array('2', false, array(3, null))));
		$expected = array(1, array('2', 2 => array(3)));
		$this->assertEquals($expected, $result);

		$this->assertSame(array(), Hash::filter(array()));
	}

/**
 * testNumericArrayCheck method
 *
 * @return void
 */
	public function testNumeric() {
		$data = array('one');
		$this->assertTrue(Hash::numeric(array_keys($data)));

		$data = array(1 => 'one');
		$this->assertFalse(Hash::numeric($data));

		$data = array('one');
		$this->assertFalse(Hash::numeric($data));

		$data = array('one' => 'two');
		$this->assertFalse(Hash::numeric($data));

		$data = array('one' => 1);
		$this->assertTrue(Hash::numeric($data));

		$data = array(0);
		$this->assertTrue(Hash::numeric($data));

		$data = array('one', 'two', 'three', 'four', 'five');
		$this->assertTrue(Hash::numeric(array_keys($data)));

		$data = array(1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five');
		$this->assertTrue(Hash::numeric(array_keys($data)));

		$data = array('1' => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five');
		$this->assertTrue(Hash::numeric(array_keys($data)));

		$data = array('one', 2 => 'two', 3 => 'three', 4 => 'four', 'a' => 'five');
		$this->assertFalse(Hash::numeric(array_keys($data)));
	}

/**
 * Test simple paths.
 *
 * @return void
 */
	public function testExtractBasic() {
		$data = self::articleData();

		$result = Hash::extract($data, '');
		$this->assertEquals($data, $result);

		$result = Hash::extract($data, '0.Article.title');
		$this->assertEquals(array('First Article'), $result);

		$result = Hash::extract($data, '1.Article.title');
		$this->assertEquals(array('Second Article'), $result);

		$result = Hash::extract(array(false), '{n}.Something.another_thing');
		$this->assertEquals(array(), $result);
	}

/**
 * Test the {n} selector
 *
 * @return void
 */
	public function testExtractNumericKey() {
		$data = self::articleData();
		$result = Hash::extract($data, '{n}.Article.title');
		$expected = array(
			'First Article', 'Second Article',
			'Third Article', 'Fourth Article',
			'Fifth Article'
		);
		$this->assertEquals($expected, $result);

		$result = Hash::extract($data, '0.Comment.{n}.user_id');
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
	public function testExtractNumericMixedKeys() {
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
		$result = Hash::extract($data, 'User.{n}.name');
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
		$result = Hash::extract($data, '{n}.User.name');
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
		$result = Hash::extract($data, '{n}.{s}.user');
		$expected = array(
			'mariano',
			'mariano',
			'mariano',
			'mariano',
			'mariano'
		);
		$this->assertEquals($expected, $result);

		$result = Hash::extract($data, '{n}.{s}.Nesting.test.1');
		$this->assertEquals(array('foo'), $result);
	}

/**
 * Test the attribute presense selector.
 *
 * @return void
 */
	public function testExtractAttributePresence() {
		$data = self::articleData();

		$result = Hash::extract($data, '{n}.Article[published]');
		$expected = array($data[1]['Article']);
		$this->assertEquals($expected, $result);

		$result = Hash::extract($data, '{n}.Article[id][published]');
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

		$result = Hash::extract($data, '{n}.Article[id=3]');
		$expected = array($data[2]['Article']);
		$this->assertEquals($expected, $result);

		$result = Hash::extract($data, '{n}.Article[id = 3]');
		$expected = array($data[2]['Article']);
		$this->assertEquals($expected, $result, 'Whitespace should not matter.');

		$result = Hash::extract($data, '{n}.Article[id!=3]');
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

		$result = Hash::extract($data, '{n}.Comment.{n}[user_id > 2]');
		$expected = array($data[0]['Comment'][1]);
		$this->assertEquals($expected, $result);
		$this->assertEquals(4, $expected[0]['user_id']);

		$result = Hash::extract($data, '{n}.Comment.{n}[user_id >= 4]');
		$expected = array($data[0]['Comment'][1]);
		$this->assertEquals($expected, $result);
		$this->assertEquals(4, $expected[0]['user_id']);

		$result = Hash::extract($data, '{n}.Comment.{n}[user_id < 3]');
		$expected = array($data[0]['Comment'][0]);
		$this->assertEquals($expected, $result);
		$this->assertEquals(2, $expected[0]['user_id']);

		$result = Hash::extract($data, '{n}.Comment.{n}[user_id <= 2]');
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

		$result = Hash::extract($data, '{n}.Comment.{n}[user_id > 2][id=1]');
		$this->assertEmpty($result);

		$result = Hash::extract($data, '{n}.Comment.{n}[user_id > 2][id=2]');
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

		$result = Hash::extract($data, '{n}.Article[title=/^First/]');
		$expected = array($data[0]['Article']);
		$this->assertEquals($expected, $result);
	}

/**
 * Test that extract() + matching can hit null things.
 */
	public function testExtractMatchesNull() {
		$data = array(
			'Country' => array(
				array('name' => 'Canada'),
				array('name' => 'Australia'),
				array('name' => null),
			)
		);
		$result = Hash::extract($data, 'Country.{n}[name=/Canada|^$/]');
		$expected = array(
			array(
				'name' => 'Canada',
			),
			array(
				'name' => null,
			),
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test that uneven keys are handled correctly.
 *
 * @return void
 */
	public function testExtractUnevenKeys() {
		$data = array(
			'Level1' => array(
				'Level2' => array('test1', 'test2'),
				'Level2bis' => array('test3', 'test4')
			)
		);
		$this->assertEquals(
			array('test1', 'test2'),
			Hash::extract($data, 'Level1.Level2')
		);
		$this->assertEquals(
			array('test3', 'test4'),
			Hash::extract($data, 'Level1.Level2bis')
		);

		$data = array(
			'Level1' => array(
				'Level2bis' => array(
					array('test3', 'test4'),
					array('test5', 'test6')
				)
			)
		);
		$expected = array(
			array('test3', 'test4'),
			array('test5', 'test6')
		);
		$this->assertEquals($expected, Hash::extract($data, 'Level1.Level2bis'));

		$data['Level1']['Level2'] = array('test1', 'test2');
		$this->assertEquals($expected, Hash::extract($data, 'Level1.Level2bis'));
	}

/**
 * testSort method
 *
 * @return void
 */
	public function testSort() {
		$result = Hash::sort(array(), '{n}.name', 'asc');
		$this->assertEquals(array(), $result);

		$a = array(
			0 => array(
				'Person' => array('name' => 'Jeff'),
				'Friend' => array(array('name' => 'Nate'))
			),
			1 => array(
				'Person' => array('name' => 'Tracy'),
				'Friend' => array(array('name' => 'Lindsay'))
			)
		);
		$b = array(
			0 => array(
				'Person' => array('name' => 'Tracy'),
				'Friend' => array(array('name' => 'Lindsay'))
			),
			1 => array(
				'Person' => array('name' => 'Jeff'),
				'Friend' => array(array('name' => 'Nate'))
			)
		);
		$a = Hash::sort($a, '{n}.Friend.{n}.name', 'asc');
		$this->assertEquals($a, $b);

		$b = array(
			0 => array(
				'Person' => array('name' => 'Jeff'),
				'Friend' => array(array('name' => 'Nate'))
			),
			1 => array(
				'Person' => array('name' => 'Tracy'),
				'Friend' => array(array('name' => 'Lindsay'))
			)
		);
		$a = array(
			0 => array(
				'Person' => array('name' => 'Tracy'),
				'Friend' => array(array('name' => 'Lindsay'))
			),
			1 => array(
				'Person' => array('name' => 'Jeff'),
				'Friend' => array(array('name' => 'Nate'))
			)
		);
		$a = Hash::sort($a, '{n}.Friend.{n}.name', 'desc');
		$this->assertEquals($a, $b);

		$a = array(
			0 => array(
				'Person' => array('name' => 'Jeff'),
				'Friend' => array(array('name' => 'Nate'))
			),
			1 => array(
				'Person' => array('name' => 'Tracy'),
				'Friend' => array(array('name' => 'Lindsay'))
			),
			2 => array(
				'Person' => array('name' => 'Adam'),
				'Friend' => array(array('name' => 'Bob'))
			)
		);
		$b = array(
			0 => array(
				'Person' => array('name' => 'Adam'),
				'Friend' => array(array('name' => 'Bob'))
			),
			1 => array(
				'Person' => array('name' => 'Jeff'),
				'Friend' => array(array('name' => 'Nate'))
			),
			2 => array(
				'Person' => array('name' => 'Tracy'),
				'Friend' => array(array('name' => 'Lindsay'))
			)
		);
		$a = Hash::sort($a, '{n}.Person.name', 'asc');
		$this->assertEquals($a, $b);

		$a = array(
			0 => array('Person' => array('name' => 'Jeff')),
			1 => array('Shirt' => array('color' => 'black'))
		);
		$b = array(
			0 => array('Shirt' => array('color' => 'black')),
			1 => array('Person' => array('name' => 'Jeff')),
		);
		$a = Hash::sort($a, '{n}.Person.name', 'ASC', 'STRING');
		$this->assertEquals($a, $b);

		$names = array(
			array('employees' => array(
				array('name' => array('first' => 'John', 'last' => 'Doe')))
			),
			array('employees' => array(
				array('name' => array('first' => 'Jane', 'last' => 'Doe')))
			),
			array('employees' => array(array('name' => array()))),
			array('employees' => array(array('name' => array())))
		);
		$result = Hash::sort($names, '{n}.employees.0.name', 'asc');
		$expected = array(
			array('employees' => array(
				array('name' => array('first' => 'John', 'last' => 'Doe')))
			),
			array('employees' => array(
				array('name' => array('first' => 'Jane', 'last' => 'Doe')))
			),
			array('employees' => array(array('name' => array()))),
			array('employees' => array(array('name' => array())))
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test sort() with numeric option.
 *
 * @return void
 */
	public function testSortNumeric() {
		$items = array(
			array('Item' => array('price' => '155,000')),
			array('Item' => array('price' => '139,000')),
			array('Item' => array('price' => '275,622')),
			array('Item' => array('price' => '230,888')),
			array('Item' => array('price' => '66,000')),
		);
		$result = Hash::sort($items, '{n}.Item.price', 'asc', 'numeric');
		$expected = array(
			array('Item' => array('price' => '66,000')),
			array('Item' => array('price' => '139,000')),
			array('Item' => array('price' => '155,000')),
			array('Item' => array('price' => '230,888')),
			array('Item' => array('price' => '275,622')),
		);
		$this->assertEquals($expected, $result);

		$result = Hash::sort($items, '{n}.Item.price', 'desc', 'numeric');
		$expected = array(
			array('Item' => array('price' => '275,622')),
			array('Item' => array('price' => '230,888')),
			array('Item' => array('price' => '155,000')),
			array('Item' => array('price' => '139,000')),
			array('Item' => array('price' => '66,000')),
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test natural sorting.
 *
 * @return void
 */
	public function testSortNatural() {
		if (version_compare(PHP_VERSION, '5.4.0', '<')) {
			$this->markTestSkipped('SORT_NATURAL is available since PHP 5.4.');
		}
		$items = array(
			array('Item' => array('image' => 'img1.jpg')),
			array('Item' => array('image' => 'img99.jpg')),
			array('Item' => array('image' => 'img12.jpg')),
			array('Item' => array('image' => 'img10.jpg')),
			array('Item' => array('image' => 'img2.jpg')),
		);
		$result = Hash::sort($items, '{n}.Item.image', 'desc', 'natural');
		$expected = array(
			array('Item' => array('image' => 'img99.jpg')),
			array('Item' => array('image' => 'img12.jpg')),
			array('Item' => array('image' => 'img10.jpg')),
			array('Item' => array('image' => 'img2.jpg')),
			array('Item' => array('image' => 'img1.jpg')),
		);
		$this->assertEquals($expected, $result);

		$result = Hash::sort($items, '{n}.Item.image', 'asc', 'natural');
		$expected = array(
			array('Item' => array('image' => 'img1.jpg')),
			array('Item' => array('image' => 'img2.jpg')),
			array('Item' => array('image' => 'img10.jpg')),
			array('Item' => array('image' => 'img12.jpg')),
			array('Item' => array('image' => 'img99.jpg')),
		);
		$this->assertEquals($expected, $result);
	}

/**
 * test sorting with out of order keys.
 *
 * @return void
 */
	public function testSortWithOutOfOrderKeys() {
		$data = array(
			9 => array('class' => 510, 'test2' => 2),
			1 => array('class' => 500, 'test2' => 1),
			2 => array('class' => 600, 'test2' => 2),
			5 => array('class' => 625, 'test2' => 4),
			0 => array('class' => 605, 'test2' => 3),
		);
		$expected = array(
			array('class' => 500, 'test2' => 1),
			array('class' => 510, 'test2' => 2),
			array('class' => 600, 'test2' => 2),
			array('class' => 605, 'test2' => 3),
			array('class' => 625, 'test2' => 4),
		);
		$result = Hash::sort($data, '{n}.class', 'asc');
		$this->assertEquals($expected, $result);

		$result = Hash::sort($data, '{n}.test2', 'asc');
		$this->assertEquals($expected, $result);
	}

/**
 * test sorting with string keys.
 *
 * @return void
 */
	public function testSortString() {
		$toSort = array(
			'four' => array('number' => 4, 'some' => 'foursome'),
			'six' => array('number' => 6, 'some' => 'sixsome'),
			'five' => array('number' => 5, 'some' => 'fivesome'),
			'two' => array('number' => 2, 'some' => 'twosome'),
			'three' => array('number' => 3, 'some' => 'threesome')
		);
		$sorted = Hash::sort($toSort, '{s}.number', 'asc');
		$expected = array(
			'two' => array('number' => 2, 'some' => 'twosome'),
			'three' => array('number' => 3, 'some' => 'threesome'),
			'four' => array('number' => 4, 'some' => 'foursome'),
			'five' => array('number' => 5, 'some' => 'fivesome'),
			'six' => array('number' => 6, 'some' => 'sixsome')
		);
		$this->assertEquals($expected, $sorted);

		$menus = array(
			'blogs' => array('title' => 'Blogs', 'weight' => 3),
			'comments' => array('title' => 'Comments', 'weight' => 2),
			'users' => array('title' => 'Users', 'weight' => 1),
		);
		$expected = array(
			'users' => array('title' => 'Users', 'weight' => 1),
			'comments' => array('title' => 'Comments', 'weight' => 2),
			'blogs' => array('title' => 'Blogs', 'weight' => 3),
		);
		$result = Hash::sort($menus, '{s}.weight', 'ASC');
		$this->assertEquals($expected, $result);
	}

/**
 * Test insert()
 *
 * @return void
 */
	public function testInsertSimple() {
		$a = array(
			'pages' => array('name' => 'page')
		);
		$result = Hash::insert($a, 'files', array('name' => 'files'));
		$expected = array(
			'pages' => array('name' => 'page'),
			'files' => array('name' => 'files')
		);
		$this->assertEquals($expected, $result);

		$a = array(
			'pages' => array('name' => 'page')
		);
		$result = Hash::insert($a, 'pages.name', array());
		$expected = array(
			'pages' => array('name' => array()),
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test inserting with multiple values.
 *
 * @return void
 */
	public function testInsertMulti() {
		$data = self::articleData();

		$result = Hash::insert($data, '{n}.Article.insert', 'value');
		$this->assertEquals('value', $result[0]['Article']['insert']);
		$this->assertEquals('value', $result[1]['Article']['insert']);

		$result = Hash::insert($data, '{n}.Comment.{n}.insert', 'value');
		$this->assertEquals('value', $result[0]['Comment'][0]['insert']);
		$this->assertEquals('value', $result[0]['Comment'][1]['insert']);
	}

/**
 * Test that insert() can insert data over a string value.
 *
 * @return void
 */
	public function testInsertOverwriteStringValue() {
		$data = array(
			'Some' => array(
				'string' => 'value'
			)
		);
		$result = Hash::insert($data, 'Some.string.value', array('values'));
		$expected = array(
			'Some' => array(
				'string' => array(
					'value' => array('values')
				)
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test remove() method.
 *
 * @return void
 */
	public function testRemove() {
		$a = array(
			'pages' => array('name' => 'page'),
			'files' => array('name' => 'files')
		);

		$result = Hash::remove($a, 'files');
		$expected = array(
			'pages' => array('name' => 'page')
		);
		$this->assertEquals($expected, $result);

		$a = array(
			'pages' => array(
				0 => array('name' => 'main'),
				1 => array(
					'name' => 'about',
					'vars' => array('title' => 'page title')
				)
			)
		);

		$result = Hash::remove($a, 'pages.1.vars');
		$expected = array(
			'pages' => array(
				0 => array('name' => 'main'),
				1 => array('name' => 'about')
			)
		);
		$this->assertEquals($expected, $result);

		$result = Hash::remove($a, 'pages.2.vars');
		$expected = $a;
		$this->assertEquals($expected, $result);
	}

/**
 * Test removing multiple values.
 *
 * @return void
 */
	public function testRemoveMulti() {
		$data = self::articleData();

		$result = Hash::remove($data, '{n}.Article.title');
		$this->assertFalse(isset($result[0]['Article']['title']));
		$this->assertFalse(isset($result[1]['Article']['title']));

		$result = Hash::remove($data, '{n}.Article.{s}');
		$this->assertFalse(isset($result[0]['Article']['id']));
		$this->assertFalse(isset($result[0]['Article']['user_id']));
		$this->assertFalse(isset($result[0]['Article']['title']));
		$this->assertFalse(isset($result[0]['Article']['body']));
	}

/**
 * testCheck method
 *
 * @return void
 */
	public function testCheck() {
		$set = array(
			'My Index 1' => array('First' => 'The first item')
		);
		$this->assertTrue(Hash::check($set, 'My Index 1.First'));
		$this->assertTrue(Hash::check($set, 'My Index 1'));

		$set = array(
			'My Index 1' => array(
				'First' => array(
					'Second' => array(
						'Third' => array(
							'Fourth' => 'Heavy. Nesting.'
						)
					)
				)
			)
		);
		$this->assertTrue(Hash::check($set, 'My Index 1.First.Second'));
		$this->assertTrue(Hash::check($set, 'My Index 1.First.Second.Third'));
		$this->assertTrue(Hash::check($set, 'My Index 1.First.Second.Third.Fourth'));
		$this->assertFalse(Hash::check($set, 'My Index 1.First.Seconds.Third.Fourth'));
	}

/**
 * testCombine method
 *
 * @return void
 */
	public function testCombine() {
		$result = Hash::combine(array(), '{n}.User.id', '{n}.User.Data');
		$this->assertTrue(empty($result));

		$a = self::userData();

		$result = Hash::combine($a, '{n}.User.id');
		$expected = array(2 => null, 14 => null, 25 => null);
		$this->assertEquals($expected, $result);

		$result = Hash::combine($a, '{n}.User.id', '{n}.User.non-existant');
		$expected = array(2 => null, 14 => null, 25 => null);
		$this->assertEquals($expected, $result);

		$result = Hash::combine($a, '{n}.User.id', '{n}.User.Data');
		$expected = array(
			2 => array('user' => 'mariano.iglesias', 'name' => 'Mariano Iglesias'),
			14 => array('user' => 'phpnut', 'name' => 'Larry E. Masters'),
			25 => array('user' => 'gwoo', 'name' => 'The Gwoo'));
		$this->assertEquals($expected, $result);

		$result = Hash::combine($a, '{n}.User.id', '{n}.User.Data.name');
		$expected = array(
			2 => 'Mariano Iglesias',
			14 => 'Larry E. Masters',
			25 => 'The Gwoo');
		$this->assertEquals($expected, $result);
	}

/**
 * test combine() with a group path.
 *
 * @return void
 */
	public function testCombineWithGroupPath() {
		$a = self::userData();

		$result = Hash::combine($a, '{n}.User.id', '{n}.User.Data', '{n}.User.group_id');
		$expected = array(
			1 => array(
				2 => array('user' => 'mariano.iglesias', 'name' => 'Mariano Iglesias'),
				25 => array('user' => 'gwoo', 'name' => 'The Gwoo')
			),
			2 => array(
				14 => array('user' => 'phpnut', 'name' => 'Larry E. Masters')
			)
		);
		$this->assertEquals($expected, $result);

		$result = Hash::combine($a, '{n}.User.id', '{n}.User.Data.name', '{n}.User.group_id');
		$expected = array(
			1 => array(
				2 => 'Mariano Iglesias',
				25 => 'The Gwoo'
			),
			2 => array(
				14 => 'Larry E. Masters'
			)
		);
		$this->assertEquals($expected, $result);

		$result = Hash::combine($a, '{n}.User.id', '{n}.User.Data', '{n}.User.group_id');
		$expected = array(
			1 => array(
				2 => array('user' => 'mariano.iglesias', 'name' => 'Mariano Iglesias'),
				25 => array('user' => 'gwoo', 'name' => 'The Gwoo')
			),
			2 => array(
				14 => array('user' => 'phpnut', 'name' => 'Larry E. Masters')
			)
		);
		$this->assertEquals($expected, $result);

		$result = Hash::combine($a, '{n}.User.id', '{n}.User.Data.name', '{n}.User.group_id');
		$expected = array(
			1 => array(
				2 => 'Mariano Iglesias',
				25 => 'The Gwoo'
			),
			2 => array(
				14 => 'Larry E. Masters'
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test combine with formatting rules.
 *
 * @return void
 */
	public function testCombineWithFormatting() {
		$a = self::userData();

		$result = Hash::combine(
			$a,
			'{n}.User.id',
			array('%1$s: %2$s', '{n}.User.Data.user', '{n}.User.Data.name'),
			'{n}.User.group_id'
		);
		$expected = array(
			1 => array(
				2 => 'mariano.iglesias: Mariano Iglesias',
				25 => 'gwoo: The Gwoo'
			),
			2 => array(
				14 => 'phpnut: Larry E. Masters'
			)
		);
		$this->assertEquals($expected, $result);

		$result = Hash::combine(
			$a,
			array(
				'%s: %s',
				'{n}.User.Data.user',
				'{n}.User.Data.name'
			),
			'{n}.User.id'
		);
		$expected = array(
			'mariano.iglesias: Mariano Iglesias' => 2,
			'phpnut: Larry E. Masters' => 14,
			'gwoo: The Gwoo' => 25
		);
		$this->assertEquals($expected, $result);

		$result = Hash::combine(
			$a,
			array('%1$s: %2$d', '{n}.User.Data.user', '{n}.User.id'),
			'{n}.User.Data.name'
		);
		$expected = array(
			'mariano.iglesias: 2' => 'Mariano Iglesias',
			'phpnut: 14' => 'Larry E. Masters',
			'gwoo: 25' => 'The Gwoo'
		);
		$this->assertEquals($expected, $result);

		$result = Hash::combine(
			$a,
			array('%2$d: %1$s', '{n}.User.Data.user', '{n}.User.id'),
			'{n}.User.Data.name'
		);
		$expected = array(
			'2: mariano.iglesias' => 'Mariano Iglesias',
			'14: phpnut' => 'Larry E. Masters',
			'25: gwoo' => 'The Gwoo'
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testFormat method
 *
 * @return void
 */
	public function testFormat() {
		$data = self::userData();

		$result = Hash::format(
			$data,
			array('{n}.User.Data.user', '{n}.User.id'),
			'%s, %s'
		);
		$expected = array(
			'mariano.iglesias, 2',
			'phpnut, 14',
			'gwoo, 25'
		);
		$this->assertEquals($expected, $result);

		$result = Hash::format(
			$data,
			array('{n}.User.Data.user', '{n}.User.id'),
			'%2$s, %1$s'
		);
		$expected = array(
			'2, mariano.iglesias',
			'14, phpnut',
			'25, gwoo'
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testFormattingNullValues method
 *
 * @return void
 */
	public function testFormatNullValues() {
		$data = array(
			array('Person' => array(
				'first_name' => 'Nate', 'last_name' => 'Abele', 'city' => 'Boston', 'state' => 'MA', 'something' => '42'
			)),
			array('Person' => array(
				'first_name' => 'Larry', 'last_name' => 'Masters', 'city' => 'Boondock', 'state' => 'TN', 'something' => null
			)),
			array('Person' => array(
				'first_name' => 'Garrett', 'last_name' => 'Woodworth', 'city' => 'Venice Beach', 'state' => 'CA', 'something' => null
			))
		);

		$result = Hash::format($data, array('{n}.Person.something'), '%s');
		$expected = array('42', '', '');
		$this->assertEquals($expected, $result);

		$result = Hash::format($data, array('{n}.Person.city', '{n}.Person.something'), '%s, %s');
		$expected = array('Boston, 42', 'Boondock, ', 'Venice Beach, ');
		$this->assertEquals($expected, $result);
	}

/**
 * Test map()
 *
 * @return void
 */
	public function testMap() {
		$data = self::articleData();

		$result = Hash::map($data, '{n}.Article.id', array($this, 'mapCallback'));
		$expected = array(2, 4, 6, 8, 10);
		$this->assertEquals($expected, $result);
	}

	public function testApply() {
		$data = self::articleData();

		$result = Hash::apply($data, '{n}.Article.id', 'array_sum');
		$this->assertEquals(15, $result);
	}

/**
 * Test reduce()
 *
 * @return void
 */
	public function testReduce() {
		$data = self::articleData();

		$result = Hash::reduce($data, '{n}.Article.id', array($this, 'reduceCallback'));
		$this->assertEquals(15, $result);
	}

/**
 * testing method for map callbacks.
 *
 * @param mixed $value
 * @return mixed.
 */
	public function mapCallback($value) {
		return $value * 2;
	}

/**
 * testing method for reduce callbacks.
 *
 * @param mixed $one
 * @param mixed $two
 * @return mixed.
 */
	public function reduceCallback($one, $two) {
		return $one + $two;
	}

/**
 * test Hash nest with a normal model result set. For kicks rely on Hash nest detecting the key names
 * automatically
 *
 * @return void
 */
	public function testNestModel() {
		$input = array(
			array(
				'ModelName' => array(
					'id' => 1,
					'parent_id' => null
				),
			),
			array(
				'ModelName' => array(
					'id' => 2,
					'parent_id' => 1
				),
			),
			array(
				'ModelName' => array(
					'id' => 3,
					'parent_id' => 1
				),
			),
			array(
				'ModelName' => array(
					'id' => 4,
					'parent_id' => 1
				),
			),
			array(
				'ModelName' => array(
					'id' => 5,
					'parent_id' => 1
				),
			),
			array(
				'ModelName' => array(
					'id' => 6,
					'parent_id' => null
				),
			),
			array(
				'ModelName' => array(
					'id' => 7,
					'parent_id' => 6
				),
			),
			array(
				'ModelName' => array(
					'id' => 8,
					'parent_id' => 6
				),
			),
			array(
				'ModelName' => array(
					'id' => 9,
					'parent_id' => 6
				),
			),
			array(
				'ModelName' => array(
					'id' => 10,
					'parent_id' => 6
				)
			)
		);
		$expected = array(
			array(
				'ModelName' => array(
					'id' => 1,
					'parent_id' => null
				),
				'children' => array(
					array(
						'ModelName' => array(
							'id' => 2,
							'parent_id' => 1
						),
						'children' => array()
					),
					array(
						'ModelName' => array(
							'id' => 3,
							'parent_id' => 1
						),
						'children' => array()
					),
					array(
						'ModelName' => array(
							'id' => 4,
							'parent_id' => 1
						),
						'children' => array()
					),
					array(
						'ModelName' => array(
							'id' => 5,
							'parent_id' => 1
						),
						'children' => array()
					),

				)
			),
			array(
				'ModelName' => array(
					'id' => 6,
					'parent_id' => null
				),
				'children' => array(
					array(
						'ModelName' => array(
							'id' => 7,
							'parent_id' => 6
						),
						'children' => array()
					),
					array(
						'ModelName' => array(
							'id' => 8,
							'parent_id' => 6
						),
						'children' => array()
					),
					array(
						'ModelName' => array(
							'id' => 9,
							'parent_id' => 6
						),
						'children' => array()
					),
					array(
						'ModelName' => array(
							'id' => 10,
							'parent_id' => 6
						),
						'children' => array()
					)
				)
			)
		);
		$result = Hash::nest($input);
		$this->assertEquals($expected, $result);
	}

/**
 * test Hash nest with a normal model result set, and a nominated root id
 *
 * @return void
 */
	public function testNestModelExplicitRoot() {
		$input = array(
			array(
				'ModelName' => array(
					'id' => 1,
					'parent_id' => null
				),
			),
			array(
				'ModelName' => array(
					'id' => 2,
					'parent_id' => 1
				),
			),
			array(
				'ModelName' => array(
					'id' => 3,
					'parent_id' => 1
				),
			),
			array(
				'ModelName' => array(
					'id' => 4,
					'parent_id' => 1
				),
			),
			array(
				'ModelName' => array(
					'id' => 5,
					'parent_id' => 1
				),
			),
			array(
				'ModelName' => array(
					'id' => 6,
					'parent_id' => null
				),
			),
			array(
				'ModelName' => array(
					'id' => 7,
					'parent_id' => 6
				),
			),
			array(
				'ModelName' => array(
					'id' => 8,
					'parent_id' => 6
				),
			),
			array(
				'ModelName' => array(
					'id' => 9,
					'parent_id' => 6
				),
			),
			array(
				'ModelName' => array(
					'id' => 10,
					'parent_id' => 6
				)
			)
		);
		$expected = array(
			array(
				'ModelName' => array(
					'id' => 6,
					'parent_id' => null
				),
				'children' => array(
					array(
						'ModelName' => array(
							'id' => 7,
							'parent_id' => 6
						),
						'children' => array()
					),
					array(
						'ModelName' => array(
							'id' => 8,
							'parent_id' => 6
						),
						'children' => array()
					),
					array(
						'ModelName' => array(
							'id' => 9,
							'parent_id' => 6
						),
						'children' => array()
					),
					array(
						'ModelName' => array(
							'id' => 10,
							'parent_id' => 6
						),
						'children' => array()
					)
				)
			)
		);
		$result = Hash::nest($input, array('root' => 6));
		$this->assertEquals($expected, $result);
	}

/**
 * test Hash nest with a 1d array - this method should be able to handle any type of array input
 *
 * @return void
 */
	public function testNest1Dimensional() {
		$input = array(
			array(
				'id' => 1,
				'parent_id' => null
			),
			array(
				'id' => 2,
				'parent_id' => 1
			),
			array(
				'id' => 3,
				'parent_id' => 1
			),
			array(
				'id' => 4,
				'parent_id' => 1
			),
			array(
				'id' => 5,
				'parent_id' => 1
			),
			array(
				'id' => 6,
				'parent_id' => null
			),
			array(
				'id' => 7,
				'parent_id' => 6
			),
			array(
				'id' => 8,
				'parent_id' => 6
			),
			array(
				'id' => 9,
				'parent_id' => 6
			),
			array(
				'id' => 10,
				'parent_id' => 6
			)
		);
		$expected = array(
			array(
				'id' => 1,
				'parent_id' => null,
				'children' => array(
					array(
						'id' => 2,
						'parent_id' => 1,
						'children' => array()
					),
					array(
						'id' => 3,
						'parent_id' => 1,
						'children' => array()
					),
					array(
						'id' => 4,
						'parent_id' => 1,
						'children' => array()
					),
					array(
						'id' => 5,
						'parent_id' => 1,
						'children' => array()
					),

				)
			),
			array(
				'id' => 6,
				'parent_id' => null,
				'children' => array(
					array(
						'id' => 7,
						'parent_id' => 6,
						'children' => array()
					),
					array(
						'id' => 8,
						'parent_id' => 6,
						'children' => array()
					),
					array(
						'id' => 9,
						'parent_id' => 6,
						'children' => array()
					),
					array(
						'id' => 10,
						'parent_id' => 6,
						'children' => array()
					)
				)
			)
		);
		$result = Hash::nest($input, array('idPath' => '{n}.id', 'parentPath' => '{n}.parent_id'));
		$this->assertEquals($expected, $result);
	}

/**
 * test Hash nest with no specified parent data.
 *
 * The result should be the same as the input.
 * For an easier comparison, unset all the empty children arrays from the result
 *
 * @return void
 */
	public function testMissingParent() {
		$input = array(
			array(
				'id' => 1,
			),
			array(
				'id' => 2,
			),
			array(
				'id' => 3,
			),
			array(
				'id' => 4,
			),
			array(
				'id' => 5,
			),
			array(
				'id' => 6,
			),
			array(
				'id' => 7,
			),
			array(
				'id' => 8,
			),
			array(
				'id' => 9,
			),
			array(
				'id' => 10,
			)
		);

		$result = Hash::nest($input, array('idPath' => '{n}.id', 'parentPath' => '{n}.parent_id'));
		foreach ($result as &$row) {
			if (empty($row['children'])) {
				unset($row['children']);
			}
		}
		$this->assertEquals($input, $result);
	}

/**
 * testMergeDiff method
 *
 * @return void
 */
	public function testMergeDiff() {
		$first = array(
			'ModelOne' => array(
				'id' => 1001,
				'field_one' => 'a1.m1.f1',
				'field_two' => 'a1.m1.f2'
			)
		);
		$second = array(
			'ModelTwo' => array(
				'id' => 1002,
				'field_one' => 'a2.m2.f1',
				'field_two' => 'a2.m2.f2'
			)
		);
		$result = Hash::mergeDiff($first, $second);
		$this->assertEquals($result, $first + $second);

		$result = Hash::mergeDiff($first, array());
		$this->assertEquals($result, $first);

		$result = Hash::mergeDiff(array(), $first);
		$this->assertEquals($result, $first);

		$third = array(
			'ModelOne' => array(
				'id' => 1003,
				'field_one' => 'a3.m1.f1',
				'field_two' => 'a3.m1.f2',
				'field_three' => 'a3.m1.f3'
			)
		);
		$result = Hash::mergeDiff($first, $third);
		$expected = array(
			'ModelOne' => array(
				'id' => 1001,
				'field_one' => 'a1.m1.f1',
				'field_two' => 'a1.m1.f2',
				'field_three' => 'a3.m1.f3'
			)
		);
		$this->assertEquals($expected, $result);

		$first = array(
			0 => array('ModelOne' => array('id' => 1001, 'field_one' => 's1.0.m1.f1', 'field_two' => 's1.0.m1.f2')),
			1 => array('ModelTwo' => array('id' => 1002, 'field_one' => 's1.1.m2.f2', 'field_two' => 's1.1.m2.f2'))
		);
		$second = array(
			0 => array('ModelOne' => array('id' => 1001, 'field_one' => 's2.0.m1.f1', 'field_two' => 's2.0.m1.f2')),
			1 => array('ModelTwo' => array('id' => 1002, 'field_one' => 's2.1.m2.f2', 'field_two' => 's2.1.m2.f2'))
		);

		$result = Hash::mergeDiff($first, $second);
		$this->assertEquals($result, $first);

		$third = array(
			0 => array(
				'ModelThree' => array(
					'id' => 1003,
					'field_one' => 's3.0.m3.f1',
					'field_two' => 's3.0.m3.f2'
				)
			)
		);

		$result = Hash::mergeDiff($first, $third);
		$expected = array(
			0 => array(
				'ModelOne' => array(
					'id' => 1001,
					'field_one' => 's1.0.m1.f1',
					'field_two' => 's1.0.m1.f2'
				),
				'ModelThree' => array(
					'id' => 1003,
					'field_one' => 's3.0.m3.f1',
					'field_two' => 's3.0.m3.f2'
				)
			),
			1 => array(
				'ModelTwo' => array(
					'id' => 1002,
					'field_one' => 's1.1.m2.f2',
					'field_two' => 's1.1.m2.f2'
				)
			)
		);
		$this->assertEquals($expected, $result);

		$result = Hash::mergeDiff($first, null);
		$this->assertEquals($result, $first);

		$result = Hash::mergeDiff($first, $second);
		$this->assertEquals($result, $first + $second);
	}

/**
 * Tests Hash::expand
 *
 * @return void
 */
	public function testExpand() {
		$data = array('My', 'Array', 'To', 'Flatten');
		$flat = Hash::flatten($data);
		$result = Hash::expand($flat);
		$this->assertEquals($data, $result);

		$data = array(
			'0.Post.id' => '1', '0.Post.author_id' => '1', '0.Post.title' => 'First Post', '0.Author.id' => '1',
			'0.Author.user' => 'nate', '0.Author.password' => 'foo', '1.Post.id' => '2', '1.Post.author_id' => '3',
			'1.Post.title' => 'Second Post', '1.Post.body' => 'Second Post Body', '1.Author.id' => '3',
			'1.Author.user' => 'larry', '1.Author.password' => null
		);
		$result = Hash::expand($data);
		$expected = array(
			array(
				'Post' => array('id' => '1', 'author_id' => '1', 'title' => 'First Post'),
				'Author' => array('id' => '1', 'user' => 'nate', 'password' => 'foo'),
			),
			array(
				'Post' => array('id' => '2', 'author_id' => '3', 'title' => 'Second Post', 'body' => 'Second Post Body'),
				'Author' => array('id' => '3', 'user' => 'larry', 'password' => null),
			)
		);
		$this->assertEquals($expected, $result);

		$data = array(
			'0/Post/id' => 1,
			'0/Post/name' => 'test post'
		);
		$result = Hash::expand($data, '/');
		$expected = array(
			array(
				'Post' => array(
					'id' => 1,
					'name' => 'test post'
				)
			)
		);
		$this->assertEquals($result, $expected);

		$data = array('a.b.100.a' => null, 'a.b.200.a' => null);
		$expected = array(
			'a' => array(
				'b' => array(
					100 => array('a' => null),
					200 => array('a' => null)
				)
			)
		);
		$result = Hash::expand($data);
		$this->assertEquals($expected, $result);
	}

}
