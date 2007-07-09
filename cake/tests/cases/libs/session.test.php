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
 * @since			CakePHP(tm) v 1.2.0.4206
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
uses('session');
/**
 * Short description for class.
 *
 * @package    cake.tests
 * @subpackage cake.tests.cases.libs
 */
class SessionTest extends UnitTestCase {

	function setUp() {
		restore_error_handler();

		@$this->Session =& new CakeSession();

		set_error_handler('simpleTestErrorHandler');
	}

	function testCheck() {
		$this->Session->write('SessionTestCase', 'value');
		$this->assertTrue($this->Session->check('SessionTestCase'));

		$this->assertFalse($this->Session->check('NotExistingSessionTestCase'), false);
	}

	function testCheckingSavedEmpty() {
		$this->assertTrue($this->Session->write('SessionTestCase', 0));
		$this->assertTrue($this->Session->check('SessionTestCase'));

		$this->assertTrue($this->Session->write('SessionTestCase', '0'));
		$this->assertTrue($this->Session->check('SessionTestCase'));

		$this->assertTrue($this->Session->write('SessionTestCase', false));
		$this->assertTrue($this->Session->check('SessionTestCase'));

		$this->assertTrue($this->Session->write('SessionTestCase', null));
		$this->assertFalse($this->Session->check('SessionTestCase'));
	}

	function testCheckKeyWithSpaces() {
		$this->assertTrue($this->Session->write('Session Test', "test"));
		$this->assertEqual($this->Session->check('Session Test'), 'test');
		$this->Session->del('Session Test');

		$this->assertTrue($this->Session->write('Session Test.Test Case', "test"));
		$this->assertTrue($this->Session->check('Session Test.Test Case'));
	}

	function testReadingSavedEmpty() {
		$this->Session->write('SessionTestCase', 0);
		$this->assertEqual($this->Session->read('SessionTestCase'), 0);

		$this->Session->write('SessionTestCase', '0');
		$this->assertEqual($this->Session->read('SessionTestCase'), '0');
		$this->assertFalse($this->Session->read('SessionTestCase') === 0);

		$this->Session->write('SessionTestCase', false);
		$this->assertFalse($this->Session->read('SessionTestCase'));

		$this->Session->write('SessionTestCase', null);
		$this->assertEqual($this->Session->read('SessionTestCase'), null);
	}

	function tearDown() {
		$this->Session->del('SessionTestCase');
		unset($this->Session);
	}
}

?>