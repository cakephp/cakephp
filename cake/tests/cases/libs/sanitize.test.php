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
 * @subpackage		cake.tests.cases.libs
 * @since			CakePHP(tm) v 1.2.0.5428
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
uses('sanitize');
/**
 * Short description for class.
 *
 * @package    cake.tests
 * @subpackage cake.tests.cases.libs
 */
class SanitizeTest extends CakeTestCase {

	function startTest($method) {
		parent::startTest($method);
		$this->_initDb();
	}

	function testEscapeAlphaNumeric() {
		$resultAlpha = Sanitize::escape('abc', 'test_suite');
		$this->assertEqual($resultAlpha, 'abc');

		$resultNumeric = Sanitize::escape('123', 'test_suite');
		$this->assertEqual($resultNumeric, '123');

		$resultNumeric = Sanitize::escape(1234, 'test_suite');
		$this->assertEqual($resultNumeric, 1234);

		$resultNumeric = Sanitize::escape(1234.23, 'test_suite');
		$this->assertEqual($resultNumeric, 1234.23);

		$resultNumeric = Sanitize::escape('#1234.23', 'test_suite');
		$this->assertEqual($resultNumeric, '#1234.23');

		$resultNull = Sanitize::escape(null, 'test_suite');
		$this->assertEqual($resultNull, null);

		$resultNull = Sanitize::escape(false, 'test_suite');
		$this->assertEqual($resultNull, false);

		$resultNull = Sanitize::escape(true, 'test_suite');
		$this->assertEqual($resultNull, true);
	}

	function testClean() {
		$string = 'test & "quote" \'other\' ;.$ symbol.' . "\r" . 'another line';
		$expected = 'test &amp; &quot;quote&quot; &#39;other&#39; ;.$ symbol.another line';
		$result = Sanitize::clean($string, array('connection' => 'test_suite'));
		$this->assertEqual($result, $expected);

		$string = 'test & "quote" \'other\' ;.$ symbol.' . "\r" . 'another line';
		$expected = 'test & ' . Sanitize::escape('"quote"', 'test_suite') . ' ' . Sanitize::escape('\'other\'', 'test_suite') . ' ;.$ symbol.another line';
		$result = Sanitize::clean($string, array('encode' => false, 'connection' => 'test_suite'));
		$this->assertEqual($result, $expected);

		$string = 'test & "quote" \'other\' ;.$ \\$ symbol.' . "\r" . 'another line';
		$expected = 'test & "quote" \'other\' ;.$ $ symbol.another line';
		$result = Sanitize::clean($string, array('encode' => false, 'escape' => false, 'connection' => 'test_suite'));
		$this->assertEqual($result, $expected);

		$string = 'test & "quote" \'other\' ;.$ \\$ symbol.' . "\r" . 'another line';
		$expected = 'test & "quote" \'other\' ;.$ \\$ symbol.another line';
		$result = Sanitize::clean($string, array('encode' => false, 'escape' => false, 'dollar' => false, 'connection' => 'test_suite'));
		$this->assertEqual($result, $expected);

		$string = 'test & "quote" \'other\' ;.$ symbol.' . "\r" . 'another line';
		$expected = 'test & "quote" \'other\' ;.$ symbol.' . "\r" . 'another line';
		$result = Sanitize::clean($string, array('encode' => false, 'escape' => false, 'carriage' => false, 'connection' => 'test_suite'));
		$this->assertEqual($result, $expected);

		$array = array(array('test & "quote" \'other\' ;.$ symbol.' . "\r" . 'another line'));
		$expected = array(array('test &amp; &quot;quote&quot; &#39;other&#39; ;.$ symbol.another line'));
		$result = Sanitize::clean($array, array('connection' => 'test_suite'));
		$this->assertEqual($result, $expected);

		$array = array(array('test & "quote" \'other\' ;.$ \\$ symbol.' . "\r" . 'another line'));
		$expected = array(array('test & "quote" \'other\' ;.$ $ symbol.another line'));
		$result = Sanitize::clean($array, array('encode' => false, 'escape' => false, 'connection' => 'test_suite'));
		$this->assertEqual($result, $expected);

		$array = array(array('test odd '.chr(0xCA).' spaces'.chr(0xCA)));
		$expected = array(array('test odd '.chr(0xCA).' spaces'.chr(0xCA)));
		$result = Sanitize::clean($array, array('odd_spaces' => false, 'escape' => false, 'connection' => 'test_suite'));
		$this->assertEqual($result, $expected);

		$array = array(array('\\$', array('key' => 'test & "quote" \'other\' ;.$ \\$ symbol.' . "\r" . 'another line')));
		$expected = array(array('$', array('key' => 'test & "quote" \'other\' ;.$ $ symbol.another line')));
		$result = Sanitize::clean($array, array('encode' => false, 'escape' => false));
		$this->assertEqual($result, $expected);
	}
}
?>