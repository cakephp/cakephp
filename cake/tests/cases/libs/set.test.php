<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs
 * @since			CakePHP(tm) v 1.2.0.4206
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', 'Set');

/**
 * UnitTestCase for the Set class
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs
 */
class SetTest extends UnitTestCase {
	function testNumericKeyExtraction() {
		$data = array('plugin' => null, 'controller' => '', 'action' => '', 1, 'whatever');
		$this->assertIdentical(Set::extract($data, '{n}'), array(1, 'whatever'));
		$this->assertIdentical(Set::diff($data, Set::extract($data, '{n}')), array('plugin' => null, 'controller' => '', 'action' => ''));
	}

	function testEnum() {
		$result = Set::enum(1, 'one, two');
		$this->assertIdentical($result, 'two');
		$result = Set::enum(2, 'one, two');
		$this->assertNull($result);
		
		$result = Set::enum(1, array('one', 'two'));
		$this->assertIdentical($result, 'two');
		$result = Set::enum(2, array('one', 'two'));
		$this->assertNull($result);
		
		$result = Set::enum('first', array('first' => 'one', 'second' => 'two'));
		$this->assertIdentical($result, 'one');
		$result = Set::enum('third', array('first' => 'one', 'second' => 'two'));
		$this->assertNull($result);
		
		$result = Set::enum('no', array('no' => 0, 'yes' => 1));
		$this->assertIdentical($result, 0);
		$result = Set::enum('not sure', array('no' => 0, 'yes' => 1));
		$this->assertNull($result);
	}

	function testFilter() {
		$result = Set::filter(array('0', false, true, 0, array('one thing', 'I can tell you', 'is you got to be', false)));
		$expected = array('0', 2 => true, 3 => 0, 4 => array('one thing', 'I can tell you', 'is you got to be', false));
		$this->assertIdentical($result, $expected);
	}

	function testNumericArrayCheck() {
		$data = array('one', 'two', 'three', 'four', 'five');
		$this->assertTrue(Set::numeric(array_keys($data)));

		$data = array(1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five');
		$this->assertTrue(Set::numeric(array_keys($data)));

		$data = array('1' => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five');
		$this->assertTrue(Set::numeric(array_keys($data)));

		$data = array('one', 2 => 'two', 3 => 'three', 4 => 'four', 'a' => 'five');
		$this->assertFalse(Set::numeric(array_keys($data)));
	}

	function testKeyCheck() {
		$data = array('Multi' => array('dimensonal' => array('array')));
		$this->assertTrue(Set::check($data, 'Multi.dimensonal'));
		$this->assertFalse(Set::check($data, 'Multi.dimensonal.array'));

		$data = array(
			array(
				'Article' => array('id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'),
				'User' => array('id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'),
				'Comment' => array(
					array('id' => '1', 'article_id' => '1', 'user_id' => '2', 'comment' => 'First Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31'),
					array('id' => '2', 'article_id' => '1', 'user_id' => '4', 'comment' => 'Second Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31'),
				),
				'Tag' => array(
					array('id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
					array('id' => '2', 'tag' => 'tag2', 'created' => '2007-03-18 12:24:23', 'updated' => '2007-03-18 12:26:31')
				)
			),
			array(
				'Article' => array('id' => '3', 'user_id' => '1', 'title' => 'Third Article', 'body' => 'Third Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'),
				'User' => array('id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'),
				'Comment' => array(),
				'Tag' => array()
			)
		);
		$this->assertTrue(Set::check($data, '0.Article.user_id'));
		$this->assertTrue(Set::check($data, '0.Comment.0.id'));
		$this->assertFalse(Set::check($data, '0.Comment.0.id.0'));
		$this->assertTrue(Set::check($data, '0.Article.user_id'));
		$this->assertFalse(Set::check($data, '0.Article.user_id.a'));
	}

	function testMerge() {
		$r = Set::merge(array('foo'));
		$this->assertIdentical($r, array('foo'));

		$r = Set::merge('foo');
		$this->assertIdentical($r, array('foo'));

		$r = Set::merge('foo', 'bar');
		$this->assertIdentical($r, array('foo', 'bar'));
		
		if (substr(phpversion(), 0, 1) >= 5) {
			$r = eval('class StaticSetCaller{static function merge($a, $b){return Set::merge($a, $b);}} return StaticSetCaller::merge("foo", "bar");');
			$this->assertIdentical($r, array('foo', 'bar'));
		}
		
		$r = Set::merge('foo', array('user' => 'bob', 'no-bar'), 'bar');
		$this->assertIdentical($r, array('foo', 'user' => 'bob', 'no-bar', 'bar'));

		$a = array('foo', 'foo2');
		$b = array('bar', 'bar2');
		$this->assertIdentical(Set::merge($a, $b), array('foo', 'foo2', 'bar', 'bar2'));

		$a = array('foo' => 'bar', 'bar' => 'foo');
		$b = array('foo' => 'no-bar', 'bar' => 'no-foo');
		$this->assertIdentical(Set::merge($a, $b), array('foo' => 'no-bar', 'bar' => 'no-foo'));

		$a = array('users' => array('bob', 'jim'));
		$b = array('users' => array('lisa', 'tina'));
		$this->assertIdentical(Set::merge($a, $b), array('users' => array('bob', 'jim', 'lisa', 'tina')));

		$a = array('users' => array('jim', 'bob'));
		$b = array('users' => 'none');
		$this->assertIdentical(Set::merge($a, $b), array('users' => 'none'));

		$a = array('users' => array('lisa' => array('id' => 5, 'pw' => 'secret')), 'cakephp');
		$b = array('users' => array('lisa' => array('pw' => 'new-pass', 'age' => 23)), 'ice-cream');
		$this->assertIdentical(Set::merge($a, $b), array('users' => array('lisa' => array('id' => 5, 'pw' => 'new-pass', 'age' => 23)), 'cakephp', 'ice-cream'));

		$c = array('users' => array('lisa' => array('pw' => 'you-will-never-guess', 'age' => 25, 'pet' => 'dog')), 'chocolate');
		$expected = array('users' => array('lisa' => array('id' => 5, 'pw' => 'you-will-never-guess', 'age' => 25, 'pet' => 'dog')), 'cakephp', 'ice-cream', 'chocolate');
		$this->assertIdentical(Set::merge($a, $b, $c), $expected);

		$this->assertIdentical(Set::merge($a, $b, array(), $c), $expected);

		$Set =& new Set($a);
		$r = $Set->merge($b, array(), $c);
		$this->assertIdentical($r, $expected);
		$this->assertIdentical($Set->value, $expected);

		unset($Set);

		$Set =& new Set();
		$SetA =& new Set($a);
		$SetB =& new Set($b);
		$SetC =& new Set($c);

		$r = $Set->merge($SetA, $SetB, $SetC);
		$this->assertIdentical($r, $expected);
		$this->assertIdentical($Set->value, $expected);

		$a = array('Tree', 'CounterCache',
				'Upload' => array('folder' => 'products',
					'fields' => array('image_1_id', 'image_2_id', 'image_3_id', 'image_4_id', 'image_5_id')));
		$b =  array('Cacheable' => array('enabled' => false),
				'Limit',
				'Bindable',
				'Validator',
				'Transactional');

		$expected = array('Tree', 'CounterCache',
				'Upload' => array('folder' => 'products',
					'fields' => array('image_1_id', 'image_2_id', 'image_3_id', 'image_4_id', 'image_5_id')),
				'Cacheable' => array('enabled' => false),
				'Limit',
				'Bindable',
				'Validator',
				'Transactional');

		$this->assertIdentical(Set::merge($a, $b), $expected);

		$expected = array('Tree' => null, 'CounterCache' => null,
				'Upload' => array('folder' => 'products',
					'fields' => array('image_1_id', 'image_2_id', 'image_3_id', 'image_4_id', 'image_5_id')),
				'Cacheable' => array('enabled' => false),
				'Limit' => null,
				'Bindable' => null,
				'Validator' => null,
				'Transactional' => null);

		$this->assertIdentical(Set::normalize(Set::merge($a, $b)), $expected);
	}

	function testSort() {
		$a = array(
			0 => array('Person' => array('name' => 'Jeff'), 'Friend' => array(array('name' => 'Nate'))),
			1 => array('Person' => array('name' => 'Tracy'),'Friend' => array(array('name' => 'Lindsay')))
		);
		$b = array(
			0 => array('Person' => array('name' => 'Tracy'),'Friend' => array(array('name' => 'Lindsay'))),
			1 => array('Person' => array('name' => 'Jeff'), 'Friend' => array(array('name' => 'Nate')))

		);
		$a = Set::sort($a, '{n}.Friend.{n}.name', 'asc');
		$this->assertIdentical($a, $b);

		$b = array(
			0 => array('Person' => array('name' => 'Jeff'), 'Friend' => array(array('name' => 'Nate'))),
			1 => array('Person' => array('name' => 'Tracy'),'Friend' => array(array('name' => 'Lindsay')))
		);
		$a = array(
			0 => array('Person' => array('name' => 'Tracy'),'Friend' => array(array('name' => 'Lindsay'))),
			1 => array('Person' => array('name' => 'Jeff'), 'Friend' => array(array('name' => 'Nate')))

		);
		$a = Set::sort($a, '{n}.Friend.{n}.name', 'desc');
		$this->assertIdentical($a, $b);

		$a = array(
			0 => array('Person' => array('name' => 'Jeff'), 'Friend' => array(array('name' => 'Nate'))),
			1 => array('Person' => array('name' => 'Tracy'),'Friend' => array(array('name' => 'Lindsay'))),
			2 => array('Person' => array('name' => 'Adam'),'Friend' => array(array('name' => 'Bob')))
		);
		$b = array(
			0 => array('Person' => array('name' => 'Adam'),'Friend' => array(array('name' => 'Bob'))),
			1 => array('Person' => array('name' => 'Jeff'), 'Friend' => array(array('name' => 'Nate'))),
			2 => array('Person' => array('name' => 'Tracy'),'Friend' => array(array('name' => 'Lindsay')))
		);
		$a = Set::sort($a, '{n}.Person.name', 'asc');
		$this->assertIdentical($a, $b);

		$a = array(
			array(7,6,4),
			array(3,4,5),
			array(3,2,1),
		);

		$b = array(
			array(3,2,1),
			array(3,4,5),
			array(7,6,4),
		);

		$a = Set::sort($a, '{n}.{n}', 'asc');
		$this->assertIdentical($a, $b);

		$a = array(
			array(7,6,4),
			array(3,4,5),
			array(3,2,array(1,1,1)),
		);

		$b = array(
			array(3,2,array(1,1,1)),
			array(3,4,5),
			array(7,6,4),
		);

		$a = Set::sort($a, '{n}', 'asc');
		$this->assertIdentical($a, $b);

		$a = array(
			0 => array('Person' => array('name' => 'Jeff')),
			1 => array('Shirt' => array('color' => 'black'))
		);
		$b = array(
			0 => array('Shirt' => array('color' => 'black')),
			1 => array('Person' => array('name' => 'Jeff')),
		);
		$a = Set::sort($a, '{n}.Person.name', 'asc');
		$this->assertIdentical($a, $b);
	}

	function testExtract() {
		$a = array(
			array(
				'Article' => array('id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'),
				'User' => array('id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'),
				'Comment' => array(
					array('id' => '1', 'article_id' => '1', 'user_id' => '2', 'comment' => 'First Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31'),
					array('id' => '2', 'article_id' => '1', 'user_id' => '4', 'comment' => 'Second Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31'),
				),
				'Tag' => array(
					array('id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23', 'updated' => '2007-03-18 12:24:31'),
					array('id' => '2', 'tag' => 'tag2', 'created' => '2007-03-18 12:24:23', 'updated' => '2007-03-18 12:26:31')
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
				'Article' => array('id' => '3', 'user_id' => '1', 'title' => 'Third Article', 'body' => 'Third Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'),
				'User' => array('id' => '2', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'),
				'Comment' => array(),
				'Tag' => array()
			),
			array(
				'Article' => array('id' => '3', 'user_id' => '1', 'title' => 'Third Article', 'body' => 'Third Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'),
				'User' => array('id' => '3', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'),
				'Comment' => array(),
				'Tag' => array()
			),
			array(
				'Article' => array('id' => '3', 'user_id' => '1', 'title' => 'Third Article', 'body' => 'Third Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'),
				'User' => array('id' => '4', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'),
				'Comment' => array(),
				'Tag' => array()
			),
			array(
				'Article' => array('id' => '3', 'user_id' => '1', 'title' => 'Third Article', 'body' => 'Third Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'),
				'User' => array('id' => '5', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'),
				'Comment' => array(),
				'Tag' => array()
			)
		);
		$b = array('Deep' => $a[0]['Deep']);
		$c = array(
			array(
				'a' => array(
					'I' => array(
						'a' => 1
					)
				)
			),
			array(
				'a' => array(
					2
				)
			),
			array(
				'a' => array(
					'II' => array(
						'a' => 3,
						'III' => array(
							'a' => array('foo' => 4)
						)
					)
				)
			),
		);

		$expected = array(
			$c[0], $c[0]['a']['I'], $c[1], $c[2], array('a' => $c[2]['a']['II']['a']), $c[2]['a']['II']['III']
		);

		$expected = array(1,2,3,4,5);
		$r = Set::extract('/User/id', $a);
		$this->assertEqual($r, $expected);

		$expected = array(array('id' => 1), array('id' => 2), array('id' => 3), array('id' => 4), array('id' => 5));
		$r = Set::extract('/User/id', $a, array('flatten' => false));
		$this->assertEqual($r, $expected);

		$expected = array(array('test' => $a[0]['Deep']['Nesting']['test']));
		$this->assertEqual(Set::extract('/Deep/Nesting/test', $a), $expected);
		$this->assertEqual(Set::extract('/Deep/Nesting/test', $b), $expected);

		$expected = array(array('test' => $a[0]['Deep']['Nesting']['test']));
		$r = Set::extract('/Deep/Nesting/test/1/..', $a);
		$this->assertEqual($r, $expected);

		$expected = array(array('test' => $a[0]['Deep']['Nesting']['test']));
		$r = Set::extract('/Deep/Nesting/test/2/and/../..', $a);
		$this->assertEqual($r, $expected);

		$expected = array(array('test' => $a[0]['Deep']['Nesting']['test']));
		$r = Set::extract('/Deep/Nesting/test/2/../../../Nesting/test/2/..', $a);
		$this->assertEqual($r, $expected);

		$expected = array(2);
		$r = Set::extract('/User[2]/id', $a);
		$this->assertEqual($r, $expected);

		$expected = array(4, 5);
		$r = Set::extract('/User[id>3]/id', $a);
		$this->assertEqual($r, $expected);

		$expected = array(2, 3);
		$r = Set::extract('/User[id>1][id<=3]/id', $a);
		$this->assertEqual($r, $expected);

		$expected = array(array('I'), array('II'));
		$r = Set::extract('/a/@*', $c);
		$this->assertEqual($r, $expected);

		$single = array(
			'User' => array(
				'id' => 4,
				'name' => 'Neo',
			)
		);
		$tricky = array(
			0 => array(
				'User' => array(
					'id' => 1,
					'name' => 'John',
				)
			),
			1 => array(
				'User' => array(
					'id' => 2,
					'name' => 'Bob',
				)
			),
			2 => array(
				'User' => array(
					'id' => 3,
					'name' => 'Tony',
				)
			),
			'User' => array(
				'id' => 4,
				'name' => 'Neo',
			)
		);

		$expected = array(1, 2, 3, 4);
		$r = Set::extract('/User/id', $tricky);
		$this->assertEqual($r, $expected);

		$expected = array(4);
		$r = Set::extract('/User/id', $single);
		$this->assertEqual($r, $expected);

		$expected = array(1, 3);
		$r = Set::extract('/User[name=/n/]/id', $tricky);
		$this->assertEqual($r, $expected);

		$expected = array(4);
		$r = Set::extract('/User[name=/N/]/id', $tricky);
		$this->assertEqual($r, $expected);

		$expected = array(1, 3, 4);
		$r = Set::extract('/User[name=/N/i]/id', $tricky);
		$this->assertEqual($r, $expected);

		$expected = array(array('id', 'name'), array('id', 'name'), array('id', 'name'), array('id', 'name'));
		$r = Set::extract('/User/@*', $tricky);
		$this->assertEqual($r, $expected);

		$common = array(
			array(
				'Article' => array(
					'id' => 1,
					'name' => 'Article 1',
				),
				'Comment' => array(
					array(
						'id' => 1,
						'user_id' => 5,
						'article_id' => 1,
						'text' => 'Comment 1',
					),
					array(
						'id' => 2,
						'user_id' => 23,
						'article_id' => 1,
						'text' => 'Comment 2',
					),
					array(
						'id' => 3,
						'user_id' => 17,
						'article_id' => 1,
						'text' => 'Comment 3',
					),
				),
			),
			array(
				'Article' => array(
					'id' => 2,
					'name' => 'Article 2',
				),
				'Comment' => array(
					array(
						'id' => 4,
						'user_id' => 2,
						'article_id' => 2,
						'text' => 'Comment 4',
						'addition' => '',
					),
					array(
						'id' => 5,
						'user_id' => 23,
						'article_id' => 2,
						'text' => 'Comment 5',
						'addition' => 'foo',
					),
				),
			),
			array(
				'Article' => array(
					'id' => 3,
					'name' => 'Article 3',
				),
				'Comment' => array(),
			)
		);

		$r = Set::extract('/Comment/id', $common);
		$expected = array(1, 2, 3, 4, 5);
		$this->assertEqual($r, $expected);

		$expected = array(1, 2, 4, 5);
		$r = Set::extract('/Comment[id!=3]/id', $common);
		$this->assertEqual($r, $expected);

		$r = Set::extract('/', $common);
		$this->assertEqual($r, $common);

		$expected = array(1, 2, 4, 5);
		$r = Set::extract($common, '/Comment[id!=3]/id');
		$this->assertEqual($r, $expected);

		$expected = array($common[0]['Comment'][2]);
		$r = Set::extract($common, '/Comment/2');
		$this->assertEqual($r, $expected);

		$expected = array($common[0]['Comment'][0]);
		$r = Set::extract($common, '/Comment[1]/.[id=1]');
		$this->assertEqual($r, $expected);

		$expected = array($common[1]['Comment'][1]);
		$r = Set::extract($common, '/1/Comment/.[2]');
		$this->assertEqual($r, $expected);

		$expected = array();
		$r = Set::extract('/User/id', array());
		$this->assertEqual($r, $expected);

		$expected = array(5);
		$r = Set::extract('/Comment/id[:last]', $common);
		$this->assertEqual($r, $expected);

		$expected = array(1);
		$r = Set::extract('/Comment/id[:first]', $common);
		$this->assertEqual($r, $expected);

		$expected = array(3);
		$r = Set::extract('/Article[:last]/id', $common);
		$this->assertEqual($r, $expected);
		
		$expected = array(array('Comment' => $common[1]['Comment'][0]));
		$r = Set::extract('/Comment[addition=]', $common);
		$this->assertEqual($r, $expected);
	}
/**
 * undocumented function
 *
 * @return void
 * @author Felix
 */

	function testMatches() {
		$a = array(
			array('Article' => array('id' => 1, 'title' => 'Article 1')),
			array('Article' => array('id' => 2, 'title' => 'Article 2')),
			array('Article' => array('id' => 3, 'title' => 'Article 3')));

		$this->assertTrue(Set::matches(array('id=2'), $a[1]['Article']));
		$this->assertFalse(Set::matches(array('id>2'), $a[1]['Article']));
		$this->assertTrue(Set::matches(array('id>=2'), $a[1]['Article']));
		$this->assertTrue(Set::matches(array('id>1'), $a[1]['Article']));
		$this->assertTrue(Set::matches(array('id>1', 'id<3', 'id!=0'), $a[1]['Article']));

		$this->assertTrue(Set::matches(array('3'), null, 3));
		$this->assertTrue(Set::matches(array('5'), null, 5));

		$this->assertTrue(Set::matches(array('id'), $a[1]['Article']));
		$this->assertTrue(Set::matches(array('id', 'title'), $a[1]['Article']));
		$this->assertFalse(Set::matches(array('non-existant'), $a[1]['Article']));

		$this->assertTrue(Set::matches('/Article[id=2]', $a));
		$this->assertFalse(Set::matches('/Article[id=4]', $a));
	}

	function testClassicExtract() {
		$a = array(
			array('Article' => array('id' => 1, 'title' => 'Article 1')),
			array('Article' => array('id' => 2, 'title' => 'Article 2')),
			array('Article' => array('id' => 3, 'title' => 'Article 3')));

		$result = Set::extract($a, '{n}.Article.id');
		$expected = array( 1, 2, 3 );
		$this->assertIdentical($result, $expected);

		$result = Set::extract($a, '{n}.Article.title');
		$expected = array( 'Article 1', 'Article 2', 'Article 3' );
		$this->assertIdentical($result, $expected);

		$a = array(
			array('Article' => array('id' => 1, 'title' => 'Article 1',
				'User' => array('id' => 1, 'username' => 'mariano.iglesias'))),
			array('Article' => array('id' => 2, 'title' => 'Article 2',
				'User' => array('id' => 1, 'username' => 'mariano.iglesias'))),
			array('Article' => array('id' => 3, 'title' => 'Article 3',
				'User' => array('id' => 2, 'username' => 'phpnut'))));

		$result = Set::extract($a, '{n}.Article.User.username');
		$expected = array( 'mariano.iglesias', 'mariano.iglesias', 'phpnut' );
		$this->assertIdentical($result, $expected);

		$a = array(
			array('Article' => array('id' => 1, 'title' => 'Article 1',
				'Comment' => array(
					array('id' => 10, 'title' => 'Comment 10'),
					array('id' => 11, 'title' => 'Comment 11'),
					array('id' => 12, 'title' => 'Comment 12')))),
			array('Article' => array('id' => 2, 'title' => 'Article 2',
				'Comment' => array(
					array('id' => 13, 'title' => 'Comment 13'),
					array('id' => 14, 'title' => 'Comment 14')))),
			array('Article' => array('id' => 3, 'title' => 'Article 3')));

		$result = Set::extract($a, '{n}.Article.Comment.{n}.id');
		$expected = array (array(10, 11, 12), array(13, 14), null);
		$this->assertIdentical($result, $expected);

		$result = Set::extract($a, '{n}.Article.Comment.{n}.title');
		$expected = array (array('Comment 10', 'Comment 11', 'Comment 12'), array('Comment 13', 'Comment 14'), null);
		$this->assertIdentical($result, $expected);

		$a = array(array('1day' => '20 sales'), array('1day' => '2 sales'));
		$result = Set::extract($a, '{n}.1day');
		$expected = array('20 sales', '2 sales');
		$this->assertIdentical($result, $expected);

    	$a = array(
			'pages'     => array('name' => 'page'),
			'fruites'   => array('name' => 'fruit'),
			0           => array('name' => 'zero')
		);
		$result = Set::extract($a, '{s}.name');
		$expected = array('page','fruit');
		$this->assertIdentical($result, $expected);

		$a = array(
			0 => array('pages' => array('name' => 'page')),
			1 => array('fruites'=> array('name' => 'fruit')),
			'test' => array(array('name' => 'jippi')),
			'dot.test' => array(array('name' => 'jippi'))
		);

		$result = Set::extract($a, '{n}.{s}.name');
		$expected = array(0 => array('page'), 1 => array('fruit'));
		$this->assertIdentical($result, $expected);

		$result = Set::extract($a, '{s}.{n}.name');
		$expected = array(array('jippi'), array('jippi'));
		$this->assertIdentical($result, $expected);

		$result = Set::extract($a,'{\w+}.{\w+}.name');
		$expected = array(array('pages' => 'page'), array('fruites' => 'fruit'), 'test' => array('jippi'), 'dot.test' => array('jippi'));
		$this->assertIdentical($result, $expected);

		$result = Set::extract($a,'{\d+}.{\w+}.name');
		$expected = array(array('pages' => 'page'), array('fruites' => 'fruit'));
		$this->assertIdentical($result, $expected);

		$result = Set::extract($a,'{n}.{\w+}.name');
    	$expected = array(array('pages' => 'page'), array('fruites' => 'fruit'));
		$this->assertIdentical($result, $expected);

		$result = Set::extract($a,'{s}.{\d+}.name');
		$expected = array(array('jippi'), array('jippi'));
		$this->assertIdentical($result, $expected);

		$result = Set::extract($a,'{s}');
		$expected = array(array(array('name' => 'jippi')), array(array('name' => 'jippi')));
		$this->assertIdentical($result, $expected);

		$result = Set::extract($a,'{[a-z]}');
		$expected = array('test' => array(array('name' => 'jippi')), 'dot.test' => array(array('name' => 'jippi')));
		$this->assertIdentical($result, $expected);

		$result = Set::extract($a, '{dot\.test}.{n}');
		$expected = array('dot.test' => array(array('name' => 'jippi')));
		$this->assertIdentical($result, $expected);
	}

	function testCheck() {
		$set = new Set(array(
			'My Index 1' => array('First' => 'The first item')
		));
		$this->assertTrue($set->check('My Index 1.First'));

		$set = new Set(array(
			'My Index 1' => array('First' => array('Second' => array('Third' => array('Fourth' => 'Heavy. Nesting.'))))
		));
		$this->assertTrue($set->check('My Index 1.First.Second'));
		$this->assertTrue($set->check('My Index 1.First.Second.Third'));
		$this->assertTrue($set->check('My Index 1.First.Second.Third.Fourth'));
	}

	function testWritingWithFunkyKeys() {
		$set = new Set();
		$set->insert('Session Test', "test");
		$this->assertEqual($set->extract('Session Test'), 'test');

		$set->remove('Session Test');
		$this->assertFalse($set->check('Session Test'));

		$this->assertTrue($set->insert('Session Test.Test Case', "test"));
		$this->assertTrue($set->check('Session Test.Test Case'));
	}

	function testCombine() {
	    $result = Set::combine(array(), '{n}.User.id', '{n}.User.Data');
	    $this->assertFalse($result);
	    $result = Set::combine('', '{n}.User.id', '{n}.User.Data');
	    $this->assertFalse($result);
	    
		$a = array(
			array('User' => array('id' => 2, 'group_id' => 1,
				'Data' => array('user' => 'mariano.iglesias','name' => 'Mariano Iglesias'))),
			array('User' => array('id' => 14, 'group_id' => 2,
				'Data' => array('user' => 'phpnut', 'name' => 'Larry E. Masters'))),
			array('User' => array('id' => 25, 'group_id' => 1,
				'Data' => array('user' => 'gwoo','name' => 'The Gwoo'))));
		$result = Set::combine($a, '{n}.User.id');
		$expected = array(2 => null, 14 => null, 25 => null);
		$this->assertIdentical($result, $expected);

		$result = Set::combine($a, '{n}.User.id', '{n}.User.Data');
		$expected = array(
			2 => array('user' => 'mariano.iglesias',	'name' => 'Mariano Iglesias'),
			14 => array('user' => 'phpnut',	'name' => 'Larry E. Masters'),
			25 => array('user' => 'gwoo',	'name' => 'The Gwoo'));
		$this->assertIdentical($result, $expected);

		$result = Set::combine($a, '{n}.User.id', '{n}.User.Data.name');
		$expected = array(
			2 => 'Mariano Iglesias',
			14 => 'Larry E. Masters',
			25 => 'The Gwoo');
		$this->assertIdentical($result, $expected);

		$result = Set::combine($a, '{n}.User.id', '{n}.User.Data', '{n}.User.group_id');
		$expected = array(
			1 => array(
				2 => array('user' => 'mariano.iglesias', 'name' => 'Mariano Iglesias'),
				25 => array('user' => 'gwoo', 'name' => 'The Gwoo')),
			2 => array(
				14 => array('user' => 'phpnut', 'name' => 'Larry E. Masters')));
		$this->assertIdentical($result, $expected);

		$result = Set::combine($a, '{n}.User.id', '{n}.User.Data.name', '{n}.User.group_id');
		$expected = array(
			1 => array(
				2 => 'Mariano Iglesias',
				25 => 'The Gwoo'),
			2 => array(
				14 => 'Larry E. Masters'));
		$this->assertIdentical($result, $expected);

		$Set =& new Set($a);

		$result = $Set->combine('{n}.User.id');
		$expected = array(2 => null, 14 => null, 25 => null);
		$this->assertIdentical($result, $expected);

		$result = $Set->combine('{n}.User.id', '{n}.User.Data');
		$expected = array(
			2 => array('user' => 'mariano.iglesias', 'name' => 'Mariano Iglesias'),
			14 => array('user' => 'phpnut', 'name' => 'Larry E. Masters'),
			25 => array('user' => 'gwoo', 'name' => 'The Gwoo'));
		$this->assertIdentical($result, $expected);

		$result = $Set->combine('{n}.User.id', '{n}.User.Data.name');
		$expected = array(2 => 'Mariano Iglesias', 14 => 'Larry E. Masters', 25 => 'The Gwoo');
		$this->assertIdentical($result, $expected);

		$result = $Set->combine('{n}.User.id', '{n}.User.Data', '{n}.User.group_id');
		$expected = array(
			1 => array(
				2 => array('user' => 'mariano.iglesias', 'name' => 'Mariano Iglesias'),
				25 => array('user' => 'gwoo', 'name' => 'The Gwoo')),
			2 => array(
				14 => array('user' => 'phpnut', 'name' => 'Larry E. Masters')));
		$this->assertIdentical($result, $expected);

		$result = $Set->combine('{n}.User.id', '{n}.User.Data.name', '{n}.User.group_id');
		$expected = array(
			1 => array(
				2 => 'Mariano Iglesias',
				25 => 'The Gwoo'),
			2 => array(
				14 => 'Larry E. Masters'));
		$this->assertIdentical($result, $expected);

		$result = Set::combine($a, '{n}.User.id', array('{0}: {1}', '{n}.User.Data.user', '{n}.User.Data.name'), '{n}.User.group_id');
		$expected = array (
			1 => array (
				2 => 'mariano.iglesias: Mariano Iglesias',
				25 => 'gwoo: The Gwoo'),
			2 => array (14 => 'phpnut: Larry E. Masters'));
		$this->assertIdentical($result, $expected);

		$result = Set::combine($a, array('{0}: {1}', '{n}.User.Data.user', '{n}.User.Data.name'), '{n}.User.id');
		$expected = array('mariano.iglesias: Mariano Iglesias' => 2, 'phpnut: Larry E. Masters' => 14, 'gwoo: The Gwoo' => 25);
		$this->assertIdentical($result, $expected);

		$result = Set::combine($a, array('{1}: {0}', '{n}.User.Data.user', '{n}.User.Data.name'), '{n}.User.id');
		$expected = array('Mariano Iglesias: mariano.iglesias' => 2, 'Larry E. Masters: phpnut' => 14, 'The Gwoo: gwoo' => 25);
		$this->assertIdentical($result, $expected);

		$result = Set::combine($a, array('%1$s: %2$d', '{n}.User.Data.user', '{n}.User.id'), '{n}.User.Data.name');
		$expected = array('mariano.iglesias: 2' => 'Mariano Iglesias', 'phpnut: 14' => 'Larry E. Masters', 'gwoo: 25' => 'The Gwoo');
		$this->assertIdentical($result, $expected);

		$result = Set::combine($a, array('%2$d: %1$s', '{n}.User.Data.user', '{n}.User.id'), '{n}.User.Data.name');
		$expected = array('2: mariano.iglesias' => 'Mariano Iglesias', '14: phpnut' => 'Larry E. Masters', '25: gwoo' => 'The Gwoo');
		$this->assertIdentical($result, $expected);
	}

	function testMapReverse() {
		$result = Set::reverse(null);
		$this->assertEqual($result, null);

		$result = Set::reverse(false);
		$this->assertEqual($result, false);

		$expected = array(
		'Array1' => array(
				'Array1Data1' => 'Array1Data1 value 1', 'Array1Data2' => 'Array1Data2 value 2'),
		'Array2' => array(
				0 => array('Array2Data1' => 1, 'Array2Data2' => 'Array2Data2 value 2', 'Array2Data3' => 'Array2Data3 value 2', 'Array2Data4' => 'Array2Data4 value 4'),
				1 => array('Array2Data1' => 2, 'Array2Data2' => 'Array2Data2 value 2', 'Array2Data3' => 'Array2Data3 value 2', 'Array2Data4' => 'Array2Data4 value 4'),
				2 => array('Array2Data1' => 3, 'Array2Data2' => 'Array2Data2 value 2', 'Array2Data3' => 'Array2Data3 value 2', 'Array2Data4' => 'Array2Data4 value 4'),
				3 => array('Array2Data1' => 4, 'Array2Data2' => 'Array2Data2 value 2', 'Array2Data3' => 'Array2Data3 value 2', 'Array2Data4' => 'Array2Data4 value 4'),
				4 => array('Array2Data1' => 5, 'Array2Data2' => 'Array2Data2 value 2', 'Array2Data3' => 'Array2Data3 value 2', 'Array2Data4' => 'Array2Data4 value 4')),
		'Array3' => array(
				0 => array('Array3Data1' => 1, 'Array3Data2' => 'Array3Data2 value 2', 'Array3Data3' => 'Array3Data3 value 2', 'Array3Data4' => 'Array3Data4 value 4'),
				1 => array('Array3Data1' => 2, 'Array3Data2' => 'Array3Data2 value 2', 'Array3Data3' => 'Array3Data3 value 2', 'Array3Data4' => 'Array3Data4 value 4'),
				2 => array('Array3Data1' => 3, 'Array3Data2' => 'Array3Data2 value 2', 'Array3Data3' => 'Array3Data3 value 2', 'Array3Data4' => 'Array3Data4 value 4'),
				3 => array('Array3Data1' => 4, 'Array3Data2' => 'Array3Data2 value 2', 'Array3Data3' => 'Array3Data3 value 2', 'Array3Data4' => 'Array3Data4 value 4'),
				4 => array('Array3Data1' => 5, 'Array3Data2' => 'Array3Data2 value 2', 'Array3Data3' => 'Array3Data3 value 2', 'Array3Data4' => 'Array3Data4 value 4')));
		$map = Set::map($expected, true);
		$this->assertEqual($map->Array1->Array1Data1, $expected['Array1']['Array1Data1']);
		$this->assertEqual($map->Array2[0]->Array2Data1, $expected['Array2'][0]['Array2Data1']);

		$result = Set::reverse($map);
		$this->assertIdentical($result, $expected);

		$expected = array(
			'Post' => array('id'=> 1, 'title' => 'First Post'),
			'Comment' => array(
				array('id'=> 1, 'title' => 'First Comment'),
				array('id'=> 2, 'title' => 'Second Comment')
			),
			'Tag' => array(
				array('id'=> 1, 'title' => 'First Tag'),
				array('id'=> 2, 'title' => 'Second Tag')
			),
		);
		$map = Set::map($expected);
		$this->assertIdentical($map->title, $expected['Post']['title']);
		foreach ($map->Comment as $comment) {
			$ids[] = $comment->id;
		}
		$this->assertIdentical($ids, array(1, 2));

		$expected = array(
		'Array1' => array(
				'Array1Data1' => 'Array1Data1 value 1', 'Array1Data2' => 'Array1Data2 value 2', 'Array1Data3' => 'Array1Data3 value 3','Array1Data4' => 'Array1Data4 value 4',
				'Array1Data5' => 'Array1Data5 value 5', 'Array1Data6' => 'Array1Data6 value 6', 'Array1Data7' => 'Array1Data7 value 7', 'Array1Data8' => 'Array1Data8 value 8'),
		'string' => 1,
		'another' => 'string',
		'some' => 'thing else',
		'Array2' => array(
				0 => array('Array2Data1' => 1, 'Array2Data2' => 'Array2Data2 value 2', 'Array2Data3' => 'Array2Data3 value 2', 'Array2Data4' => 'Array2Data4 value 4'),
				1 => array('Array2Data1' => 2, 'Array2Data2' => 'Array2Data2 value 2', 'Array2Data3' => 'Array2Data3 value 2', 'Array2Data4' => 'Array2Data4 value 4'),
				2 => array('Array2Data1' => 3, 'Array2Data2' => 'Array2Data2 value 2', 'Array2Data3' => 'Array2Data3 value 2', 'Array2Data4' => 'Array2Data4 value 4'),
				3 => array('Array2Data1' => 4, 'Array2Data2' => 'Array2Data2 value 2', 'Array2Data3' => 'Array2Data3 value 2', 'Array2Data4' => 'Array2Data4 value 4'),
				4 => array('Array2Data1' => 5, 'Array2Data2' => 'Array2Data2 value 2', 'Array2Data3' => 'Array2Data3 value 2', 'Array2Data4' => 'Array2Data4 value 4')),
		'Array3' => array(
				0 => array('Array3Data1' => 1, 'Array3Data2' => 'Array3Data2 value 2', 'Array3Data3' => 'Array3Data3 value 2', 'Array3Data4' => 'Array3Data4 value 4'),
				1 => array('Array3Data1' => 2, 'Array3Data2' => 'Array3Data2 value 2', 'Array3Data3' => 'Array3Data3 value 2', 'Array3Data4' => 'Array3Data4 value 4'),
				2 => array('Array3Data1' => 3, 'Array3Data2' => 'Array3Data2 value 2', 'Array3Data3' => 'Array3Data3 value 2', 'Array3Data4' => 'Array3Data4 value 4'),
				3 => array('Array3Data1' => 4, 'Array3Data2' => 'Array3Data2 value 2', 'Array3Data3' => 'Array3Data3 value 2', 'Array3Data4' => 'Array3Data4 value 4'),
				4 => array('Array3Data1' => 5, 'Array3Data2' => 'Array3Data2 value 2', 'Array3Data3' => 'Array3Data3 value 2', 'Array3Data4' => 'Array3Data4 value 4')));
		$map = Set::map($expected, true);
		$result = Set::reverse($map);
		$this->assertIdentical($result, $expected);

		$expected = array(
		'Array1' => array(
				'Array1Data1' => 'Array1Data1 value 1', 'Array1Data2' => 'Array1Data2 value 2', 'Array1Data3' => 'Array1Data3 value 3','Array1Data4' => 'Array1Data4 value 4',
				'Array1Data5' => 'Array1Data5 value 5', 'Array1Data6' => 'Array1Data6 value 6', 'Array1Data7' => 'Array1Data7 value 7', 'Array1Data8' => 'Array1Data8 value 8'),
		'string' => 1,
		'another' => 'string',
		'some' => 'thing else',
		'Array2' => array(
				0 => array('Array2Data1' => 1, 'Array2Data2' => 'Array2Data2 value 2', 'Array2Data3' => 'Array2Data3 value 2', 'Array2Data4' => 'Array2Data4 value 4'),
				1 => array('Array2Data1' => 2, 'Array2Data2' => 'Array2Data2 value 2', 'Array2Data3' => 'Array2Data3 value 2', 'Array2Data4' => 'Array2Data4 value 4'),
				2 => array('Array2Data1' => 3, 'Array2Data2' => 'Array2Data2 value 2', 'Array2Data3' => 'Array2Data3 value 2', 'Array2Data4' => 'Array2Data4 value 4'),
				3 => array('Array2Data1' => 4, 'Array2Data2' => 'Array2Data2 value 2', 'Array2Data3' => 'Array2Data3 value 2', 'Array2Data4' => 'Array2Data4 value 4'),
				4 => array('Array2Data1' => 5, 'Array2Data2' => 'Array2Data2 value 2', 'Array2Data3' => 'Array2Data3 value 2', 'Array2Data4' => 'Array2Data4 value 4')),
		'string2' => 1,
		'another2' => 'string',
		'some2' => 'thing else',
		'Array3' => array(
				0 => array('Array3Data1' => 1, 'Array3Data2' => 'Array3Data2 value 2', 'Array3Data3' => 'Array3Data3 value 2', 'Array3Data4' => 'Array3Data4 value 4'),
				1 => array('Array3Data1' => 2, 'Array3Data2' => 'Array3Data2 value 2', 'Array3Data3' => 'Array3Data3 value 2', 'Array3Data4' => 'Array3Data4 value 4'),
				2 => array('Array3Data1' => 3, 'Array3Data2' => 'Array3Data2 value 2', 'Array3Data3' => 'Array3Data3 value 2', 'Array3Data4' => 'Array3Data4 value 4'),
				3 => array('Array3Data1' => 4, 'Array3Data2' => 'Array3Data2 value 2', 'Array3Data3' => 'Array3Data3 value 2', 'Array3Data4' => 'Array3Data4 value 4'),
				4 => array('Array3Data1' => 5, 'Array3Data2' => 'Array3Data2 value 2', 'Array3Data3' => 'Array3Data3 value 2', 'Array3Data4' => 'Array3Data4 value 4')),
		'string3' => 1,
		'another3' => 'string',
		'some3' => 'thing else');
		$map = Set::map($expected, true);
		$result = Set::reverse($map);
		$this->assertIdentical($result, $expected);

		$expected = array('User' => array('psword'=> 'whatever', 'Icon' => array('id'=> 851)));
		$map = Set::map($expected);
		$result = Set::reverse($map);
		$this->assertIdentical($result, $expected);

		$expected = array('User' => array('psword'=> 'whatever', 'Icon' => array('id'=> 851)));
		$class = new stdClass;
		$class->User = new stdClass;
		$class->User->psword = 'whatever';
		$class->User->Icon = new stdClass;
		$class->User->Icon->id = 851;
		$result = Set::reverse($class);
		$this->assertIdentical($result, $expected);

		$expected = array('User' => array('psword'=> 'whatever', 'Icon' => array('id'=> 851), 'Profile' => array('name' => 'Some Name', 'address' => 'Some Address')));
		$class = new stdClass;
		$class->User = new stdClass;
		$class->User->psword = 'whatever';
		$class->User->Icon = new stdClass;
		$class->User->Icon->id = 851;
		$class->User->Profile = new stdClass;
		$class->User->Profile->name = 'Some Name';
		$class->User->Profile->address = 'Some Address';

		$result = Set::reverse($class);
		$this->assertIdentical($result, $expected);

		$expected = array('User' => array('psword'=> 'whatever',
						'Icon' => array('id'=> 851),
						'Profile' => array('name' => 'Some Name', 'address' => 'Some Address'),
						'Comment' => array(
								array('id' => 1, 'article_id' => 1, 'user_id' => 1, 'comment' => 'First Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31'),
								array('id' => 2, 'article_id' => 1, 'user_id' => 2, 'comment' => 'Second Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31'))));

		$class = new stdClass;
		$class->User = new stdClass;
		$class->User->psword = 'whatever';
		$class->User->Icon = new stdClass;
		$class->User->Icon->id = 851;
		$class->User->Profile = new stdClass;
		$class->User->Profile->name = 'Some Name';
		$class->User->Profile->address = 'Some Address';
		$class->User->Comment = new stdClass;
		$class->User->Comment->{'0'} = new stdClass;
		$class->User->Comment->{'0'}->id = 1;
		$class->User->Comment->{'0'}->article_id = 1;
		$class->User->Comment->{'0'}->user_id = 1;
		$class->User->Comment->{'0'}->comment = 'First Comment for First Article';
		$class->User->Comment->{'0'}->published = 'Y';
		$class->User->Comment->{'0'}->created = '2007-03-18 10:47:23';
		$class->User->Comment->{'0'}->updated = '2007-03-18 10:49:31';
		$class->User->Comment->{'1'} = new stdClass;
		$class->User->Comment->{'1'}->id = 2;
		$class->User->Comment->{'1'}->article_id = 1;
		$class->User->Comment->{'1'}->user_id = 2;
		$class->User->Comment->{'1'}->comment = 'Second Comment for First Article';
		$class->User->Comment->{'1'}->published = 'Y';
		$class->User->Comment->{'1'}->created = '2007-03-18 10:47:23';
		$class->User->Comment->{'1'}->updated = '2007-03-18 10:49:31';

		$result = Set::reverse($class);
		$this->assertIdentical($result, $expected);

		$expected = array('User' => array('psword'=> 'whatever',
						'Icon' => array('id'=> 851),
						'Profile' => array('name' => 'Some Name', 'address' => 'Some Address'),
						'Comment' => array(
								array('id' => 1, 'article_id' => 1, 'user_id' => 1, 'comment' => 'First Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31'),
								array('id' => 2, 'article_id' => 1, 'user_id' => 2, 'comment' => 'Second Comment for First Article', 'published' => 'Y', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31'))));

		$class = new stdClass;
		$class->User = new stdClass;
		$class->User->psword = 'whatever';
		$class->User->Icon = new stdClass;
		$class->User->Icon->id = 851;
		$class->User->Profile = new stdClass;
		$class->User->Profile->name = 'Some Name';
		$class->User->Profile->address = 'Some Address';
		$class->User->Comment = array();
		$comment = new stdClass;
		$comment->id = 1;
		$comment->article_id = 1;
		$comment->user_id = 1;
		$comment->comment = 'First Comment for First Article';
		$comment->published = 'Y';
		$comment->created = '2007-03-18 10:47:23';
		$comment->updated = '2007-03-18 10:49:31';
		$comment2 = new stdClass;
		$comment2->id = 2;
		$comment2->article_id = 1;
		$comment2->user_id = 2;
		$comment2->comment = 'Second Comment for First Article';
		$comment2->published = 'Y';
		$comment2->created = '2007-03-18 10:47:23';
		$comment2->updated = '2007-03-18 10:49:31';
		$class->User->Comment =  array($comment, $comment2);
		$result = Set::reverse($class);
		$this->assertIdentical($result, $expected);

		uses('model'.DS.'model');
		$model = new Model(array('id' => false, 'name' => 'Model', 'table' => false));
		$expected = array(
			'Behaviors' => array('modelName' => 'Model', '_attached' => array(), '_disabled' => array(), '__methods' => array(), '__mappedMethods' => array(), '_log' => null),
			'useDbConfig' => 'default', 'useTable' => false, 'displayField' => null, 'id' => false, 'data' => array(), 'table' => false, 'primaryKey' => 'id', '_schema' => null, 'validate' => array(),
			'validationErrors' => array(), 'tablePrefix' => null, 'name' => 'Model', 'alias' => 'Model', 'tableToModel' => array(), 'logTransactions' => false, 'transactional' => false, 'cacheQueries' => false,
			'belongsTo' => array(), 'hasOne' =>  array(), 'hasMany' =>  array(), 'hasAndBelongsToMany' =>  array(), 'actsAs' => null, 'whitelist' =>  array(), 'cacheSources' => true,
			'findQueryType' => null, 'recursive' => 1, 'order' => null, '__exists' => null,
			'__associationKeys' => array(
				'belongsTo' => array('className', 'foreignKey', 'conditions', 'fields', 'order', 'counterCache'),
				'hasOne' => array('className', 'foreignKey', 'conditions', 'fields', 'order', 'dependent'),
				'hasMany' => array('className', 'foreignKey', 'conditions', 'fields', 'order', 'limit', 'offset', 'dependent', 'exclusive', 'finderQuery', 'counterQuery'),
				'hasAndBelongsToMany' => array('className', 'joinTable', 'with', 'foreignKey', 'associationForeignKey', 'conditions', 'fields', 'order', 'limit', 'offset', 'unique', 'finderQuery', 'deleteQuery', 'insertQuery')),
			'__associations' => array('belongsTo', 'hasOne', 'hasMany', 'hasAndBelongsToMany'), '__backAssociation' => array(), '__insertID' => null, '__numRows' => null, '__affectedRows' => null,
				'__findMethods' => array('all' => true, 'first' => true, 'count' => true, 'neighbors' => true, 'list' => true), '_log' => null);
		$result = Set::reverse($model);

		ksort($result);
		ksort($expected);

		$this->assertIdentical($result, $expected);

		$class = new stdClass;
		$class->User = new stdClass;
		$class->User->id = 100;
		$class->someString = 'this is some string';
		$class->Profile = new stdClass;
		$class->Profile->name = 'Joe Mamma';

		$result = Set::reverse($class);
		$expected = array('User' => array('id' => '100'), 'someString'=> 'this is some string', 'Profile' => array('name' => 'Joe Mamma'));
		$this->assertEqual($result, $expected);
	}

	function testFormatting() {
		$data = array(
			array('Person' => array('first_name' => 'Nate', 'last_name' => 'Abele', 'city' => 'Boston', 'state' => 'MA', 'something' => '42')),
			array('Person' => array('first_name' => 'Larry', 'last_name' => 'Masters', 'city' => 'Boondock', 'state' => 'TN', 'something' => '{0}')),
			array('Person' => array('first_name' => 'Garrett', 'last_name' => 'Woodworth', 'city' => 'Venice Beach', 'state' => 'CA', 'something' => '{1}')));

		$result = Set::format($data, '{1}, {0}', array('{n}.Person.first_name', '{n}.Person.last_name'));
		$expected = array('Abele, Nate', 'Masters, Larry', 'Woodworth, Garrett');
		$this->assertEqual($result, $expected);

		$result = Set::format($data, '{0}, {1}', array('{n}.Person.last_name', '{n}.Person.first_name'));
		$this->assertEqual($result, $expected);

		$result = Set::format($data, '{0}, {1}', array('{n}.Person.city', '{n}.Person.state'));
		$expected = array('Boston, MA', 'Boondock, TN', 'Venice Beach, CA');
		$this->assertEqual($result, $expected);

		$result = Set::format($data, '{{0}, {1}}', array('{n}.Person.city', '{n}.Person.state'));
		$expected = array('{Boston, MA}', '{Boondock, TN}', '{Venice Beach, CA}');
		$this->assertEqual($result, $expected);

		$result = Set::format($data, '{{0}, {1}}', array('{n}.Person.something', '{n}.Person.something'));
		$expected = array('{42, 42}', '{{0}, {0}}', '{{1}, {1}}');
		$this->assertEqual($result, $expected);

		$result = Set::format($data, '{%2$d, %1$s}', array('{n}.Person.something', '{n}.Person.something'));
		$expected = array('{42, 42}', '{0, {0}}', '{0, {1}}');
		$this->assertEqual($result, $expected);

		$result = Set::format($data, '{%1$s, %1$s}', array('{n}.Person.something', '{n}.Person.something'));
		$expected = array('{42, 42}', '{{0}, {0}}', '{{1}, {1}}');
		$this->assertEqual($result, $expected);

		$result = Set::format($data, '%2$d, %1$s', array('{n}.Person.first_name', '{n}.Person.something'));
		$expected = array('42, Nate', '0, Larry', '0, Garrett');
		$this->assertEqual($result, $expected);

		$result = Set::format($data, '%1$s, %2$d', array('{n}.Person.first_name', '{n}.Person.something'));
		$expected = array('Nate, 42', 'Larry, 0', 'Garrett, 0');
		$this->assertEqual($result, $expected);
	}

	function testCountDim() {
		$data = array('one', '2', 'three');
		$result = Set::countDim($data);
		$this->assertEqual($result, 1);

		$data = array('1' => '1.1', '2', '3');
		$result = Set::countDim($data);
		$this->assertEqual($result, 1);

		$data = array('1' => array('1.1' => '1.1.1'), '2', '3' => array('3.1' => '3.1.1'));
		$result = Set::countDim($data);
		$this->assertEqual($result, 2);

		$data = array('1' => '1.1', '2', '3' => array('3.1' => '3.1.1'));
		$result = Set::countDim($data);
		$this->assertEqual($result, 1);

		$data = array('1' => '1.1', '2', '3' => array('3.1' => '3.1.1'));
		$result = Set::countDim($data, true);
		$this->assertEqual($result, 2);

		$data = array('1' => array('1.1' => '1.1.1'), '2', '3' => array('3.1' => array('3.1.1' => '3.1.1.1')));
		$result = Set::countDim($data);
		$this->assertEqual($result, 2);

		$data = array('1' => array('1.1' => '1.1.1'), '2', '3' => array('3.1' => array('3.1.1' => '3.1.1.1')));
		$result = Set::countDim($data, true);
		$this->assertEqual($result, 3);

		$data = array('1' => array('1.1' => '1.1.1'), array('2' => array('2.1' => array('2.1.1' => '2.1.1.1'))), '3' => array('3.1' => array('3.1.1' => '3.1.1.1')));
		$result = Set::countDim($data, true);
		$this->assertEqual($result, 4);

		$data = array('1' => array('1.1' => '1.1.1'), array('2' => array('2.1' => array('2.1.1' => array('2.1.1.1')))), '3' => array('3.1' => array('3.1.1' => '3.1.1.1')));
		$result = Set::countDim($data, true);
		$this->assertEqual($result, 5);

		$data = array('1' => array('1.1' => '1.1.1'), array('2' => array('2.1' => array('2.1.1' => array('2.1.1.1' => '2.1.1.1.1')))), '3' => array('3.1' => array('3.1.1' => '3.1.1.1')));
		$result = Set::countDim($data, true);
		$this->assertEqual($result, 5);
	}

	function testMapNesting() {
		$expected = array(
			array(
				"IndexedPage" => array(
					"id" => 1,
					"url" => 'http://blah.com/',
					'hash' => '68a9f053b19526d08e36c6a9ad150737933816a5',
					'headers' => array(
							'Date' => "Wed, 14 Nov 2007 15:51:42 GMT",
							'Server' => "Apache",
							'Expires' => "Thu, 19 Nov 1981 08:52:00 GMT",
							'Cache-Control' => "private",
							'Pragma' => "no-cache",
							'Content-Type' => "text/html; charset=UTF-8",
							'X-Original-Transfer-Encoding' => "chunked",
							'Content-Length' => "50210",
					),
					'meta' => array(
							'keywords' => array('testing','tests'),
							'description'=>'describe me',
					),
					'get_vars' => '',
					'post_vars' => array(),
					'cookies' => array('PHPSESSID' => "dde9896ad24595998161ffaf9e0dbe2d"),
					'redirect' => '',
					'created' => "1195055503",
					'updated' => "1195055503",
				)
			),
			array(
				"IndexedPage" => array(
					"id" => 2,
					"url" => 'http://blah.com/',
					'hash' => '68a9f053b19526d08e36c6a9ad150737933816a5',
					'headers' => array(
						'Date' => "Wed, 14 Nov 2007 15:51:42 GMT",
						'Server' => "Apache",
						'Expires' => "Thu, 19 Nov 1981 08:52:00 GMT",
						'Cache-Control' => "private",
						'Pragma' => "no-cache",
						'Content-Type' => "text/html; charset=UTF-8",
						'X-Original-Transfer-Encoding' => "chunked",
						'Content-Length' => "50210",
					),
					'meta' => array(
							'keywords' => array('testing','tests'),
							'description'=>'describe me',
					),
					'get_vars' => '',
					'post_vars' => array(),
					'cookies' => array('PHPSESSID' => "dde9896ad24595998161ffaf9e0dbe2d"),
					'redirect' => '',
					'created' => "1195055503",
					'updated' => "1195055503",
				),
			)
		);

		$mapped = Set::map($expected);
		$ids = array();

		foreach($mapped as $object)	 {
			$ids[] = $object->id;
		}
		$this->assertEqual($ids, array(1, 2));
		$this->assertEqual(get_object_vars($mapped[0]->headers), $expected[0]['IndexedPage']['headers']);

		$result = Set::reverse($mapped);
		$this->assertIdentical($result, $expected);

		$data = array(
			array(
				"IndexedPage" => array(
					"id" => 1,
					"url" => 'http://blah.com/',
					'hash' => '68a9f053b19526d08e36c6a9ad150737933816a5',
					'get_vars' => '',
					'redirect' => '',
					'created' => "1195055503",
					'updated' => "1195055503",
				)
			),
			array(
				"IndexedPage" => array(
					"id" => 2,
					"url" => 'http://blah.com/',
					'hash' => '68a9f053b19526d08e36c6a9ad150737933816a5',
					'get_vars' => '',
					'redirect' => '',
					'created' => "1195055503",
					'updated' => "1195055503",
				),
			)
		);
		$mapped = Set::map($data);

		$expected = new stdClass();
		$expected->_name_ = 'IndexedPage';
		$expected->id = 2;
		$expected->url = 'http://blah.com/';
		$expected->hash = '68a9f053b19526d08e36c6a9ad150737933816a5';
		$expected->get_vars = '';
		$expected->redirect = '';
		$expected->created = "1195055503";
		$expected->updated = "1195055503";
		$this->assertIdentical($mapped[1], $expected);

		$ids = array();

		foreach($mapped as $object)	 {
			$ids[] = $object->id;
		}
		$this->assertEqual($ids, array(1, 2));
	}

	function testNestedMappedData() {
		$result = Set::map(array(
				array(
					'Post' => array('id' => '1', 'author_id' => '1', 'title' => 'First Post', 'body' => 'First Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'),
					'Author' => array('id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31', 'test' => 'working'),
				)
				, array(
					'Post' => array('id' => '2', 'author_id' => '3', 'title' => 'Second Post', 'body' => 'Second Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'),
					'Author' => array('id' => '3', 'user' => 'larry', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:20:23', 'updated' => '2007-03-17 01:22:31', 'test' => 'working'),
				)
			));

		$expected = new stdClass;
		$expected->_name_ = 'Post';
		$expected->id = '1';
		$expected->author_id = '1';
		$expected->title = 'First Post';
		$expected->body = 'First Post Body';
		$expected->published = 'Y';
		$expected->created = "2007-03-18 10:39:23";
		$expected->updated = "2007-03-18 10:41:31";

		$expected->Author = new stdClass;
		$expected->Author->id = '1';
		$expected->Author->user = 'mariano';
		$expected->Author->password = '5f4dcc3b5aa765d61d8327deb882cf99';
		$expected->Author->created = "2007-03-17 01:16:23";
		$expected->Author->updated = "2007-03-17 01:18:31";
		$expected->Author->test = "working";

		$expected2 = new stdClass;
		$expected2->_name_ = 'Post';
		$expected2->id = '2';
		$expected2->author_id = '3';
		$expected2->title = 'Second Post';
		$expected2->body = 'Second Post Body';
		$expected2->published = 'Y';
		$expected2->created = "2007-03-18 10:41:23";
		$expected2->updated = "2007-03-18 10:43:31";

		$expected2->Author = new stdClass;
		$expected2->Author->id = '3';
		$expected2->Author->user = 'larry';
		$expected2->Author->password = '5f4dcc3b5aa765d61d8327deb882cf99';
		$expected2->Author->created = "2007-03-17 01:20:23";
		$expected2->Author->updated = "2007-03-17 01:22:31";
		$expected2->Author->test = "working";

		$test = array();
		$test[0] = $expected;
		$test[1] = $expected2;

		$this->assertIdentical($test, $result);

		$result = Set::map(
				array(
					'Post' => array('id' => '1', 'author_id' => '1', 'title' => 'First Post', 'body' => 'First Post Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'),
					'Author' => array('id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31', 'test' => 'working'),
				)
			);
		$expected = new stdClass;
		$expected->_name_ = 'Post';
		$expected->id = '1';
		$expected->author_id = '1';
		$expected->title = 'First Post';
		$expected->body = 'First Post Body';
		$expected->published = 'Y';
		$expected->created = "2007-03-18 10:39:23";
		$expected->updated = "2007-03-18 10:41:31";

		$expected->Author = new stdClass;
		$expected->Author->id = '1';
		$expected->Author->user = 'mariano';
		$expected->Author->password = '5f4dcc3b5aa765d61d8327deb882cf99';
		$expected->Author->created = "2007-03-17 01:16:23";
		$expected->Author->updated = "2007-03-17 01:18:31";
		$expected->Author->test = "working";

		$this->assertIdentical($expected, $result);
	}

	function testPushDiff() {
		$array1 = array('ModelOne' => array('id'=>1001, 'field_one'=>'a1.m1.f1', 'field_two'=>'a1.m1.f2'));
		$array2 = array('ModelTwo' => array('id'=>1002, 'field_one'=>'a2.m2.f1', 'field_two'=>'a2.m2.f2'));

		$result = Set::pushDiff($array1, $array2);

		$this->assertIdentical($result, $array1 + $array2);

		$array3 = array('ModelOne' => array('id'=>1003, 'field_one'=>'a3.m1.f1', 'field_two'=>'a3.m1.f2', 'field_three'=>'a3.m1.f3'));
		$result = Set::pushDiff($array1, $array3);

		$expected = array('ModelOne' => array('id'=>1001, 'field_one'=>'a1.m1.f1', 'field_two'=>'a1.m1.f2', 'field_three'=>'a3.m1.f3'));
		$this->assertIdentical($result, $expected);


		$array1 = array(
				0 => array('ModelOne' => array('id'=>1001, 'field_one'=>'s1.0.m1.f1', 'field_two'=>'s1.0.m1.f2')),
				1 => array('ModelTwo' => array('id'=>1002, 'field_one'=>'s1.1.m2.f2', 'field_two'=>'s1.1.m2.f2')));
		$array2 = array(
				0 => array('ModelOne' => array('id'=>1001, 'field_one'=>'s2.0.m1.f1', 'field_two'=>'s2.0.m1.f2')),
				1 => array('ModelTwo' => array('id'=>1002, 'field_one'=>'s2.1.m2.f2', 'field_two'=>'s2.1.m2.f2')));

		$result = Set::pushDiff($array1, $array2);
		$this->assertIdentical($result, $array1);

		$array3 = array(0 => array('ModelThree' => array('id'=>1003, 'field_one'=>'s3.0.m3.f1', 'field_two'=>'s3.0.m3.f2')));

		$result = Set::pushDiff($array1, $array3);
		$expected = array(
					0 => array('ModelOne' => array('id'=>1001, 'field_one'=>'s1.0.m1.f1', 'field_two'=>'s1.0.m1.f2'),
						'ModelThree' => array('id'=>1003, 'field_one'=>'s3.0.m3.f1', 'field_two'=>'s3.0.m3.f2')),
					1 => array('ModelTwo' => array('id'=>1002, 'field_one'=>'s1.1.m2.f2', 'field_two'=>'s1.1.m2.f2')));
		$this->assertIdentical($result, $expected);

		$result = Set::pushDiff($array1);
		$this->assertIdentical($result, $array1);

		$set =& new Set($array1);
		$result = $set->pushDiff($array2);
		$this->assertIdentical($result, $array1+$array2);
	}

	function testXmlSetReverse() {
		App::import('Core', 'Xml');

		$string = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
		<rss version="2.0">
		  <channel>
		  <title>Cake PHP Google Group</title>
		  <link>http://groups.google.com/group/cake-php</link>
		  <description>Search this group before posting anything. There are over 20,000 posts and it&amp;#39;s very likely your question was answered before. Visit the IRC channel #cakephp at irc.freenode.net for live chat with users and developers of Cake. If you post, tell us the version of Cake, PHP, and database.</description>
		  <language>en</language>
		  	<item>
			  <title>constructng result array when using findall</title>
			  <link>http://groups.google.com/group/cake-php/msg/49bc00f3bc651b4f</link>
			  <description>i&#39;m using cakephp to construct a logical data model array that will be &lt;br&gt; passed to a flex app. I have the following model association: &lt;br&gt; ServiceDay-&amp;gt;(hasMany)ServiceTi me-&amp;gt;(hasMany)ServiceTimePrice. So what &lt;br&gt; the current output from my findall is something like this example: &lt;br&gt; &lt;p&gt;Array( &lt;br&gt; [0] =&amp;gt; Array(</description>
			  <guid isPermaLink="true">http://groups.google.com/group/cake-php/msg/49bc00f3bc651b4f</guid>
			  <author>bmil...@gmail.com(bpscrugs)</author>
			  <pubDate>Fri, 28 Dec 2007 00:44:14 UT</pubDate>
			  </item>
			  <item>
			  <title>Re: share views between actions?</title>
			  <link>http://groups.google.com/group/cake-php/msg/8b350d898707dad8</link>
			  <description>Then perhaps you might do us all a favour and refrain from replying to &lt;br&gt; things you do not understand. That goes especially for asinine comments. &lt;br&gt; Indeed. &lt;br&gt; To sum up: &lt;br&gt; No comment. &lt;br&gt; In my day, a simple &amp;quot;RTFM&amp;quot; would suffice. I&#39;ll keep in mind to ignore any &lt;br&gt; further responses from you. &lt;br&gt; You (and I) were referring to the *online documentation*, not other</description>
			  <guid isPermaLink="true">http://groups.google.com/group/cake-php/msg/8b350d898707dad8</guid>
			  <author>subtropolis.z...@gmail.com(subtropolis zijn)</author>
			  <pubDate>Fri, 28 Dec 2007 00:45:01 UT</pubDate>
			 </item>
		</channel>
		</rss>';
		$xml = new Xml($string);
		$result = Set::reverse($xml);
		$expected = array('Rss' => array(
			'version' => '2.0',
			'Channel' => array(
				'title' => 'Cake PHP Google Group',
				'link' => 'http://groups.google.com/group/cake-php',
				'description' => 'Search this group before posting anything. There are over 20,000 posts and it&#39;s very likely your question was answered before. Visit the IRC channel #cakephp at irc.freenode.net for live chat with users and developers of Cake. If you post, tell us the version of Cake, PHP, and database.',
				'language' => 'en',
				'Item' => array(
					array(
						'title' => 'constructng result array when using findall',
						'link' => 'http://groups.google.com/group/cake-php/msg/49bc00f3bc651b4f',
						'description' => "i'm using cakephp to construct a logical data model array that will be <br> passed to a flex app. I have the following model association: <br> ServiceDay-&gt;(hasMany)ServiceTi me-&gt;(hasMany)ServiceTimePrice. So what <br> the current output from my findall is something like this example: <br><p>Array( <br> [0] =&gt; Array(",
						'guid' => array('isPermaLink' => 'true', 'value' => 'http://groups.google.com/group/cake-php/msg/49bc00f3bc651b4f'),
						'author' => 'bmil...@gmail.com(bpscrugs)',
						'pubDate' => 'Fri, 28 Dec 2007 00:44:14 UT',
					),
					array(
						'title' => 'Re: share views between actions?',
						'link' => 'http://groups.google.com/group/cake-php/msg/8b350d898707dad8',
						'description' => 'Then perhaps you might do us all a favour and refrain from replying to <br> things you do not understand. That goes especially for asinine comments. <br> Indeed. <br> To sum up: <br> No comment. <br> In my day, a simple &quot;RTFM&quot; would suffice. I\'ll keep in mind to ignore any <br> further responses from you. <br> You (and I) were referring to the *online documentation*, not other',
						'guid' => array('isPermaLink' => 'true', 'value' => 'http://groups.google.com/group/cake-php/msg/8b350d898707dad8'),
						'author' => 'subtropolis.z...@gmail.com(subtropolis zijn)',
						'pubDate' => 'Fri, 28 Dec 2007 00:45:01 UT'
					)
				)
			)
		));
		$this->assertEqual($result, $expected);
		$string ='<data><post title="Title of this post" description="cool" /></data>';

		$xml = new Xml($string);
		$result = Set::reverse($xml);
		$expected = array('Data' => array('Post' => array('title' => 'Title of this post', 'description' => 'cool')));
		$this->assertEqual($result, $expected);

		$xml = new Xml('<example><item><title>An example of a correctly reversed XMLNode</title><desc/></item></example>');
		$result = Set::reverse($xml);
		$expected = array('Example' => 
			array(
				'Item' => array(
					'title' => 'An example of a correctly reversed XMLNode',
					'Desc' => array(),
				)
			)
		);
		$this->assertIdentical($result, $expected);
		
		$xml = new Xml('<example><item attr="123"><titles><title>title1</title><title>title2</title></titles></item></example>');
		$result = Set::reverse($xml);
		$expected = 
			array('Example' => array(
				'Item' => array(
					'attr' => '123', 
					'Titles' => array(
						array('Title' => 'title1'),
						array('Title' => 'title2'),
					)					
				)
			)
		);
		$this->assertIdentical($result, $expected);
		
		$xml = new Xml('<example attr="ex_attr"><item attr="123"><titles>list</titles>textforitems</item></example>');
		$result = Set::reverse($xml);
		$expected = 
			array('Example' => array(
				'attr' => 'ex_attr',
				'Item' => array(
					'attr' => '123', 
					'titles' => 'list',
					'value'  => 'textforitems'
				)
			)
		);
		$this->assertIdentical($result, $expected);
	}

	function testStrictKeyCheck() {
		$set = new Set(array('a' => 'hi'));
		$this->assertFalse($set->check('a.b'));
	}
}

?>