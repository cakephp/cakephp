<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Utility
 * @since         CakePHP(tm) v 2.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Hash', 'Utility');

/**
 * HashTest
 *
 * @package       Cake.Utility
 */
class HashTest extends CakeTestCase {

/**
 * Data provider
 *
 * @return array
 */
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

/**
 * Data provider
 *
 * @return array
 */
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
 * @return void
 */
	public function testGet() {
		$data = array('abc', 'def');

		$result = Hash::get($data, '0');
		$this->assertEquals('abc', $result);

		$result = Hash::get($data, 0);
		$this->assertEquals('abc', $result);

		$result = Hash::get($data, '1');
		$this->assertEquals('def', $result);

		$data = static::articleData();

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

		$default = array('empty');
		$this->assertEquals($default, Hash::get($data, '5.Article.title', $default));
		$this->assertEquals($default, Hash::get(array(), '5.Article.title', $default));

		$result = Hash::get($data, '1.Article.title.not_there');
		$this->assertNull($result);

		$result = Hash::get($data, '1.Article');
		$this->assertEquals($data[1]['Article'], $result);

		$result = Hash::get($data, array('1', 'Article'));
		$this->assertEquals($data[1]['Article'], $result);
	}

/**
 * Test that get() can extract '' key data.
 *
 * @return void
 */
	public function testGetEmptyKey() {
		$data = array(
			true => 'true value',
			false => 'false value',
			'' => 'some value',
		);
		$this->assertSame($data[''], Hash::get($data, ''));
		$this->assertSame($data[false], Hash::get($data, false));
		$this->assertSame($data[true], Hash::get($data, true));
	}

/**
 * Test get() with an invalid path
 *
 * @expectedException InvalidArgumentException
 * @return void
 */
	public function testGetInvalidPath() {
		Hash::get(array('one' => 'two'), new StdClass());
	}

/**
 * Test testGetNullPath()
 *
 * @return void
 */
	public function testGetNullPath() {
		$result = Hash::get(array('one' => 'two'), null, '-');
		$this->assertEquals('-', $result);

		$result = Hash::get(array('one' => 'two'), '', '-');
		$this->assertEquals('-', $result);
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
		$data = array();
		$result = Hash::maxDimensions($data);
		$this->assertEquals(0, $result);

		$data = array('a', 'b');
		$result = Hash::maxDimensions($data);
		$this->assertEquals(1, $result);

		$data = array('1' => '1.1', '2', '3' => array('3.1' => '3.1.1'));
		$result = Hash::maxDimensions($data);
		$this->assertEquals($result, 2);

		$data = array(
			'1' => array('1.1' => '1.1.1'),
			'2',
			'3' => array('3.1' => array('3.1.1' => '3.1.1.1'))
		);
		$result = Hash::maxDimensions($data);
		$this->assertEquals($result, 3);

		$data = array(
			'1' => array(
				'1.1' => '1.1.1',
				'1.2' => array(
					'1.2.1' => array(
						'1.2.1.1',
						array('1.2.2.1')
					)
				)
			),
			'2' => array('2.1' => '2.1.1')
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
		$this->assertEquals($expected, Hash::merge($a, $b));
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

		$a = array(0 => 'test', 'string' => null);
		$this->assertTrue(Hash::contains($a, array('string' => null)));

		$a = array(0 => 'test', 'string' => null);
		$this->assertTrue(Hash::contains($a, array('test')));
	}

/**
 * testFilter method
 *
 * @return void
 */
	public function testFilter() {
		$result = Hash::filter(array(
			'0',
			false,
			true,
			0,
			0.0,
			array('one thing', 'I can tell you', 'is you got to be', false)
		));
		$expected = array(
			'0',
			2 => true,
			3 => 0,
			4 => 0.0,
			5 => array('one thing', 'I can tell you', 'is you got to be')
		);
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

		$data = array(2.4, 1, 0, -1, -2);
		$this->assertTrue(Hash::numeric($data));
	}

/**
 * Test simple paths.
 *
 * @return void
 */
	public function testExtractBasic() {
		$data = static::articleData();

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
		$data = static::articleData();
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
		$data = static::articleData();
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
 * Test wildcard matcher
 *
 * @return void
 */
	public function testExtractWildcard() {
		$data = array(
			'02000009C5560001' => array('name' => 'Mr. Alphanumeric'),
			'2300000918020101' => array('name' => 'Mr. Numeric'),
			'390000096AB30001' => array('name' => 'Mrs. Alphanumeric'),
			'stuff' => array('name' => 'Ms. Word'),
			123 => array('name' => 'Mr. Number'),
			true => array('name' => 'Ms. Bool'),
		);
		$result = Hash::extract($data, '{*}.name');
		$expected = array(
			'Mr. Alphanumeric',
			'Mr. Numeric',
			'Mrs. Alphanumeric',
			'Ms. Word',
			'Mr. Number',
			'Ms. Bool',
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test the attribute presense selector.
 *
 * @return void
 */
	public function testExtractAttributePresence() {
		$data = static::articleData();

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
		$data = static::articleData();

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
 * Test extracting based on attributes with boolean values.
 *
 * @return void
 */
	public function testExtractAttributeBoolean() {
		$users = array(
			array(
				'id' => 2,
				'username' => 'johndoe',
				'active' => true
			),
			array(
				'id' => 5,
				'username' => 'kevin',
				'active' => true
			),
			array(
				'id' => 9,
				'username' => 'samantha',
				'active' => false
			),
		);
		$result = Hash::extract($users, '{n}[active=0]');
		$this->assertCount(1, $result);
		$this->assertEquals($users[2], $result[0]);

		$result = Hash::extract($users, '{n}[active=false]');
		$this->assertCount(1, $result);
		$this->assertEquals($users[2], $result[0]);

		$result = Hash::extract($users, '{n}[active=1]');
		$this->assertCount(2, $result);
		$this->assertEquals($users[0], $result[0]);
		$this->assertEquals($users[1], $result[1]);

		$result = Hash::extract($users, '{n}[active=true]');
		$this->assertCount(2, $result);
		$this->assertEquals($users[0], $result[0]);
		$this->assertEquals($users[1], $result[1]);
	}

/**
 * Test that attribute matchers don't cause errors on scalar data.
 *
 * @return void
 */
	public function testExtractAttributeEqualityOnScalarValue() {
		$data = array(
			'Entity' => array(
				'id' => 1,
				'data1' => 'value',
			)
		);
		$result = Hash::extract($data, 'Entity[id=1].data1');
		$this->assertEquals(array('value'), $result);

		$data = array('Entity' => false );
		$result = Hash::extract($data, 'Entity[id=1].data1');
		$this->assertEquals(array(), $result);
	}

/**
 * Test comparison operators.
 *
 * @return void
 */
	public function testExtractAttributeComparison() {
		$data = static::articleData();

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
		$data = static::articleData();

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
		$data = static::articleData();

		$result = Hash::extract($data, '{n}.Article[title=/^First/]');
		$expected = array($data[0]['Article']);
		$this->assertEquals($expected, $result);

		$result = Hash::extract($data, '{n}.Article[title=/^Fir[a-z]+/]');
		$expected = array($data[0]['Article']);
		$this->assertEquals($expected, $result);
	}

/**
 * Test that extract() + matching can hit null things.
 *
 * @return void
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
		$result = Hash::sort(array(), '{n}.name');
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
		$a = Hash::sort($a, '{n}.Friend.{n}.name');
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
		$this->assertSame($a, $b);

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
		$this->assertSame($expected, $result);

		$a = array(
			'SU' => array(
				'total_fulfillable' => 2
			),
			'AA' => array(
				'total_fulfillable' => 1
			),
			'LX' => array(
				'total_fulfillable' => 0
			),
			'BL' => array(
				'total_fulfillable' => 3
			),
		);
		$expected = array(
			'LX' => array(
				'total_fulfillable' => 0
			),
			'AA' => array(
				'total_fulfillable' => 1
			),
			'SU' => array(
				'total_fulfillable' => 2
			),
			'BL' => array(
				'total_fulfillable' => 3
			),
		);
		$result = Hash::sort($a, '{s}.total_fulfillable', 'asc');
		$this->assertSame($expected, $result);
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
 * Test natural sorting ignoring case.
 *
 * @return void
 */
	public function testSortNaturalIgnoreCase() {
		if (version_compare(PHP_VERSION, '5.4.0', '<')) {
			$this->markTestSkipped('SORT_NATURAL is available since PHP 5.4.');
		}
		$items = array(
			array('Item' => array('image' => 'img1.jpg')),
			array('Item' => array('image' => 'img99.jpg')),
			array('Item' => array('image' => 'Img12.jpg')),
			array('Item' => array('image' => 'Img10.jpg')),
			array('Item' => array('image' => 'img2.jpg')),
		);
		$result = Hash::sort($items, '{n}.Item.image', 'desc', array('type' => 'natural', 'ignoreCase' => true));
		$expected = array(
			array('Item' => array('image' => 'img99.jpg')),
			array('Item' => array('image' => 'Img12.jpg')),
			array('Item' => array('image' => 'Img10.jpg')),
			array('Item' => array('image' => 'img2.jpg')),
			array('Item' => array('image' => 'img1.jpg')),
		);
		$this->assertEquals($expected, $result);

		$result = Hash::sort($items, '{n}.Item.image', 'asc', array('type' => 'natural', 'ignoreCase' => true));
		$expected = array(
			array('Item' => array('image' => 'img1.jpg')),
			array('Item' => array('image' => 'img2.jpg')),
			array('Item' => array('image' => 'Img10.jpg')),
			array('Item' => array('image' => 'Img12.jpg')),
			array('Item' => array('image' => 'img99.jpg')),
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test that sort() with 'natural' type will fallback to 'regular' as SORT_NATURAL is introduced in PHP 5.4
 *
 * @return void
 */
	public function testSortNaturalFallbackToRegular() {
		if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
			$this->markTestSkipped('Skipping SORT_NATURAL fallback test on PHP >= 5.4');
		}

		$a = array(
			0 => array('Person' => array('name' => 'Jeff')),
			1 => array('Shirt' => array('color' => 'black'))
		);
		$b = array(
			0 => array('Shirt' => array('color' => 'black')),
			1 => array('Person' => array('name' => 'Jeff')),
		);
		$sorted = Hash::sort($a, '{n}.Person.name', 'asc', 'natural');
		$this->assertEquals($sorted, $b);
	}

/**
 * Test sort() with locale option.
 *
 * @return void
 */
	public function testSortLocale() {
		// get the current locale
		$oldLocale = setlocale(LC_COLLATE, '0');

		$updated = setlocale(LC_COLLATE, 'de_DE.utf8');
		$this->skipIf($updated === false, 'Could not set locale to de_DE.utf8, skipping test.');

		$items = array(
			array('Item' => array('entry' => 'Übergabe')),
			array('Item' => array('entry' => 'Ostfriesland')),
			array('Item' => array('entry' => 'Äpfel')),
			array('Item' => array('entry' => 'Apfel')),
		);

		$result = Hash::sort($items, '{n}.Item.entry', 'asc', 'locale');
		$expected = array(
			array('Item' => array('entry' => 'Apfel')),
			array('Item' => array('entry' => 'Äpfel')),
			array('Item' => array('entry' => 'Ostfriesland')),
			array('Item' => array('entry' => 'Übergabe')),
		);
		$this->assertEquals($expected, $result);

		// change to the original locale
		setlocale(LC_COLLATE, $oldLocale);
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
	public function testSortStringKeys() {
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
 * test sorting with string ignoring case.
 *
 * @return void
 */
	public function testSortStringIgnoreCase() {
		$toSort = array(
			array('Item' => array('name' => 'bar')),
			array('Item' => array('name' => 'Baby')),
			array('Item' => array('name' => 'Baz')),
			array('Item' => array('name' => 'bat')),
		);
		$sorted = Hash::sort($toSort, '{n}.Item.name', 'asc', array('type' => 'string', 'ignoreCase' => true));
		$expected = array(
			array('Item' => array('name' => 'Baby')),
			array('Item' => array('name' => 'bar')),
			array('Item' => array('name' => 'bat')),
			array('Item' => array('name' => 'Baz')),
		);
		$this->assertEquals($expected, $sorted);
	}

/**
 * test regular sorting ignoring case.
 *
 * @return void
 */
	public function testSortRegularIgnoreCase() {
		$toSort = array(
			array('Item' => array('name' => 'bar')),
			array('Item' => array('name' => 'Baby')),
			array('Item' => array('name' => 'Baz')),
			array('Item' => array('name' => 'bat')),
		);
		$sorted = Hash::sort($toSort, '{n}.Item.name', 'asc', array('type' => 'regular', 'ignoreCase' => true));
		$expected = array(
			array('Item' => array('name' => 'Baby')),
			array('Item' => array('name' => 'bar')),
			array('Item' => array('name' => 'bat')),
			array('Item' => array('name' => 'Baz')),
		);
		$this->assertEquals($expected, $sorted);
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

		$a = array(
			'foo' => array('bar' => 'baz')
		);
		$result = Hash::insert($a, 'some.0123.path', array('foo' => array('bar' => 'baz')));
		$expected = array('foo' => array('bar' => 'baz'));
		$this->assertEquals($expected, Hash::get($result, 'some.0123.path'));
	}

/**
 * Test inserting with multiple values.
 *
 * @return void
 */
	public function testInsertMulti() {
		$data = static::articleData();

		$result = Hash::insert($data, '{n}.Article.insert', 'value');
		$this->assertEquals('value', $result[0]['Article']['insert']);
		$this->assertEquals('value', $result[1]['Article']['insert']);

		$result = Hash::insert($data, '{n}.Comment.{n}.insert', 'value');
		$this->assertEquals('value', $result[0]['Comment'][0]['insert']);
		$this->assertEquals('value', $result[0]['Comment'][1]['insert']);

		$data = array(
			0 => array('Item' => array('id' => 1, 'title' => 'first')),
			1 => array('Item' => array('id' => 2, 'title' => 'second')),
			2 => array('Item' => array('id' => 3, 'title' => 'third')),
			3 => array('Item' => array('id' => 4, 'title' => 'fourth')),
			4 => array('Item' => array('id' => 5, 'title' => 'fifth')),
		);
		$result = Hash::insert($data, '{n}.Item[id=/\b2|\b4/]', array('test' => 2));
		$expected = array(
			0 => array('Item' => array('id' => 1, 'title' => 'first')),
			1 => array('Item' => array('id' => 2, 'title' => 'second', 'test' => 2)),
			2 => array('Item' => array('id' => 3, 'title' => 'third')),
			3 => array('Item' => array('id' => 4, 'title' => 'fourth', 'test' => 2)),
			4 => array('Item' => array('id' => 5, 'title' => 'fifth')),
		);
		$this->assertEquals($expected, $result);
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

		$a = array(
			0 => array(
				'name' => 'pages'
			),
			1 => array(
				'name' => 'files'
			)
		);

		$result = Hash::remove($a, '{n}[name=files]');
		$expected = array(
			0 => array(
				'name' => 'pages'
			)
		);
		$this->assertEquals($expected, $result);

		$array = array(
			0 => 'foo',
			1 => array(
				0 => 'baz'
			)
		);
		$expected = $array;
		$result = Hash::remove($array, '{n}.part');
		$this->assertEquals($expected, $result);
		$result = Hash::remove($array, '{n}.{n}.part');
		$this->assertEquals($expected, $result);
	}

/**
 * Test removing multiple values.
 *
 * @return void
 */
	public function testRemoveMulti() {
		$data = static::articleData();

		$result = Hash::remove($data, '{n}.Article.title');
		$this->assertFalse(isset($result[0]['Article']['title']));
		$this->assertFalse(isset($result[1]['Article']['title']));

		$result = Hash::remove($data, '{n}.Article.{s}');
		$this->assertFalse(isset($result[0]['Article']['id']));
		$this->assertFalse(isset($result[0]['Article']['user_id']));
		$this->assertFalse(isset($result[0]['Article']['title']));
		$this->assertFalse(isset($result[0]['Article']['body']));

		$data = array(
			0 => array('Item' => array('id' => 1, 'title' => 'first')),
			1 => array('Item' => array('id' => 2, 'title' => 'second')),
			2 => array('Item' => array('id' => 3, 'title' => 'third')),
			3 => array('Item' => array('id' => 4, 'title' => 'fourth')),
			4 => array('Item' => array('id' => 5, 'title' => 'fifth')),
		);

		$result = Hash::remove($data, '{n}.Item[id=/\b2|\b4/]');
		$expected = array(
			0 => array('Item' => array('id' => 1, 'title' => 'first')),
			2 => array('Item' => array('id' => 3, 'title' => 'third')),
			4 => array('Item' => array('id' => 5, 'title' => 'fifth')),
		);
		$this->assertEquals($expected, $result);
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

		$a = static::userData();

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
 * test combine() giving errors on key/value length mismatches.
 *
 * @expectedException CakeException
 * @return void
 */
	public function testCombineErrorMissingValue() {
		$data = array(
			array('User' => array('id' => 1, 'name' => 'mark')),
			array('User' => array('name' => 'jose')),
		);
		Hash::combine($data, '{n}.User.id', '{n}.User.name');
	}

/**
 * test combine() giving errors on key/value length mismatches.
 *
 * @expectedException CakeException
 * @return void
 */
	public function testCombineErrorMissingKey() {
		$data = array(
			array('User' => array('id' => 1, 'name' => 'mark')),
			array('User' => array('id' => 2)),
		);
		Hash::combine($data, '{n}.User.id', '{n}.User.name');
	}

/**
 * test combine() with a group path.
 *
 * @return void
 */
	public function testCombineWithGroupPath() {
		$a = static::userData();

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
		$a = static::userData();

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
		$data = static::userData();

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
		$data = static::articleData();

		$result = Hash::map($data, '{n}.Article.id', array($this, 'mapCallback'));
		$expected = array(2, 4, 6, 8, 10);
		$this->assertEquals($expected, $result);
	}

/**
 * testApply
 *
 * @return void
 */
	public function testApply() {
		$data = static::articleData();

		$result = Hash::apply($data, '{n}.Article.id', 'array_sum');
		$this->assertEquals(15, $result);
	}

/**
 * Test reduce()
 *
 * @return void
 */
	public function testReduce() {
		$data = static::articleData();

		$result = Hash::reduce($data, '{n}.Article.id', array($this, 'reduceCallback'));
		$this->assertEquals(15, $result);
	}

/**
 * testing method for map callbacks.
 *
 * @param mixed $value Value
 * @return mixed
 */
	public function mapCallback($value) {
		return $value * 2;
	}

/**
 * testing method for reduce callbacks.
 *
 * @param mixed $one First param
 * @param mixed $two Second param
 * @return mixed
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
 * Tests that nest() throws an InvalidArgumentException when providing an invalid input.
 *
 * @expectedException InvalidArgumentException
 * @return void
 */
	public function testNestInvalid() {
		$input = array(
			array(
				'ParentCategory' => array(
					'id' => '1',
					'name' => 'Lorem ipsum dolor sit amet',
					'parent_id' => '1'
				)
			)
		);
		Hash::nest($input);
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
		$this->assertEquals($expected, $result);

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

/**
 * Test that flattening a large complex set doesn't loop forever.
 *
 * @return void
 */
	public function testFlattenInfiniteLoop() {
		$data = array(
			'Order.ASI' => '0',
			'Order.Accounting' => '0',
			'Order.Admin' => '0',
			'Order.Art' => '0',
			'Order.ArtChecker' => '0',
			'Order.Canned' => '0',
			'Order.Customer_Tags' => '',
			'Order.Embroidery' => '0',
			'Order.Item.0.Product.style_number' => 'a11222',
			'Order.Item.0.Product.slug' => 'a11222',
			'Order.Item.0.Product._id' => '4ff8b8d3d7bbe8ad30000000',
			'Order.Item.0.Product.Color.slug' => 'kelly_green',
			'Order.Item.0.Product.ColorSizes.0.Color.color' => 'Sport Grey',
			'Order.Item.0.Product.ColorSizes.0.Color.slug' => 'sport_grey',
			'Order.Item.0.Product.ColorSizes.1.Color.color' => 'Kelly Green',
			'Order.Item.0.Product.ColorSizes.1.Color.slug' => 'kelly_green',
			'Order.Item.0.Product.ColorSizes.2.Color.color' => 'Orange',
			'Order.Item.0.Product.ColorSizes.2.Color.slug' => 'orange',
			'Order.Item.0.Product.ColorSizes.3.Color.color' => 'Yellow Haze',
			'Order.Item.0.Product.ColorSizes.3.Color.slug' => 'yellow_haze',
			'Order.Item.0.Product.brand' => 'OUTER BANKS',
			'Order.Item.0.Product.style' => 'T-shirt',
			'Order.Item.0.Product.description' => 'uhiuhuih oin ooi ioo ioio',
			'Order.Item.0.Product.sizes.0.Size.qty' => '',
			'Order.Item.0.Product.sizes.0.Size.size' => '0-3mo',
			'Order.Item.0.Product.sizes.0.Size.id' => '38',
			'Order.Item.0.Product.sizes.1.Size.qty' => '',
			'Order.Item.0.Product.sizes.1.Size.size' => '3-6mo',
			'Order.Item.0.Product.sizes.1.Size.id' => '39',
			'Order.Item.0.Product.sizes.2.Size.qty' => '78',
			'Order.Item.0.Product.sizes.2.Size.size' => '6-9mo',
			'Order.Item.0.Product.sizes.2.Size.id' => '40',
			'Order.Item.0.Product.sizes.3.Size.qty' => '',
			'Order.Item.0.Product.sizes.3.Size.size' => '6-12mo',
			'Order.Item.0.Product.sizes.3.Size.id' => '41',
			'Order.Item.0.Product.sizes.4.Size.qty' => '',
			'Order.Item.0.Product.sizes.4.Size.size' => '12-18mo',
			'Order.Item.0.Product.sizes.4.Size.id' => '42',
			'Order.Item.0.Art.imprint_locations.0.id' => 2,
			'Order.Item.0.Art.imprint_locations.0.name' => 'Left Chest',
			'Order.Item.0.Art.imprint_locations.0.imprint_type.id' => 7,
			'Order.Item.0.Art.imprint_locations.0.imprint_type.type' => 'Embroidery',
			'Order.Item.0.Art.imprint_locations.0.art' => '',
			'Order.Item.0.Art.imprint_locations.0.num_colors' => 3,
			'Order.Item.0.Art.imprint_locations.0.description' => 'Wooo! This is Embroidery!!',
			'Order.Item.0.Art.imprint_locations.0.lines.0' => 'Platen',
			'Order.Item.0.Art.imprint_locations.0.lines.1' => 'Logo',
			'Order.Item.0.Art.imprint_locations.0.height' => 4,
			'Order.Item.0.Art.imprint_locations.0.width' => 5,
			'Order.Item.0.Art.imprint_locations.0.stitch_density' => 'Light',
			'Order.Item.0.Art.imprint_locations.0.metallic_thread' => true,
			'Order.Item.0.Art.imprint_locations.1.id' => 4,
			'Order.Item.0.Art.imprint_locations.1.name' => 'Full Back',
			'Order.Item.0.Art.imprint_locations.1.imprint_type.id' => 6,
			'Order.Item.0.Art.imprint_locations.1.imprint_type.type' => 'Screenprinting',
			'Order.Item.0.Art.imprint_locations.1.art' => '',
			'Order.Item.0.Art.imprint_locations.1.num_colors' => 3,
			'Order.Item.0.Art.imprint_locations.1.description' => 'Wooo! This is Screenprinting!!',
			'Order.Item.0.Art.imprint_locations.1.lines.0' => 'Platen',
			'Order.Item.0.Art.imprint_locations.1.lines.1' => 'Logo',
			'Order.Item.0.Art.imprint_locations.2.id' => 26,
			'Order.Item.0.Art.imprint_locations.2.name' => 'HS - JSY Name Below',
			'Order.Item.0.Art.imprint_locations.2.imprint_type.id' => 9,
			'Order.Item.0.Art.imprint_locations.2.imprint_type.type' => 'Names',
			'Order.Item.0.Art.imprint_locations.2.description' => 'Wooo! This is Names!!',
			'Order.Item.0.Art.imprint_locations.2.sizes.S.0.active' => 1,
			'Order.Item.0.Art.imprint_locations.2.sizes.S.0.name' => 'Benjamin Talavera',
			'Order.Item.0.Art.imprint_locations.2.sizes.S.0.color' => 'Red',
			'Order.Item.0.Art.imprint_locations.2.sizes.S.0.height' => '3',
			'Order.Item.0.Art.imprint_locations.2.sizes.S.0.layout' => 'Arched',
			'Order.Item.0.Art.imprint_locations.2.sizes.S.0.style' => 'Classic',
			'Order.Item.0.Art.imprint_locations.2.sizes.S.1.active' => 0,
			'Order.Item.0.Art.imprint_locations.2.sizes.S.1.name' => 'Rishi Narayan',
			'Order.Item.0.Art.imprint_locations.2.sizes.S.1.color' => 'Cardinal',
			'Order.Item.0.Art.imprint_locations.2.sizes.S.1.height' => '4',
			'Order.Item.0.Art.imprint_locations.2.sizes.S.1.layout' => 'Straight',
			'Order.Item.0.Art.imprint_locations.2.sizes.S.1.style' => 'Team US',
			'Order.Item.0.Art.imprint_locations.2.sizes.M.0.active' => 1,
			'Order.Item.0.Art.imprint_locations.2.sizes.M.0.name' => 'Brandon Plasters',
			'Order.Item.0.Art.imprint_locations.2.sizes.M.0.color' => 'Red',
			'Order.Item.0.Art.imprint_locations.2.sizes.M.0.height' => '3',
			'Order.Item.0.Art.imprint_locations.2.sizes.M.0.layout' => 'Arched',
			'Order.Item.0.Art.imprint_locations.2.sizes.M.0.style' => 'Classic',
			'Order.Item.0.Art.imprint_locations.2.sizes.M.1.active' => 0,
			'Order.Item.0.Art.imprint_locations.2.sizes.M.1.name' => 'Andrew Reed',
			'Order.Item.0.Art.imprint_locations.2.sizes.M.1.color' => 'Cardinal',
			'Order.Item.0.Art.imprint_locations.2.sizes.M.1.height' => '4',
			'Order.Item.0.Art.imprint_locations.2.sizes.M.1.layout' => 'Straight',
			'Order.Item.0.Art.imprint_locations.2.sizes.M.1.style' => 'Team US',
			'Order.Job.0._id' => 'job-1',
			'Order.Job.0.type' => 'screenprinting',
			'Order.Job.0.postPress' => 'job-2',
			'Order.Job.1._id' => 'job-2',
			'Order.Job.1.type' => 'embroidery',
			'Order.Postpress' => '0',
			'Order.PriceAdjustment.0._id' => 'price-adjustment-1',
			'Order.PriceAdjustment.0.adjustment' => '-20',
			'Order.PriceAdjustment.0.adjustment_type' => 'percent',
			'Order.PriceAdjustment.0.type' => 'grand_total',
			'Order.PriceAdjustment.1.adjustment' => '20',
			'Order.PriceAdjustment.1.adjustment_type' => 'flat',
			'Order.PriceAdjustment.1.min-items' => '10',
			'Order.PriceAdjustment.1.type' => 'min-items',
			'Order.PriceAdjustment.1._id' => 'another-test-adjustment',
			'Order.Purchasing' => '0',
			'Order.QualityControl' => '0',
			'Order.Receiving' => '0',
			'Order.ScreenPrinting' => '0',
			'Order.Stage.art_approval' => 0,
			'Order.Stage.draft' => 1,
			'Order.Stage.quote' => 1,
			'Order.Stage.order' => 1,
			'Order.StoreLiason' => '0',
			'Order.Tag_UI_Email' => '',
			'Order.Tags' => '',
			'Order._id' => 'test-2',
			'Order.add_print_location' => '',
			'Order.created' => '2011-Dec-29 05:40:18',
			'Order.force_admin' => '0',
			'Order.modified' => '2012-Jul-25 01:24:49',
			'Order.name' => 'towering power',
			'Order.order_id' => '135961',
			'Order.slug' => 'test-2',
			'Order.title' => 'test job 2',
			'Order.type' => 'ttt'
		);
		$expanded = Hash::expand($data);
		$flattened = Hash::flatten($expanded);
		$this->assertEquals($data, $flattened);
	}

}
