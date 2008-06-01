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
App::import('Core', 'Session');
/**
 * Short description for class.
 *
 * @package    cake.tests
 * @subpackage cake.tests.cases.libs
 */
class SessionTest extends CakeTestCase {
	var $fixtures = array('core.session');  //using fixtures really messes things up. but should eventually be used.
	
	function setUp() {
		restore_error_handler();

		@$this->Session =& new CakeSession();
		$this->Session->start();
		$this->Session->_checkValid();

		set_error_handler('simpleTestErrorHandler');
	}

	function testCheck() {
		$this->Session->write('SessionTestCase', 'value');
		$this->assertTrue($this->Session->check('SessionTestCase'));

		$this->assertFalse($this->Session->check('NotExistingSessionTestCase'), false);
	}
	
	function testSimpleRead() {
		$this->Session->write('testing', '1,2,3');
		$result = $this->Session->read('testing');
		$this->assertEqual($result, '1,2,3');
		
		$this->Session->write('testing', array('1' => 'one', '2' => 'two','3' => 'three'));
		$result = $this->Session->read('testing.1');
		$this->assertEqual($result, 'one');
		
		$result = $this->Session->read('testing');
		$this->assertEqual($result, array('1' => 'one', '2' => 'two', '3' => 'three'));
		
		$result = $this->Session->read();
		$this->assertTrue(isset($result['testing']));
		$this->assertTrue(isset($result['Config']));
		$this->assertTrue(isset($result['Config']['userAgent']));
	}
	
	function testId() {
		$expected = session_id();
		$result = $this->Session->id();
		$this->assertEqual($result, $expected);
		
		$this->Session->id('MySessionId');
		$result = $this->Session->id();
		$this->assertEqual($result, 'MySessionId');
	}
	
	function testStarted() {
		$this->assertTrue($this->Session->started());
		
		unset($_SESSION);
		$this->assertFalse($this->Session->started());		
		$this->assertTrue($this->Session->start());
	}
	
	function testError() {
		$this->Session->read('Does.not.exist');
		$result = $this->Session->error();
		$this->assertEqual($result, "Does.not.exist doesn't exist");
		
		$this->Session->del('Failing.delete');
		$result = $this->Session->error();
		$this->assertEqual($result, "Failing.delete doesn't exist");
	}
	
	function testDel() {
		$this->assertTrue($this->Session->write('Delete.me', 'Clearing out'));
		$this->assertTrue($this->Session->del('Delete.me'));
		$this->assertFalse($this->Session->check('Delete.me'));
		$this->assertTrue($this->Session->check('Delete'));
		
		$this->assertTrue($this->Session->write('Clearing.sale', 'everything must go'));
		$this->assertTrue($this->Session->del('Clearing'));
		$this->assertFalse($this->Session->check('Clearing.sale'));
		$this->assertFalse($this->Session->check('Clearing'));
	}
	
	function testWatchVar() {
		$this->assertFalse($this->Session->watch(null));
		
		$this->Session->write('Watching', "I'm watching you");
		$this->Session->watch('Watching');
		$this->expectError('Writing session key {Watching}: "They found us!"');
		$this->Session->write('Watching', 'They found us!');	
		
		$this->expectError('Deleting session key {Watching}');
		$this->Session->del('Watching');
			
		$this->assertFalse($this->Session->watch('Invalid.key'));		
	}
	
	function testIgnore() {
		$this->Session->write('Watching', "I'm watching you");
		$this->Session->watch('Watching');
		$this->Session->ignore('Watching');
		$this->assertTrue($this->Session->write('Watching', 'They found us!'));
	}
	
	function testDestroy() {
		$this->Session->write('bulletProof', 'invicible');
		$id = $this->Session->id();
		$this->Session->destroy();
		$this->assertFalse($this->Session->check('bulletProof'));
		$this->assertNotEqual($id, $this->Session->id());
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

	function testCheckUserAgentFalse() {
		Configure::write('Session.checkAgent', false);
		$this->Session->_userAgent = md5('http://randomdomainname.com' . Configure::read('Security.salt'));
		$this->assertTrue($this->Session->valid());
	}

	function testCheckUserAgentTrue() {
		Configure::write('Session.checkAgent', true);
		$this->Session->_userAgent = md5('http://randomdomainname.com' . Configure::read('Security.salt'));
		$this->assertFalse($this->Session->valid());
	}

	function testReadAndWriteWithDatabaseStorage() {
		$this->tearDown();
		$this->loadFixtures('Session');
		unset($_SESSION);
		
		Configure::write('Session.table', 'sessions');
		Configure::write('Session.database', 'default');
		Configure::write('Session.save', 'database');

		$this->setUp();

		$this->Session->write('SessionTestCase', 0);
		$this->assertEqual($this->Session->read('SessionTestCase'), 0);

		$this->Session->write('SessionTestCase', '0');
		$this->assertEqual($this->Session->read('SessionTestCase'), '0');
		$this->assertFalse($this->Session->read('SessionTestCase') === 0);

		$this->Session->write('SessionTestCase', false);
		$this->assertFalse($this->Session->read('SessionTestCase'));

		$this->Session->write('SessionTestCase', null);
		$this->assertEqual($this->Session->read('SessionTestCase'), null);

		$this->Session->write('SessionTestCase', 'This is a Test');
		$this->assertEqual($this->Session->read('SessionTestCase'), 'This is a Test');
	}

	function tearDown() {
		$this->Session->del('SessionTestCase');
		unset($this->Session);
	}
}

?>