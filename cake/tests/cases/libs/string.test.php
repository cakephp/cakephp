<?php
/**
 * StringTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', 'String');

/**
 * StringTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
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
		$pattern = "/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/";
		$match = preg_match($pattern, $result);
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
		$pattern = "/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/";

		for($i = 0; $i < $count; $i++) {
			$result = String::uuid();
			$match = preg_match($pattern, $result);
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
		$string = 'some string';
		$expected = 'some string';
		$result = String::insert($string, array());
		$this->assertEqual($result, $expected);

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

		$string = ':web :web_site';
		$expected = 'www http';
		$result = String::insert($string, array('web' => 'www', 'web_site' => 'http'));
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

		$result = String::insert(':I.am: :not.yet: passing.', array('I.am' => 'We are'), array('before' => ':', 'after' => ':', 'clean' => array('replacement' => ' of course', 'method' => 'text')));
		$expected = "We are of course passing.";
		$this->assertEqual($result, $expected);

		$result = String::insert(
			':I.am: :not.yet: passing.',
			array('I.am' => 'We are'),
			array('before' => ':', 'after' => ':', 'clean' => true)
		);
		$expected = "We are passing.";
		$this->assertEqual($result, $expected);

		$result = String::insert('?-pended result', array('Pre'));
		$expected = "Pre-pended result";
		$this->assertEqual($result, $expected);

		$string = 'switching :timeout / :timeout_count';
		$expected = 'switching 5 / 10';
		$result = String::insert($string, array('timeout' => 5, 'timeout_count' => 10));
		$this->assertEqual($result, $expected);

		$string = 'switching :timeout / :timeout_count';
		$expected = 'switching 5 / 10';
		$result = String::insert($string, array('timeout_count' => 10, 'timeout' => 5));
		$this->assertEqual($result, $expected);

		$string = 'switching :timeout_count by :timeout';
		$expected = 'switching 10 by 5';
		$result = String::insert($string, array('timeout' => 5, 'timeout_count' => 10));
		$this->assertEqual($result, $expected);

		$string = 'switching :timeout_count by :timeout';
		$expected = 'switching 10 by 5';
		$result = String::insert($string, array('timeout_count' => 10, 'timeout' => 5));
		$this->assertEqual($result, $expected);
	}

/**
 * test Clean Insert
 *
 * @return void
 */
	function testCleanInsert() {
		$result = String::cleanInsert(':incomplete', array(
			'clean' => true, 'before' => ':', 'after' => ''
		));
		$this->assertEqual($result, '');

		$result = String::cleanInsert(':incomplete', array(
			'clean' => array('method' => 'text', 'replacement' => 'complete'),
			'before' => ':', 'after' => '')
		);
		$this->assertEqual($result, 'complete');

		$result = String::cleanInsert(':in.complete', array(
			'clean' => true, 'before' => ':', 'after' => ''
		));
		$this->assertEqual($result, '');

		$result = String::cleanInsert(':in.complete and', array(
			'clean' => true, 'before' => ':', 'after' => '')
		);
		$this->assertEqual($result, '');

		$result = String::cleanInsert(':in.complete or stuff', array(
			'clean' => true, 'before' => ':', 'after' => ''
		));
		$this->assertEqual($result, 'stuff');

		$result = String::cleanInsert(
			'<p class=":missing" id=":missing">Text here</p>',
			array('clean' => 'html', 'before' => ':', 'after' => '')
		);
		$this->assertEqual($result, '<p>Text here</p>');
	}

/**
 * Tests that non-insertable variables (i.e. arrays) are skipped when used as values in
 * String::insert().
 *
 * @return void
 */
	function testAutoIgnoreBadInsertData() {
		$data = array('foo' => 'alpha', 'bar' => 'beta', 'fale' => array());
		$result = String::insert('(:foo > :bar || :fale!)', $data, array('clean' => 'text'));
		$this->assertEqual($result, '(alpha > beta || !)');
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
	
	function testReplaceWithQuestionMarkInString() {
		$string = ':a, :b and :c?';
		$expected = '2 and 3?';
		$result = String::insert($string, array('b' => 2, 'c' => 3), array('clean' => true));
		$this->assertEqual($expected, $result);
	}
}
