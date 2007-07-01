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
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs.model
 * @since			CakePHP(tm) v 1.2.0.4206
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */

// Include the class to be tested
uses('set');

/**
 * UnitTestCase for the Set class
 *
 * @package		cake
 * @subpackage	cake.cake.libs
 */
class SetTest extends UnitTestCase {

	function testMerge() {
		// Test that passing in just 1 array returns it "as-is"
		$r = Set::merge(array('foo'));
		$this->assertIdentical($r, array('foo'));

		// Test that passing in a non-array turns it into one
		$r = Set::merge('foo');
		$this->assertIdentical($r, array('foo'));

		// Test that this works for 2 strings as well
		$r = Set::merge('foo', 'bar');
		$this->assertIdentical($r, array('foo', 'bar'));

		// Test that this works for arguments of mixed types as well
		$r = Set::merge('foo', array('user' => 'bob', 'no-bar'), 'bar');
		$this->assertIdentical($r, array('foo', 'user' => 'bob', 'no-bar', 'bar'));

		// Test merging two simple numerical indexed arrays
		$a = array('foo', 'foo2');
		$b = array('bar', 'bar2');
		$this->assertIdentical(Set::merge($a, $b), array('foo', 'foo2', 'bar', 'bar2'));

		// Test merging two simple associative arrays
		$a = array('foo' => 'bar', 'bar' => 'foo');
		$b = array('foo' => 'no-bar', 'bar' => 'no-foo');
		$this->assertIdentical(Set::merge($a, $b), array('foo' => 'no-bar', 'bar' => 'no-foo'));

		// Test merging two simple nested arrays
		$a = array('users' => array('bob', 'jim'));
		$b = array('users' => array('lisa', 'tina'));
		$this->assertIdentical(Set::merge($a, $b), array(
			'users' => array('bob', 'jim', 'lisa', 'tina')
		));

		// Test that merging an key holding a string over an array one causes an overwrite
		$a = array('users' => array('jim', 'bob'));
		$b = array('users' => 'none');
		$this->assertIdentical(Set::merge($a, $b), array('users' => 'none'));

		// Test merging two somewhat complex nested arrays
		$a = array(
			'users' => array(
				'lisa' => array(
					'id' => 5,
					'pw' => 'secret'
				)
			),
			'cakephp'
		);
		$b = array(
			'users' => array(
				'lisa' => array(
					'pw' => 'new-pass',
					'age' => 23
				)
			),
			'ice-cream'
		);
		$this->assertIdentical(Set::merge($a, $b), array(
			'users' => array(
				'lisa' => array(
					'id' => 5,
					'pw' => 'new-pass',
					'age' => 23
				)
			),
			'cakephp',
			'ice-cream'
		));

		// And now go for the ultimate tripple-play ; )
		$c = array(
			'users' => array(
				'lisa' => array(
					'pw' => 'you-will-never-guess',
					'age' => 25,
					'pet' => 'dog'
				)
			),
			'chocolate'
		);
		$expected = array(
			'users' => array(
				'lisa' => array(
					'id' => 5,
					'pw' => 'you-will-never-guess',
					'age' => 25,
					'pet' => 'dog'
				)
			),
			'cakephp',
			'ice-cream',
			'chocolate'
		);
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
	}

	function testExtract() {
		$a = array(
			array('Article' => array(
				'id' => 1, 'title' => 'Article 1'
			)),
			array('Article' => array(
				'id' => 2, 'title' => 'Article 2'
			)),
			array('Article' => array(
				'id' => 3, 'title' => 'Article 3'
			))
		);

		$result = Set::extract($a, '{n}.Article.id');
		$expected = array( 1, 2, 3 );
		$this->assertIdentical($result, $expected);

		$result = Set::extract($a, '{n}.Article.title');
		$expected = array( 'Article 1', 'Article 2', 'Article 3' );
		$this->assertIdentical($result, $expected);

		$a = array(
			array('Article' => array(
				'id' => 1, 'title' => 'Article 1',
				'User' => array(
					'id' => 1, 'username' => 'mariano.iglesias'
				)
			)),
			array('Article' => array(
				'id' => 2, 'title' => 'Article 2',
				'User' => array(
					'id' => 1, 'username' => 'mariano.iglesias'
				)
			)),
			array('Article' => array(
				'id' => 3, 'title' => 'Article 3',
				'User' => array(
					'id' => 2, 'username' => 'phpnut'
				)
			))
		);

		$result = Set::extract($a, '{n}.Article.User.username');
		$expected = array( 'mariano.iglesias', 'mariano.iglesias', 'phpnut' );
		$this->assertIdentical($result, $expected);

		$a = array(
			array('Article' => array(
				'id' => 1, 'title' => 'Article 1',
				'Comment' => array(
					array('id' => 10, 'title' => 'Comment 10'),
					array('id' => 11, 'title' => 'Comment 11'),
					array('id' => 12, 'title' => 'Comment 12'),
				)
			)),
			array('Article' => array(
				'id' => 2, 'title' => 'Article 2',
				'Comment' => array(
					array('id' => 13, 'title' => 'Comment 13'),
					array('id' => 14, 'title' => 'Comment 14')
				)
			)),
			array('Article' => array(
				'id' => 3, 'title' => 'Article 3'
			))
		);

		$result = Set::extract($a, '{n}.Article.Comment.{n}.id');
		$expected = array (
			array(10, 11, 12),
			array(13, 14),
			null
		);
		$this->assertIdentical($result, $expected);

		$result = Set::extract($a, '{n}.Article.Comment.{n}.title');
		$expected = array (
			array('Comment 10', 'Comment 11', 'Comment 12'),
			array('Comment 13', 'Comment 14'),
			null
		);
		$this->assertIdentical($result, $expected);

		$a = array(
			array( '1day' => '20 sales' ),
			array( '1day' => '2 sales' )
		);

		$result = Set::extract($a, '{n}.1day');
		$expected = array(
			'20 sales',
			'2 sales'
		);
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
		$a = array(
			array('User' => array(
				'id' => 2,
				'group_id' => 1,
				'Data' => array(
					'user' => 'mariano.iglesias',
					'name' => 'Mariano Iglesias'
				)
			)),
			array('User' => array(
				'id' => 14,
				'group_id' => 2,
				'Data' => array(
					'user' => 'phpnut',
					'name' => 'Larry E. Masters'
				)
			)),
			array('User' => array(
				'id' => 25,
				'group_id' => 1,
				'Data' => array(
					'user' => 'gwoo',
					'name' => 'The Gwoo'
				)
			))
		);

		$result = Set::combine($a, '{n}.User.id');
		$expected = array(
			2 => null,
			14 => null,
			25 => null
		);
		$this->assertIdentical($result, $expected);

		$result = Set::combine($a, '{n}.User.id', '{n}.User.Data');
		$expected = array(
			2 => array(
				'user' => 'mariano.iglesias',
				'name' => 'Mariano Iglesias'
			),
			14 => array(
				'user' => 'phpnut',
				'name' => 'Larry E. Masters'
			),
			25 => array(
				'user' => 'gwoo',
				'name' => 'The Gwoo'
			)
		);
		$this->assertIdentical($result, $expected);

		$result = Set::combine($a, '{n}.User.id', '{n}.User.Data.name');
		$expected = array(
			2 => 'Mariano Iglesias',
			14 => 'Larry E. Masters',
			25 => 'The Gwoo'
		);
		$this->assertIdentical($result, $expected);

		$result = Set::combine($a, '{n}.User.id', '{n}.User.Data', '{n}.User.group_id');
		$expected = array(
			1 => array(
				2 => array(
					'user' => 'mariano.iglesias',
					'name' => 'Mariano Iglesias'
				),
				25 => array(
					'user' => 'gwoo',
					'name' => 'The Gwoo'
				)
			),
			2 => array(
				14 => array(
					'user' => 'phpnut',
					'name' => 'Larry E. Masters'
				)
			)
		);
		$this->assertIdentical($result, $expected);

		$result = Set::combine($a, '{n}.User.id', '{n}.User.Data.name', '{n}.User.group_id');
		$expected = array(
			1 => array(
				2 => 'Mariano Iglesias',
				25 => 'The Gwoo'
			),
			2 => array(
				14 => 'Larry E. Masters'
			)
		);
		$this->assertIdentical($result, $expected);

		$Set =& new Set($a);

		$result = $Set->combine('{n}.User.id');
		$expected = array(
			2 => null,
			14 => null,
			25 => null
		);
		$this->assertIdentical($result, $expected);

		$result = $Set->combine('{n}.User.id', '{n}.User.Data');
		$expected = array(
			2 => array(
				'user' => 'mariano.iglesias',
				'name' => 'Mariano Iglesias'
			),
			14 => array(
				'user' => 'phpnut',
				'name' => 'Larry E. Masters'
			),
			25 => array(
				'user' => 'gwoo',
				'name' => 'The Gwoo'
			)
		);
		$this->assertIdentical($result, $expected);

		$result = $Set->combine('{n}.User.id', '{n}.User.Data.name');
		$expected = array(
			2 => 'Mariano Iglesias',
			14 => 'Larry E. Masters',
			25 => 'The Gwoo'
		);
		$this->assertIdentical($result, $expected);

		$result = $Set->combine('{n}.User.id', '{n}.User.Data', '{n}.User.group_id');
		$expected = array(
			1 => array(
				2 => array(
					'user' => 'mariano.iglesias',
					'name' => 'Mariano Iglesias'
				),
				25 => array(
					'user' => 'gwoo',
					'name' => 'The Gwoo'
				)
			),
			2 => array(
				14 => array(
					'user' => 'phpnut',
					'name' => 'Larry E. Masters'
				)
			)
		);
		$this->assertIdentical($result, $expected);

		$result = $Set->combine('{n}.User.id', '{n}.User.Data.name', '{n}.User.group_id');
		$expected = array(
			1 => array(
				2 => 'Mariano Iglesias',
				25 => 'The Gwoo'
			),
			2 => array(
				14 => 'Larry E. Masters'
			)
		);
		$this->assertIdentical($result, $expected);
	}
}

?>