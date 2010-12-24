<?php
/**
 * SessionTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
if (!class_exists('CakeSession')) {
	App::import('Core', 'CakeSession');
}

class TestCakeSession extends CakeSession {
	public static function setUserAgent($value) {
		self::$_userAgent = $value;
	}
	
	public static function setHost($host) {
		self::_setHost($host);
	}
}

/**
 * CakeSessionTest class
 *
 * @package       cake.tests.cases.libs
 */
class CakeSessionTest extends CakeTestCase {

	protected static $_gcDivisor;

/**
 * Fixtures used in the SessionTest
 *
 * @var array
 * @access public
 */
	public $fixtures = array('core.session');

/**
 * setup before class.
 *
 * @access public
 * @return void
 */
	public static function setupBeforeClass() {
		// Make sure garbage colector will be called
		self::$_gcDivisor = ini_get('session.gc_divisor');
		ini_set('session.gc_divisor', '1');
	}

/**
 * teardown after class
 *
 * @access public
 * @return void
 */
	public static function teardownAfterClass() {
		// Revert to the default setting
		ini_set('session.gc_divisor', self::$_gcDivisor);
	}

/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setup() {
		parent::setup();
		Configure::write('Session', array(
			'defaults' => 'php',
			'cookie' => 'cakephp',
			'timeout' => 120,
			'cookieTimeout' => 120,
			'ini' => array(),
		));
		TestCakeSession::init();
	}

/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function teardown() {
		if (TestCakeSession::started()) {
			TestCakeSession::clear();
		}
		unset($_SESSION);
		parent::teardown();
	}

/**
 * test setting ini properties with Session configuration.
 *
 * @return void
 */
	function testSessionConfigIniSetting() {
		$_SESSION = null;

		Configure::write('Session', array(
			'cookie' => 'test',
			'checkAgent' => false,
			'timeout' => 86400,
			'ini' => array(
				'session.referer_check' => 'example.com',
				'session.use_trans_sid' => false
			)
		));
		TestCakeSession::start();
		$this->assertEquals('', ini_get('session.use_trans_sid'), 'Ini value is incorrect');
		$this->assertEquals('example.com', ini_get('session.referer_check'), 'Ini value is incorrect');
		$this->assertEquals('test', ini_get('session.name'), 'Ini value is incorrect');
	}

/**
 * testSessionPath
 *
 * @access public
 * @return void
 */
	function testSessionPath() {
		TestCakeSession::init('/index.php');
		$this->assertEquals(TestCakeSession::$path, '/');

		TestCakeSession::init('/sub_dir/index.php');
		$this->assertEquals(TestCakeSession::$path, '/sub_dir/');
	}

/**
 * testCakeSessionPathEmpty
 *
 * @access public
 * @return void
 */	
	function testCakeSessionPathEmpty() {
		TestCakeSession::init('');
		$this->assertEquals(TestCakeSession::$path, '/', 'Session path is empty, with "" as $base needs to be /');
	}

/**
 * testCakeSessionPathContainsParams
 *
 * @access public
 * @return void
 */
	function testCakeSessionPathContainsQuestion() {
		TestCakeSession::init('/index.php?');
		$this->assertEquals(TestCakeSession::$path, '/');
	}

/**
 * testSetHost
 *
 * @access public
 * @return void
 */
	function testSetHost() {
		TestCakeSession::init();
		TestCakeSession::setHost('cakephp.org');
		$this->assertEquals(TestCakeSession::$host, 'cakephp.org');
	}

/**
 * testSetHostWithPort
 *
 * @access public
 * @return void
 */
	function testSetHostWithPort() {
		TestCakeSession::init();
		TestCakeSession::setHost('cakephp.org:443');
		$this->assertEquals(TestCakeSession::$host, 'cakephp.org');
	}

/**
 * test valid with bogus user agent.
 *
 * @return void
 */
	function testValidBogusUserAgent() {
		Configure::write('Session.checkAgent', true);
		TestCakeSession::start();
		$this->assertTrue(TestCakeSession::valid(), 'Newly started session should be valid');

		TestCakeSession::userAgent('bogus!');
		$this->assertFalse(TestCakeSession::valid(), 'user agent mismatch should fail.');
	}

/**
 * test valid with bogus user agent.
 *
 * @return void
 */
	function testValidTimeExpiry() {
		Configure::write('Session.checkAgent', true);
		TestCakeSession::start();
		$this->assertTrue(TestCakeSession::valid(), 'Newly started session should be valid');

		TestCakeSession::$time = strtotime('next year');
		$this->assertFalse(TestCakeSession::valid(), 'time should cause failure.');
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
		$this->assertEquals('1,2,3', $result);

		TestCakeSession::write('testing', array('1' => 'one', '2' => 'two','3' => 'three'));
		$result = TestCakeSession::read('testing.1');
		$this->assertEquals('one', $result);

		$result = TestCakeSession::read('testing');
		$this->assertEquals(array('1' => 'one', '2' => 'two', '3' => 'three'), $result);

		$result = TestCakeSession::read();
		$this->assertTrue(isset($result['testing']));
		$this->assertTrue(isset($result['Config']));
		$this->assertTrue(isset($result['Config']['userAgent']));

		TestCakeSession::write('This.is.a.deep.array.my.friend', 'value');
		$result = TestCakeSession::read('This.is.a.deep.array.my.friend');
		$this->assertEquals($result, 'value');
	}

/**
 * testReadyEmpty
 *
 * @return void
 * @access public
 */
	function testReadyEmpty() {
		$this->assertFalse(TestCakeSession::read(''));
	}

/**
 * test writing a hash of values/
 *
 * @return void
 * @access public
 */
	function testWriteArray() {
		$result = TestCakeSession::write(array(
			'one' => 1,
			'two' => 2,
			'three' => array('something'),
			'null' => null
		));
		$this->assertTrue($result);
		$this->assertEquals(1, TestCakeSession::read('one'));
		$this->assertEquals(array('something'), TestCakeSession::read('three'));
		$this->assertEquals(null, TestCakeSession::read('null'));
	}

/**
 * testWriteEmptyKey
 *
 * @return void
 * @access public
 */
	function testWriteEmptyKey() {
		$this->assertFalse(TestCakeSession::write('', 'graham'));
		$this->assertFalse(TestCakeSession::write('', ''));
		$this->assertFalse(TestCakeSession::write(''));
	}

/**
 * testId method
 *
 * @access public
 * @return void
 */
	function testId() {
		TestCakeSession::destroy();
		
		$result = TestCakeSession::id();
		$expected = session_id();
		$this->assertEquals($expected, $result);

		TestCakeSession::id('MySessionId');
		$result = TestCakeSession::id();
		$this->assertEquals('MySessionId', $result);
	}

/**
 * testStarted method
 *
 * @access public
 * @return void
 */
	function testStarted() {
		unset($_SESSION);
		$_SESSION = null;

		$this->assertFalse(TestCakeSession::started());
		$this->assertTrue(TestCakeSession::start());
		$this->assertTrue(TestCakeSession::started());
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
		$this->assertEquals("Does.not.exist doesn't exist", $result);

		TestCakeSession::delete('Failing.delete');
		$result = TestCakeSession::error();
		$this->assertEquals("Failing.delete doesn't exist", $result);
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
		$this->assertEquals('test', TestCakeSession::check('Session Test'));
		TestCakeSession::delete('Session Test');

		$this->assertTrue(TestCakeSession::write('Session Test.Test Case', "test"));
		$this->assertTrue(TestCakeSession::check('Session Test.Test Case'));
	}

/**
 * testCheckEmpty
 *
 * @access public
 * @return void
 */
	function testCheckEmpty() {
		$this->assertFalse(TestCakeSession::check());
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
		$this->assertEquals('haxored', $result);
	}

/**
 * testReadingSavedEmpty method
 *
 * @access public
 * @return void
 */
	function testReadingSavedEmpty() {
		TestCakeSession::write('SessionTestCase', 0);
		$this->assertEquals(0, TestCakeSession::read('SessionTestCase'));

		TestCakeSession::write('SessionTestCase', '0');
		$this->assertEquals('0', TestCakeSession::read('SessionTestCase'));
		$this->assertFalse(TestCakeSession::read('SessionTestCase') === 0);

		TestCakeSession::write('SessionTestCase', false);
		$this->assertFalse(TestCakeSession::read('SessionTestCase'));

		TestCakeSession::write('SessionTestCase', null);
		$this->assertEquals(null, TestCakeSession::read('SessionTestCase'));
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
		TestCakeSession::$error = false;
		$agent = md5('http://randomdomainname.com' . Configure::read('Security.salt'));

		TestCakeSession::write('Config.userAgent', md5('Hacking you!'));
		TestCakeSession::setUserAgent($agent);
		$this->assertFalse(TestCakeSession::valid());
	}

/**
 * testReadAndWriteWithDatabaseStorage method
 *
 * @access public
 * @return void
 */
	function testReadAndWriteWithCakeStorage() {
		Configure::write('Session.defaults', 'cake');

		TestCakeSession::init();
		TestCakeSession::start();

		TestCakeSession::write('SessionTestCase', 0);
		$this->assertEquals(0, TestCakeSession::read('SessionTestCase'));

		TestCakeSession::write('SessionTestCase', '0');
		$this->assertEquals('0', TestCakeSession::read('SessionTestCase'));
		$this->assertFalse(TestCakeSession::read('SessionTestCase') === 0);

		TestCakeSession::write('SessionTestCase', false);
		$this->assertFalse(TestCakeSession::read('SessionTestCase'));

		TestCakeSession::write('SessionTestCase', null);
		$this->assertEquals(null, TestCakeSession::read('SessionTestCase'));

		TestCakeSession::write('SessionTestCase', 'This is a Test');
		$this->assertEquals('This is a Test', TestCakeSession::read('SessionTestCase'));

		TestCakeSession::write('SessionTestCase', 'This is a Test');
		TestCakeSession::write('SessionTestCase', 'This was updated');
		$this->assertEquals('This was updated', TestCakeSession::read('SessionTestCase'));

		TestCakeSession::destroy();
		$this->assertNull(TestCakeSession::read('SessionTestCase'));
	}

/**
 * test using a handler from app/libs.
 *
 * @return void
 */
	function testUsingAppLibsHandler() {
		App::build(array(
			'libs' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'libs' . DS),
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS)
		), true);
		Configure::write('Session', array(
			'defaults' => 'cake',
			'handler' => array(
				'engine' => 'TestAppLibSession'
			)
		));
		TestCakeSession::destroy();
		$this->assertTrue(TestCakeSession::started());

		App::build();
	}

/**
 * test using a handler from a plugin.
 *
 * @return void
 */
	function testUsingPluginHandler() {
		App::build(array(
			'libs' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'libs' . DS),
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS)
		), true);
		
		Configure::write('Session', array(
			'defaults' => 'cake',
			'handler' => array(
				'engine' => 'TestPlugin.TestPluginSession'
			)
		));
		
		TestCakeSession::destroy();
		$this->assertTrue(TestCakeSession::started());

		App::build();
	}

/**
 * testReadAndWriteWithDatabaseStorage method
 *
 * @access public
 * @return void
 */
	function testReadAndWriteWithCacheStorage() {
		Configure::write('Session.defaults', 'cache');

		TestCakeSession::init();
		TestCakeSession::destroy();

		TestCakeSession::write('SessionTestCase', 0);
		$this->assertEquals(0, TestCakeSession::read('SessionTestCase'));

		TestCakeSession::write('SessionTestCase', '0');
		$this->assertEquals('0', TestCakeSession::read('SessionTestCase'));
		$this->assertFalse(TestCakeSession::read('SessionTestCase') === 0);

		TestCakeSession::write('SessionTestCase', false);
		$this->assertFalse(TestCakeSession::read('SessionTestCase'));

		TestCakeSession::write('SessionTestCase', null);
		$this->assertEquals(null, TestCakeSession::read('SessionTestCase'));

		TestCakeSession::write('SessionTestCase', 'This is a Test');
		$this->assertEquals('This is a Test', TestCakeSession::read('SessionTestCase'));

		TestCakeSession::write('SessionTestCase', 'This is a Test');
		TestCakeSession::write('SessionTestCase', 'This was updated');
		$this->assertEquals('This was updated', TestCakeSession::read('SessionTestCase'));

		TestCakeSession::destroy();
		$this->assertNull(TestCakeSession::read('SessionTestCase'));
	}

/**
 * test that changing the config name of the cache config works.
 *
 * @return void
 */
	function testReadAndWriteWithCustomCacheConfig() {
		Configure::write('Session.defaults', 'cache');
		Configure::write('Session.handler.config', 'session_test');
		
		Cache::config('session_test', array(
			'engine' => 'File',
			'prefix' => 'session_test_',
		));

		TestCakeSession::init();
		TestCakeSession::start();

		TestCakeSession::write('SessionTestCase', 'Some value');
		$this->assertEquals('Some value', TestCakeSession::read('SessionTestCase'));
		$id = TestCakeSession::id();

		Cache::delete($id, 'session_test');
	}

/**
 * testReadAndWriteWithDatabaseStorage method
 *
 * @access public
 * @return void
 */
	function testReadAndWriteWithDatabaseStorage() {
		Configure::write('Session.defaults', 'database');
		Configure::write('Session.handler.table', 'sessions');
		Configure::write('Session.handler.model', 'Session');
		Configure::write('Session.handler.database', 'test');

		TestCakeSession::init();
		TestCakeSession::start();

		TestCakeSession::write('SessionTestCase', 0);
		$this->assertEquals(0, TestCakeSession::read('SessionTestCase'));

		TestCakeSession::write('SessionTestCase', '0');
		$this->assertEquals('0', TestCakeSession::read('SessionTestCase'));
		$this->assertFalse(TestCakeSession::read('SessionTestCase') === 0);

		TestCakeSession::write('SessionTestCase', false);
		$this->assertFalse(TestCakeSession::read('SessionTestCase'));

		TestCakeSession::write('SessionTestCase', null);
		$this->assertEquals(null, TestCakeSession::read('SessionTestCase'));

		TestCakeSession::write('SessionTestCase', 'This is a Test');
		$this->assertEquals('This is a Test', TestCakeSession::read('SessionTestCase'));

		TestCakeSession::write('SessionTestCase', 'Some additional data');
		$this->assertEquals('Some additional data', TestCakeSession::read('SessionTestCase'));

		TestCakeSession::destroy();
		$this->assertNull(TestCakeSession::read('SessionTestCase'));
		
		Configure::write('Session', array(
			'defaults' => 'php'
		));
		TestCakeSession::init();
	}

/**
 * testSessionTimeout method
 *
 * @access public
 * @return void
 */
	function testSessionTimeout() {
		Configure::write('debug', 2);
		Configure::write('Session.autoRegenerate', false);

		$timeoutSeconds = Configure::read('Session.timeout') * 60;

		TestCakeSession::destroy();
		TestCakeSession::write('Test', 'some value');

		$this->assertEquals(time() + $timeoutSeconds, CakeSession::$sessionTime);
		$this->assertEquals(10, $_SESSION['Config']['countdown']);
		$this->assertEquals(CakeSession::$sessionTime, $_SESSION['Config']['time']);
		$this->assertEquals(time(), CakeSession::$time);
		$this->assertEquals(time() + $timeoutSeconds, $_SESSION['Config']['time']);

		Configure::write('Session.harden', true);
		TestCakeSession::destroy();

		TestCakeSession::write('Test', 'some value');
		$this->assertEquals(time() + $timeoutSeconds, CakeSession::$sessionTime);
		$this->assertEquals(10, $_SESSION['Config']['countdown']);
		$this->assertEquals(CakeSession::$sessionTime, $_SESSION['Config']['time']);
		$this->assertEquals(time(), CakeSession::$time);
		$this->assertEquals(CakeSession::$time + $timeoutSeconds, $_SESSION['Config']['time']);
	}

}
