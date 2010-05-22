<?php
/**
 * SessionTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
if (!class_exists('CakeSession')) {
	App::import('Core', 'CakeSession');
}

/**
 * CakeSessionTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class CakeSessionTest extends CakeTestCase {

/**
 * Fixtures used in the SessionTest
 *
 * @var array
 * @access public
 */
	var $fixtures = array('core.session');

/**
 * startCase method
 *
 * @access public
 * @return void
 */
	function startCase() {
		// Make sure garbage colector will be called
		$this->__gc_divisor = ini_get('session.gc_divisor');
		ini_set('session.gc_divisor', '1');
	}

/**
 * endCase method
 *
 * @access public
 * @return void
 */
	function endCase() {
		// Revert to the default setting
		ini_set('session.gc_divisor', $this->__gc_divisor);
	}

/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		$this->Session =& new CakeSession();
		$this->Session->start();
		$this->Session->_checkValid();
	}

/**
 * tearDown method
 *
 * @access public
 * @return void
 */
    function tearDown() {
        unset($_SESSION);
		session_destroy();
    }

/**
 * testSessionPath
 *
 * @access public
 * @return void
 */
	function testSessionPath() {
		$Session = new CakeSession('/index.php');
		$this->assertEqual('/', $Session->path);

		$Session = new CakeSession('/sub_dir/index.php');
		$this->assertEqual('/sub_dir/', $Session->path);

		$Session = new CakeSession('');
		$this->assertEqual('/', $Session->path, 'Session path is empty, with "" as $base needs to be / %s');
	}

/**
 * testCheck method
 *
 * @access public
 * @return void
 */
	function testCheck() {
		$this->Session->write('SessionTestCase', 'value');
		$this->assertTrue($this->Session->check('SessionTestCase'));

		$this->assertFalse($this->Session->check('NotExistingSessionTestCase'), false);
	}

/**
 * testSimpleRead method
 *
 * @access public
 * @return void
 */
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

		$this->Session->write('This.is.a.deep.array.my.friend', 'value');
		$result = $this->Session->read('This.is.a.deep.array.my.friend');
		$this->assertEqual('value', $result);
	}

/**
 * testId method
 *
 * @access public
 * @return void
 */
	function testId() {
		$expected = session_id();
		$result = $this->Session->id();
		$this->assertEqual($result, $expected);

		$this->Session->id('MySessionId');
		$result = $this->Session->id();
		$this->assertEqual($result, 'MySessionId');
	}

/**
 * testStarted method
 *
 * @access public
 * @return void
 */
	function testStarted() {
		$this->assertTrue($this->Session->started());

		unset($_SESSION);
		$_SESSION = null;
		$this->assertFalse($this->Session->started());
		$this->assertTrue($this->Session->start());

		$session = new CakeSession(null, false);
		$this->assertTrue($session->started());
		unset($session);
	}

/**
 * testError method
 *
 * @access public
 * @return void
 */
	function testError() {
		$this->Session->read('Does.not.exist');
		$result = $this->Session->error();
		$this->assertEqual($result, "Does.not.exist doesn't exist");

		$this->Session->delete('Failing.delete');
		$result = $this->Session->error();
		$this->assertEqual($result, "Failing.delete doesn't exist");
	}

/**
 * testDel method
 *
 * @access public
 * @return void
 */
	function testDelete() {
		$this->assertTrue($this->Session->write('Delete.me', 'Clearing out'));
		$this->assertTrue($this->Session->delete('Delete.me'));
		$this->assertFalse($this->Session->check('Delete.me'));
		$this->assertTrue($this->Session->check('Delete'));

		$this->assertTrue($this->Session->write('Clearing.sale', 'everything must go'));
		$this->assertTrue($this->Session->delete('Clearing'));
		$this->assertFalse($this->Session->check('Clearing.sale'));
		$this->assertFalse($this->Session->check('Clearing'));
	}

/**
 * testWatchVar method
 *
 * @access public
 * @return void
 */
	function testWatchVar() {
		$this->assertFalse($this->Session->watch(null));

		$this->Session->write('Watching', "I'm watching you");
		$this->Session->watch('Watching');
		$this->expectError('Writing session key {Watching}: "They found us!"');
		$this->Session->write('Watching', 'They found us!');

		$this->expectError('Deleting session key {Watching}');
		$this->Session->delete('Watching');

		$this->assertFalse($this->Session->watch('Invalid.key'));
	}

/**
 * testIgnore method
 *
 * @access public
 * @return void
 */
	function testIgnore() {
		$this->Session->write('Watching', "I'm watching you");
		$this->Session->watch('Watching');
		$this->Session->ignore('Watching');
		$this->assertTrue($this->Session->write('Watching', 'They found us!'));
	}

/**
 * testDestroy method
 *
 * @access public
 * @return void
 */
	function testDestroy() {
		$this->Session->write('bulletProof', 'invicible');
		$id = $this->Session->id();
		$this->Session->destroy();
		$this->assertFalse($this->Session->check('bulletProof'));
		$this->assertNotEqual($id, $this->Session->id());
	}

/**
 * testCheckingSavedEmpty method
 *
 * @access public
 * @return void
 */
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

/**
 * testCheckKeyWithSpaces method
 *
 * @access public
 * @return void
 */
	function testCheckKeyWithSpaces() {
		$this->assertTrue($this->Session->write('Session Test', "test"));
		$this->assertEqual($this->Session->check('Session Test'), 'test');
		$this->Session->delete('Session Test');

		$this->assertTrue($this->Session->write('Session Test.Test Case', "test"));
		$this->assertTrue($this->Session->check('Session Test.Test Case'));
	}

/**
 * test key exploitation
 *
 * @return void
 */
	function testKeyExploit() {
		$key = "a'] = 1; phpinfo(); \$_SESSION['a";
		$result = $this->Session->write($key, 'haxored');
		$this->assertTrue($result);

		$result = $this->Session->read($key);
		$this->assertEqual($result, 'haxored');
	}

/**
 * testReadingSavedEmpty method
 *
 * @access public
 * @return void
 */
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

/**
 * testCheckUserAgentFalse method
 *
 * @access public
 * @return void
 */
	function testCheckUserAgentFalse() {
		Configure::write('Session.checkAgent', false);
		$this->Session->_userAgent = md5('http://randomdomainname.com' . Configure::read('Security.salt'));
		$this->assertTrue($this->Session->valid());
	}

/**
 * testCheckUserAgentTrue method
 *
 * @access public
 * @return void
 */
	function testCheckUserAgentTrue() {
		Configure::write('Session.checkAgent', true);
		$this->Session->_userAgent = md5('http://randomdomainname.com' . Configure::read('Security.salt'));
		$this->assertFalse($this->Session->valid());
	}

/**
 * testReadAndWriteWithDatabaseStorage method
 *
 * @access public
 * @return void
 */
	function testReadAndWriteWithCakeStorage() {
		unset($_SESSION);
		session_destroy();
		ini_set('session.save_handler', 'files');
		Configure::write('Session.save', 'cake');
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

		$this->Session->write('SessionTestCase', 'This is a Test');
		$this->Session->write('SessionTestCase', 'This was updated');
		$this->assertEqual($this->Session->read('SessionTestCase'), 'This was updated');

		$this->Session->destroy();
		$this->assertFalse($this->Session->read('SessionTestCase'));
	}

/**
 * testReadAndWriteWithDatabaseStorage method
 *
 * @access public
 * @return void
 */
	function testReadAndWriteWithCacheStorage() {
		unset($_SESSION);
		session_destroy();
		ini_set('session.save_handler', 'files');
		Configure::write('Session.save', 'cache');
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

		$this->Session->write('SessionTestCase', 'This is a Test');
		$this->Session->write('SessionTestCase', 'This was updated');
		$this->assertEqual($this->Session->read('SessionTestCase'), 'This was updated');

		$this->Session->destroy();
		$this->assertFalse($this->Session->read('SessionTestCase'));
	}

/**
 * testReadAndWriteWithDatabaseStorage method
 *
 * @access public
 * @return void
 */
	function testReadAndWriteWithDatabaseStorage() {
		unset($_SESSION);
		session_destroy();
		Configure::write('Session.table', 'sessions');
		Configure::write('Session.model', 'Session');
		Configure::write('Session.database', 'test_suite');
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

        $this->Session->write('SessionTestCase', 'Some additional data');
        $this->assertEqual($this->Session->read('SessionTestCase'), 'Some additional data');

		$this->Session->destroy();
		$this->assertFalse($this->Session->read('SessionTestCase'));
		session_write_close();

		unset($_SESSION);
		ini_set('session.save_handler', 'files');
		Configure::write('Session.save', 'php');
		$this->setUp();
	}

}
