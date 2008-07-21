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
 * @since			CakePHP(tm) v 1.2.0.5432
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', 'String');
/**
 * Short description for class.
 *
 * @package    cake.tests
 * @subpackage cake.tests.cases.libs
 */
class StringTest extends CakeTestCase {
/**
 * testUuidGeneration method
 *
 * @access public
 * @return void
 */
	function testUuidGeneration() {
		$result = String::uuid();
		$match = preg_match("/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/", $result);
		$this->assertTrue($match);
	}
/**
 * testMultipleUuidGeneration method
 *
 * @access public
 * @return void
 */
	function testMultipleUuidGeneration() {
		$check = array();
		$count = mt_rand(10, 1000);
		for($i = 0; $i < $count; $i++) {
			$result = String::uuid();
			$match = preg_match("/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/", $result);
			$this->assertTrue($match);
			$this->assertFalse(in_array($result, $check));
			$check[] = $result;
		}
	}
/**
 * testInsert method
 *
 * @access public
 * @return void
 */
	function testInsert() {
		$string = '2 + 2 = :sum. Cake is :adjective.';
		$expected = '2 + 2 = 4. Cake is yummy.';
		$result = String::insert($string, array('sum' => '4', 'adjective' => 'yummy'));
		$this->assertEqual($result, $expected);

		$string = '2 + 2 = %sum. Cake is %adjective.';
		$result = String::insert($string, array('sum' => '4', 'adjective' => 'yummy'), array('before' => '%'));
		$this->assertEqual($result, $expected);

		$string = '2 + 2 = 2sum2. Cake is 9adjective9.';
		$result = String::insert($string, array('sum' => '4', 'adjective' => 'yummy'), array('format' => '/([\d])%s\\1/'));
		$this->assertEqual($result, $expected);

		$string = '2 + 2 = 12sum21. Cake is 23adjective45.';
		$expected = '2 + 2 = 4. Cake is 23adjective45.';
		$result = String::insert($string, array('sum' => '4', 'adjective' => 'yummy'), array('format' => '/([\d])([\d])%s\\2\\1/'));
		$this->assertEqual($result, $expected);

		$string = '2 + 2 = <sum. Cake is <adjective>.';
		$expected = '2 + 2 = <sum. Cake is yummy.';
		$result = String::insert($string, array('sum' => '4', 'adjective' => 'yummy'), array('before' => '<', 'after' => '>'));
		$this->assertEqual($result, $expected);

		$string = '2 + 2 = \:sum. Cake is :adjective.';
		$expected = '2 + 2 = :sum. Cake is yummy.';
		$result = String::insert($string, array('sum' => '4', 'adjective' => 'yummy'));
		$this->assertEqual($result, $expected);

		$string = '2 + 2 = !:sum. Cake is :adjective.';
		$result = String::insert($string, array('sum' => '4', 'adjective' => 'yummy'), array('escape' => '!'));
		$this->assertEqual($result, $expected);

		$string = '2 + 2 = \%sum. Cake is %adjective.';
		$expected = '2 + 2 = %sum. Cake is yummy.';
		$result = String::insert($string, array('sum' => '4', 'adjective' => 'yummy'), array('before' => '%'));
		$this->assertEqual($result, $expected);

		$string = ':a :b \:a :a';
		$expected = '1 2 :a 1';
		$result = String::insert($string, array('a' => 1, 'b' => 2));
		$this->assertEqual($result, $expected);

		$string = ':a :b :c';
		$expected = '2 3';
		$result = String::insert($string, array('b' => 2, 'c' => 3), array('clean' => true));
		$this->assertEqual($result, $expected);

		$string = ':a :b :c';
		$expected = '1 3';
		$result = String::insert($string, array('a' => 1, 'c' => 3), array('clean' => true));
		$this->assertEqual($result, $expected);

		$string = ':a :b :c';
		$expected = '2 3';
		$result = String::insert($string, array('b' => 2, 'c' => 3), array('clean' => true));
		$this->assertEqual($result, $expected);

		$string = ':a, :b and :c';
		$expected = '2 and 3';
		$result = String::insert($string, array('b' => 2, 'c' => 3), array('clean' => true));
		$this->assertEqual($result, $expected);

		$string = '":a, :b and :c"';
		$expected = '"1, 2"';
		$result = String::insert($string, array('a' => 1, 'b' => 2), array('clean' => true));
		$this->assertEqual($result, $expected);

		$string = '"${a}, ${b} and ${c}"';
		$expected = '"1, 2"';
		$result = String::insert($string, array('a' => 1, 'b' => 2), array('before' => '${', 'after' => '}', 'clean' => true));
		$this->assertEqual($result, $expected);

		$string = '<img src=":src" alt=":alt" class="foo :extra bar"/>';
		$expected = '<img src="foo" class="foo bar"/>';
		$result = String::insert($string, array('src' => 'foo'), array('clean' => 'html'));

		$this->assertEqual($result, $expected);

		$string = '<img src=":src" class=":no :extra"/>';
		$expected = '<img src="foo"/>';
		$result = String::insert($string, array('src' => 'foo'), array('clean' => 'html'));
		$this->assertEqual($result, $expected);

		$string = '<img src=":src" class=":no :extra"/>';
		$expected = '<img src="foo" class="bar"/>';
		$result = String::insert($string, array('src' => 'foo', 'extra' => 'bar'), array('clean' => 'html'));
		$this->assertEqual($result, $expected);

		$result = String::insert("this is a ? string", "test");
		$expected = "this is a test string";
		$this->assertEqual($result, $expected);

		$result = String::insert("this is a ? string with a ? ? ?", array('long', 'few?', 'params', 'you know'));
		$expected = "this is a long string with a few? params you know";
		$this->assertEqual($result, $expected);

		$result = String::insert('update saved_urls set url = :url where id = :id', array('url' => 'http://www.testurl.com/param1:url/param2:id','id' => 1));   
		$expected = "update saved_urls set url = http://www.testurl.com/param1:url/param2:id where id = 1";
		$this->assertEqual($result, $expected);

		$result = String::insert('update saved_urls set url = :url where id = :id', array('id' => 1, 'url' => 'http://www.testurl.com/param1:url/param2:id'));   
		$expected = "update saved_urls set url = http://www.testurl.com/param1:url/param2:id where id = 1";
		$this->assertEqual($result, $expected);
		
		$result = String::insert(':me cake. :subject :verb fantastic.', array('me' => 'I :verb', 'subject' => 'cake', 'verb' => 'is'));   
		$expected = "I :verb cake. cake is fantastic.";
		$this->assertEqual($result, $expected);

	}
/**
 * testTokenize method
 *
 * @access public
 * @return void
 */
	function testTokenize() {
		$result = String::tokenize('A,(short,boring test)');
		$expected = array('A', '(short,boring test)');
		$this->assertEqual($result, $expected);

		$result = String::tokenize('A,(short,more interesting( test)');
		$expected = array('A', '(short,more interesting( test)');
		$this->assertEqual($result, $expected);

		$result = String::tokenize('A,(short,very interesting( test))');
		$expected = array('A', '(short,very interesting( test))');
		$this->assertEqual($result, $expected);

		$result = String::tokenize('"single tag"', ' ', '"', '"');
		$expected = array('"single tag"');
		$this->assertEqual($expected, $result);

		$result = String::tokenize('tagA "single tag" tagB', ' ', '"', '"');
		$expected = array('tagA', '"single tag"', 'tagB');
		$this->assertEqual($expected, $result);
	}
}
?>