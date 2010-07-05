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

class TestCakeSession extends CakeSession {
	public static function setUserAgent($value) {
		self::$_userAgent = $value;
	}
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
	public $fixtures = array('core.session');

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
	function startTest() {
		TestCakeSession::init();
		TestCakeSession::destroy();
		TestCakeSession::$watchKeys = array();
	}

/**
 * tearDown method
 *
 * @access public
 * @return void
 */
    function endTest() {
        unset($_SESSION);
		@session_destroy();
    }

/**
 * testSessionPath
 *
 * @access public
 * @return void
 */
	function testSessionPath() {
//		$Session = new CakeSession('/index.php');
		TestCakeSession::init('/index.php');
		$this->assertEqual('/', TestCakeSession::$path);

		TestCakeSession::init('/sub_dir/index.php');
		$this->assertEqual('/sub_dir/', TestCakeSession::$path);

		TestCakeSession::init('');
		$this->assertEqual('/', TestCakeSession::$path, 'Session path is empty, with "" as $base needs to be / %s');
	}

/**
 * testCheck method
 *
 * @access public
 * @return void
 */
	function testCheck() {
		TestCakeSession::write('SessionTestCase', 'value');
		$this->assertTrue(TestCakeSession::check('SessionTestCase'));

		$this->assertFalse(TestCakeSession::check('NotExistingSessionTestCase'), false);
	}

/**
 * testSimpleRead method
 *
 * @access public
 * @return void
 */
	function testSimpleRead() {
		TestCakeSession::write('testing', '1,2,3');
		$result = TestCakeSession::read('testing');
		$this->assertEqual($result, '1,2,3');

		TestCakeSession::write('testing', array('1' => 'one', '2' => 'two','3' => 'three'));
		$result = TestCakeSession::read('testing.1');
		$this->assertEqual($result, 'one');

		$result = TestCakeSession::read('testing');
		$this->assertEqual($result, array('1' => 'one', '2' => 'two', '3' => 'three'));

		$result = TestCakeSession::read();
		$this->assertTrue(isset($result['testing']));
		$this->assertTrue(isset($result['Config']));
		$this->assertTrue(isset($result['Config']['userAgent']));

		TestCakeSession::write('This.is.a.deep.array.my.friend', 'value');
		$result = TestCakeSession::read('This.is.a.deep.array.my.friend');
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
		$result = TestCakeSession::id();
		$this->assertEqual($result, $expected);

		TestCakeSession::id('MySessionId');
		$result = TestCakeSession::id();
		$this->assertEqual($result, 'MySessionId');
	}

/**
 * testStarted method
 *
 * @access public
 * @return void
 */
	function testStarted() {
		$this->assertTrue(TestCakeSession::started());

		unset($_SESSION);
		$_SESSION = null;
		$this->assertFalse(TestCakeSession::started());
		$this->assertTrue(TestCakeSession::start());
	}

/**
 * testError method
 *
 * @access public
 * @return void
 */
	function testError() {
		TestCakeSession::read('Does.not.exist');
		$result = TestCakeSession::error();
		$this->assertEqual($result, "Does.not.exist doesn't exist");

		TestCakeSession::delete('Failing.delete');
		$result = TestCakeSession::error();
		$this->assertEqual($result, "Failing.delete doesn't exist");
	}

/**
 * testDel method
 *
 * @access public
 * @return void
 */
	function testDelete() {
		$this->assertTrue(TestCakeSession::write('Delete.me', 'Clearing out'));
		$this->assertTrue(TestCakeSession::delete('Delete.me'));
		$this->assertFalse(TestCakeSession::check('Delete.me'));
		$this->assertTrue(TestCakeSession::check('Delete'));

		$this->assertTrue(TestCakeSession::write('Clearing.sale', 'everything must go'));
		$this->assertTrue(TestCakeSession::delete('Clearing'));
		$this->assertFalse(TestCakeSession::check('Clearing.sale'));
		$this->assertFalse(TestCakeSession::check('Clearing'));
	}

/**
 * testWatchVar method
 *
 * @access public
 * @return void
 */
	function testWatchVar() {
		$this->assertFalse(TestCakeSession::watch(null));

		TestCakeSession::write('Watching', "I'm watching you");
		TestCakeSession::watch('Watching');
		$this->expectError('Writing session key {Watching}: "They found us!"');
		TestCakeSession::write('Watching', 'They found us!');

		$this->expectError('Deleting session key {Watching}');
		TestCakeSession::delete('Watching');

		$this->assertFalse(TestCakeSession::watch('Invalid.key'));
	}

/**
 * testIgnore method
 *
 * @access public
 * @return void
 */
	function testIgnore() {
		TestCakeSession::write('Watching', "I'm watching you");
		TestCakeSession::watch('Watching');
		TestCakeSession::ignore('Watching');
		$this->assertTrue(TestCakeSession::write('Watching', 'They found us!'));
	}

/**
 * testDestroy method
 *
 * @access public
 * @return void
 */
	function testDestroy() {
		TestCakeSession::write('bulletProof', 'invicible');
		$id = TestCakeSession::id();
		TestCakeSession::destroy();
		$this->assertFalse(TestCakeSession::check('bulletProof'));
		$this->assertNotEqual($id, TestCakeSession::id());
	}

/**
 * testCheckingSavedEmpty method
 *
 * @access public
 * @return void
 */
	function testCheckingSavedEmpty() {
		$this->assertTrue(TestCakeSession::write('SessionTestCase', 0));
		$this->assertTrue(TestCakeSession::check('SessionTestCase'));

		$this->assertTrue(TestCakeSession::write('SessionTestCase', '0'));
		$this->assertTrue(TestCakeSession::check('SessionTestCase'));

		$this->assertTrue(TestCakeSession::write('SessionTestCase', false));
		$this->assertTrue(TestCakeSession::check('SessionTestCase'));

		$this->assertTrue(TestCakeSession::write('SessionTestCase', null));
		$this->assertFalse(TestCakeSession::check('SessionTestCase'));
	}

/**
 * testCheckKeyWithSpaces method
 *
 * @access public
 * @return void
 */
	function testCheckKeyWithSpaces() {
		$this->assertTrue(TestCakeSession::write('Session Test', "test"));
		$this->assertEqual(TestCakeSession::check('Session Test'), 'test');
		TestCakeSession::delete('Session Test');

		$this->assertTrue(TestCakeSession::write('Session Test.Test Case', "test"));
		$this->assertTrue(TestCakeSession::check('Session Test.Test Case'));
	}

/**
 * test key exploitation
 *
 * @return void
 */
	function testKeyExploit() {
		$key = "a'] = 1; phpinfo(); \$_SESSION['a";
		$result = TestCakeSession::write($key, 'haxored');
		$this->assertTrue($result);

		$result = TestCakeSession::read($key);
		$this->assertEqual($result, 'haxored');
	}

/**
 * testReadingSavedEmpty method
 *
 * @access public
 * @return void
 */
	function testReadingSavedEmpty() {
		TestCakeSession::write('SessionTestCase', 0);
		$this->assertEqual(TestCakeSession::read('SessionTestCase'), 0);

		TestCakeSession::write('SessionTestCase', '0');
		$this->assertEqual(TestCakeSession::read('SessionTestCase'), '0');
		$this->assertFalse(TestCakeSession::read('SessionTestCase') === 0);

		TestCakeSession::write('SessionTestCase', false);
		$this->assertFalse(TestCakeSession::read('SessionTestCase'));

		TestCakeSession::write('SessionTestCase', null);
		$this->assertEqual(TestCakeSession::read('SessionTestCase'), null);
	}

/**
 * testCheckUserAgentFalse method
 *
 * @access public
 * @return void
 */
	function testCheckUserAgentFalse() {
		Configure::write('Session.checkAgent', false);
		TestCakeSession::setUserAgent(md5('http://randomdomainname.com' . Configure::read('Security.salt')));
		$this->assertTrue(TestCakeSession::valid());
	}

/**
 * testCheckUserAgentTrue method
 *
 * @access public
 * @return void
 */
	function testCheckUserAgentTrue() {
		Configure::write('Session.checkAgent', true);
		TestCakeSession::setUserAgent(md5('http://randomdomainname.com' . Configure::read('Security.salt')));
		$this->assertFalse(TestCakeSession::valid());
	}

/**
 * testReadAndWriteWithDatabaseStorage method
 *
 * @access public
 * @return void
 */
	function testReadAndWriteWithCakeStorage() {
		ini_set('session.save_handler', 'files');
		Configure::write('Session.save', 'cake');
		$this->setUp();

		TestCakeSession::write('SessionTestCase', 0);
		$this->assertEqual(TestCakeSession::read('SessionTestCase'), 0);

		TestCakeSession::write('SessionTestCase', '0');
		$this->assertEqual(TestCakeSession::read('SessionTestCase'), '0');
		$this->assertFalse(TestCakeSession::read('SessionTestCase') === 0);

		TestCakeSession::write('SessionTestCase', false);
		$this->assertFalse(TestCakeSession::read('SessionTestCase'));

		TestCakeSession::write('SessionTestCase', null);
		$this->assertEqual(TestCakeSession::read('SessionTestCase'), null);

		TestCakeSession::write('SessionTestCase', 'This is a Test');
		$this->assertEqual(TestCakeSession::read('SessionTestCase'), 'This is a Test');

		TestCakeSession::write('SessionTestCase', 'This is a Test');
		TestCakeSession::write('SessionTestCase', 'This was updated');
		$this->assertEqual(TestCakeSession::read('SessionTestCase'), 'This was updated');

		TestCakeSession::destroy();
		$this->assertFalse(TestCakeSession::read('SessionTestCase'));
	}

/**
 * testReadAndWriteWithDatabaseStorage method
 *
 * @access public
 * @return void
 */
	function testReadAndWriteWithCacheStorage() {
		ini_set('session.save_handler', 'files');
		Configure::write('Session.save', 'cache');
		$this->setUp();

		TestCakeSession::write('SessionTestCase', 0);
		$this->assertEqual(TestCakeSession::read('SessionTestCase'), 0);

		TestCakeSession::write('SessionTestCase', '0');
		$this->assertEqual(TestCakeSession::read('SessionTestCase'), '0');
		$this->assertFalse(TestCakeSession::read('SessionTestCase') === 0);

		TestCakeSession::write('SessionTestCase', false);
		$this->assertFalse(TestCakeSession::read('SessionTestCase'));

		TestCakeSession::write('SessionTestCase', null);
		$this->assertEqual(TestCakeSession::read('SessionTestCase'), null);

		TestCakeSession::write('SessionTestCase', 'This is a Test');
		$this->assertEqual(TestCakeSession::read('SessionTestCase'), 'This is a Test');

		TestCakeSession::write('SessionTestCase', 'This is a Test');
		TestCakeSession::write('SessionTestCase', 'This was updated');
		$this->assertEqual(TestCakeSession::read('SessionTestCase'), 'This was updated');

		TestCakeSession::destroy();
		$this->assertFalse(TestCakeSession::read('SessionTestCase'));
	}

/**
 * testReadAndWriteWithDatabaseStorage method
 *
 * @access public
 * @return void
 */
	function testReadAndWriteWithDatabaseStorage() {
		Configure::write('Session.table', 'sessions');
		Configure::write('Session.model', 'Session');
		Configure::write('Session.database', 'test_suite');
		Configure::write('Session.save', 'database');
		$this->startTest();

		TestCakeSession::write('SessionTestCase', 0);
		$this->assertEqual(TestCakeSession::read('SessionTestCase'), 0);

		TestCakeSession::write('SessionTestCase', '0');
		$this->assertEqual(TestCakeSession::read('SessionTestCase'), '0');
		$this->assertFalse(TestCakeSession::read('SessionTestCase') === 0);

		TestCakeSession::write('SessionTestCase', false);
		$this->assertFalse(TestCakeSession::read('SessionTestCase'));

		TestCakeSession::write('SessionTestCase', null);
		$this->assertEqual(TestCakeSession::read('SessionTestCase'), null);

		TestCakeSession::write('SessionTestCase', 'This is a Test');
		$this->assertEqual(TestCakeSession::read('SessionTestCase'), 'This is a Test');

        TestCakeSession::write('SessionTestCase', 'Some additional data');
        $this->assertEqual(TestCakeSession::read('SessionTestCase'), 'Some additional data');

		TestCakeSession::destroy();
		$this->assertFalse(TestCakeSession::read('SessionTestCase'));
		session_write_close();

		unset($_SESSION);
		ini_set('session.save_handler', 'files');
		Configure::write('Session.save', 'php');
		$this->startTest();
	}

}
