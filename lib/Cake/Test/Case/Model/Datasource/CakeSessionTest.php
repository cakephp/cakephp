<?php
/**
 * SessionTest file
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model.Datasource
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('CakeSession', 'Model/Datasource');
App::uses('DatabaseSession', 'Model/Datasource/Session');
App::uses('CacheSession', 'Model/Datasource/Session');

/**
 * Class TestCakeSession
 *
 * @package       Cake.Test.Case.Model.Datasource
 */
class TestCakeSession extends CakeSession {

	public static function setUserAgent($value) {
		self::$_userAgent = $value;
	}

	public static function setHost($host) {
		self::_setHost($host);
	}

}

/**
 * Class TestCacheSession
 *
 * @package       Cake.Test.Case.Model.Datasource
 */
class TestCacheSession extends CacheSession {

	protected function _writeSession() {
		return true;
	}

}

/**
 * Class TestDatabaseSession
 *
 * @package       Cake.Test.Case.Model.Datasource
 */
class TestDatabaseSession extends DatabaseSession {

	protected function _writeSession() {
		return true;
	}

}

/**
 * CakeSessionTest class
 *
 * @package       Cake.Test.Case.Model.Datasource
 */
class CakeSessionTest extends CakeTestCase {

	protected static $_gcDivisor;

/**
 * Fixtures used in the SessionTest
 *
 * @var array
 */
	public $fixtures = array('core.session');

/**
 * setup before class.
 *
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
 * @return void
 */
	public static function teardownAfterClass() {
		// Revert to the default setting
		ini_set('session.gc_divisor', self::$_gcDivisor);
	}

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		Configure::write('Session', array(
			'defaults' => 'php',
			'cookie' => 'cakephp',
			'timeout' => 120,
			'cookieTimeout' => 120,
			'ini' => array(),
		));
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		if (TestCakeSession::started()) {
			session_write_close();
		}
		unset($_SESSION);
		parent::tearDown();
	}

/**
 * test setting ini properties with Session configuration.
 *
 * @return void
 */
	public function testSessionConfigIniSetting() {
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
 * @return void
 */
	public function testSessionPath() {
		TestCakeSession::init('/index.php');
		$this->assertEquals('/', TestCakeSession::$path);

		TestCakeSession::init('/sub_dir/index.php');
		$this->assertEquals('/sub_dir/', TestCakeSession::$path);
	}

/**
 * testCakeSessionPathEmpty
 *
 * @return void
 */
	public function testCakeSessionPathEmpty() {
		TestCakeSession::init('');
		$this->assertEquals('/', TestCakeSession::$path, 'Session path is empty, with "" as $base needs to be /');
	}

/**
 * testCakeSessionPathContainsParams
 *
 * @return void
 */
	public function testCakeSessionPathContainsQuestion() {
		TestCakeSession::init('/index.php?');
		$this->assertEquals('/', TestCakeSession::$path);
	}

/**
 * testSetHost
 *
 * @return void
 */
	public function testSetHost() {
		TestCakeSession::init();
		TestCakeSession::setHost('cakephp.org');
		$this->assertEquals('cakephp.org', TestCakeSession::$host);
	}

/**
 * testSetHostWithPort
 *
 * @return void
 */
	public function testSetHostWithPort() {
		TestCakeSession::init();
		TestCakeSession::setHost('cakephp.org:443');
		$this->assertEquals('cakephp.org', TestCakeSession::$host);
	}

/**
 * test valid with bogus user agent.
 *
 * @return void
 */
	public function testValidBogusUserAgent() {
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
	public function testValidTimeExpiry() {
		Configure::write('Session.checkAgent', true);
		TestCakeSession::start();
		$this->assertTrue(TestCakeSession::valid(), 'Newly started session should be valid');

		TestCakeSession::$time = strtotime('next year');
		$this->assertFalse(TestCakeSession::valid(), 'time should cause failure.');
	}

/**
 * testCheck method
 *
 * @return void
 */
	public function testCheck() {
		TestCakeSession::write('SessionTestCase', 'value');
		$this->assertTrue(TestCakeSession::check('SessionTestCase'));

		$this->assertFalse(TestCakeSession::check('NotExistingSessionTestCase'));
	}

/**
 * testSimpleRead method
 *
 * @return void
 */
	public function testSimpleRead() {
		TestCakeSession::write('testing', '1,2,3');
		$result = TestCakeSession::read('testing');
		$this->assertEquals('1,2,3', $result);

		TestCakeSession::write('testing', array('1' => 'one', '2' => 'two', '3' => 'three'));
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
		$this->assertEquals('value', $result);
	}

/**
 * testReadyEmpty
 *
 * @return void
 */
	public function testReadyEmpty() {
		$this->assertNull(TestCakeSession::read(''));
	}

/**
 * test writing a hash of values/
 *
 * @return void
 */
	public function testWriteArray() {
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
 */
	public function testWriteEmptyKey() {
		$this->assertFalse(TestCakeSession::write('', 'graham'));
		$this->assertFalse(TestCakeSession::write('', ''));
		$this->assertFalse(TestCakeSession::write(''));
	}

/**
 * Test overwriting a string value as if it were an array.
 *
 * @return void
 */
	public function testWriteOverwriteStringValue() {
		TestCakeSession::write('Some.string', 'value');
		$this->assertEquals('value', TestCakeSession::read('Some.string'));

		TestCakeSession::write('Some.string.array', array('values'));
		$this->assertEquals(
			array('values'),
			TestCakeSession::read('Some.string.array')
		);
	}

/**
 * Test consuming session data.
 *
 * @return void
 */
	public function testConsume() {
		TestCakeSession::write('Some.string', 'value');
		TestCakeSession::write('Some.array', array('key1' => 'value1', 'key2' => 'value2'));
		$this->assertEquals('value', TestCakeSession::read('Some.string'));
		$value = TestCakeSession::consume('Some.string');
		$this->assertEquals('value', $value);
		$this->assertFalse(TestCakeSession::check('Some.string'));
		$value = TestCakeSession::consume('');
		$this->assertNull($value);
		$value = TestCakeSession::consume(null);
		$this->assertNull($value);
		$value = TestCakeSession::consume('Some.array');
		$expected = array('key1' => 'value1', 'key2' => 'value2');
		$this->assertEquals($expected, $value);
		$this->assertFalse(TestCakeSession::check('Some.array'));
	}

/**
 * testId method
 *
 * @return void
 */
	public function testId() {
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
 * @return void
 */
	public function testStarted() {
		unset($_SESSION);
		$_SESSION = null;

		$this->assertFalse(TestCakeSession::started());
		$this->assertTrue(TestCakeSession::start());
		$this->assertTrue(TestCakeSession::started());
	}

/**
 * testDel method
 *
 * @return void
 */
	public function testDelete() {
		$this->assertTrue(TestCakeSession::write('Delete.me', 'Clearing out'));
		$this->assertTrue(TestCakeSession::delete('Delete.me'));
		$this->assertFalse(TestCakeSession::check('Delete.me'));
		$this->assertTrue(TestCakeSession::check('Delete'));

		$this->assertTrue(TestCakeSession::write('Clearing.sale', 'everything must go'));
		$this->assertFalse(TestCakeSession::delete(''));
		$this->assertTrue(TestCakeSession::check('Clearing.sale'));
		$this->assertFalse(TestCakeSession::delete(null));
		$this->assertTrue(TestCakeSession::check('Clearing.sale'));

		$this->assertTrue(TestCakeSession::delete('Clearing'));
		$this->assertFalse(TestCakeSession::check('Clearing.sale'));
		$this->assertFalse(TestCakeSession::check('Clearing'));
	}

/**
 * testClear method
 *
 * @return void
 */
	public function testClear() {
		$this->assertTrue(TestCakeSession::write('Delete.me', 'Clearing out'));
		TestCakeSession::clear(false);
		$this->assertFalse(TestCakeSession::check('Delete.me'));
		$this->assertFalse(TestCakeSession::check('Delete'));

		TestCakeSession::write('Some.string', 'value');
		TestCakeSession::clear(false);
		$this->assertNull(TestCakeSession::read('Some'));

		TestCakeSession::write('Some.string.array', array('values'));
		TestCakeSession::clear(false);
		$this->assertFalse(TestCakeSession::read());
	}

/**
 * testDestroy method
 *
 * @return void
 */
	public function testDestroy() {
		TestCakeSession::write('bulletProof', 'invincible');
		$id = TestCakeSession::id();
		TestCakeSession::destroy();

		$this->assertFalse(TestCakeSession::check('bulletProof'));
		$this->assertNotEquals(TestCakeSession::id(), $id);
	}

/**
 * testCheckingSavedEmpty method
 *
 * @return void
 */
	public function testCheckingSavedEmpty() {
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
 * @return void
 */
	public function testCheckKeyWithSpaces() {
		$this->assertTrue(TestCakeSession::write('Session Test', "test"));
		$this->assertTrue(TestCakeSession::check('Session Test'));
		TestCakeSession::delete('Session Test');

		$this->assertTrue(TestCakeSession::write('Session Test.Test Case', "test"));
		$this->assertTrue(TestCakeSession::check('Session Test.Test Case'));
	}

/**
 * testCheckEmpty
 *
 * @return void
 */
	public function testCheckEmpty() {
		$this->assertFalse(TestCakeSession::check(''));
		$this->assertFalse(TestCakeSession::check(null));
	}

/**
 * test key exploitation
 *
 * @return void
 */
	public function testKeyExploit() {
		$key = "a'] = 1; phpinfo(); \$_SESSION['a";
		$result = TestCakeSession::write($key, 'haxored');
		$this->assertFalse($result);

		$result = TestCakeSession::read($key);
		$this->assertNull($result);
	}

/**
 * testReadingSavedEmpty method
 *
 * @return void
 */
	public function testReadingSavedEmpty() {
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
 * @return void
 */
	public function testCheckUserAgentFalse() {
		Configure::write('Session.checkAgent', false);
		TestCakeSession::setUserAgent(md5('http://randomdomainname.com' . Configure::read('Security.salt')));
		$this->assertTrue(TestCakeSession::valid());
	}

/**
 * testCheckUserAgentTrue method
 *
 * @return void
 */
	public function testCheckUserAgentTrue() {
		Configure::write('Session.checkAgent', true);
		TestCakeSession::$error = false;
		$agent = md5('http://randomdomainname.com' . Configure::read('Security.salt'));

		TestCakeSession::write('Config.userAgent', md5('Hacking you!'));
		TestCakeSession::setUserAgent($agent);
		$this->assertFalse(TestCakeSession::valid());
	}

/**
 * testReadAndWriteWithCakeStorage method
 *
 * @return void
 */
	public function testReadAndWriteWithCakeStorage() {
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
 * test using a handler from app/Model/Datasource/Session.
 *
 * @return void
 */
	public function testUsingAppLibsHandler() {
		App::build(array(
			'Model/Datasource/Session' => array(
				CAKE . 'Test' . DS . 'test_app' . DS . 'Model' . DS . 'Datasource' . DS . 'Session' . DS
			),
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		), App::RESET);
		Configure::write('Session', array(
			'defaults' => 'cake',
			'handler' => array(
				'engine' => 'TestAppLibSession'
			)
		));

		TestCakeSession::start();
		$this->assertTrue(TestCakeSession::started());

		TestCakeSession::destroy();
		$this->assertFalse(TestCakeSession::started());

		App::build();
	}

/**
 * test using a handler from a plugin.
 *
 * @return void
 */
	public function testUsingPluginHandler() {
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		), App::RESET);
		CakePlugin::load('TestPlugin');

		Configure::write('Session', array(
			'defaults' => 'cake',
			'handler' => array(
				'engine' => 'TestPlugin.TestPluginSession'
			)
		));

		TestCakeSession::start();
		$this->assertTrue(TestCakeSession::started());

		TestCakeSession::destroy();
		$this->assertFalse(TestCakeSession::started());

		App::build();
	}

/**
 * testReadAndWriteWithCacheStorage method
 *
 * @return void
 */
	public function testReadAndWriteWithCacheStorage() {
		Configure::write('Session.defaults', 'cache');
		Configure::write('Session.handler.engine', 'TestCacheSession');

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
	public function testReadAndWriteWithCustomCacheConfig() {
		Configure::write('Session.defaults', 'cache');
		Configure::write('Session.handler.engine', 'TestCacheSession');
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
 * @return void
 */
	public function testReadAndWriteWithDatabaseStorage() {
		Configure::write('Session.defaults', 'database');
		Configure::write('Session.handler.engine', 'TestDatabaseSession');
		Configure::write('Session.handler.table', 'sessions');
		Configure::write('Session.handler.model', 'Session');
		Configure::write('Session.handler.database', 'test');

		TestCakeSession::init();
		$this->assertNull(TestCakeSession::id());

		TestCakeSession::start();
		$expected = session_id();
		$this->assertEquals($expected, TestCakeSession::id());

		TestCakeSession::renew();
		$this->assertFalse($expected === TestCakeSession::id());

		$expected = session_id();
		$this->assertEquals($expected, TestCakeSession::id());

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
 * @return void
 */
	public function testSessionTimeout() {
		Configure::write('debug', 2);
		Configure::write('Session.defaults', 'cake');
		Configure::write('Session.autoRegenerate', false);

		$timeoutSeconds = Configure::read('Session.timeout') * 60;

		TestCakeSession::destroy();
		TestCakeSession::write('Test', 'some value');

		$this->assertWithinMargin(time() + $timeoutSeconds, CakeSession::$sessionTime, 1);
		$this->assertEquals(10, $_SESSION['Config']['countdown']);
		$this->assertWithinMargin(CakeSession::$sessionTime, $_SESSION['Config']['time'], 1);
		$this->assertWithinMargin(time(), CakeSession::$time, 1);
		$this->assertWithinMargin(time() + $timeoutSeconds, $_SESSION['Config']['time'], 1);

		Configure::write('Session.harden', true);
		TestCakeSession::destroy();

		TestCakeSession::write('Test', 'some value');
		$this->assertWithinMargin(time() + $timeoutSeconds, CakeSession::$sessionTime, 1);
		$this->assertEquals(10, $_SESSION['Config']['countdown']);
		$this->assertWithinMargin(CakeSession::$sessionTime, $_SESSION['Config']['time'], 1);
		$this->assertWithinMargin(time(), CakeSession::$time, 1);
		$this->assertWithinMargin(CakeSession::$time + $timeoutSeconds, $_SESSION['Config']['time'], 1);
	}

/**
 * Test that cookieTimeout matches timeout when unspecified.
 *
 * @return void
 */
	public function testCookieTimeoutFallback() {
		$_SESSION = null;
		Configure::write('Session', array(
			'defaults' => 'cake',
			'timeout' => 400,
		));
		TestCakeSession::start();
		$this->assertEquals(400, Configure::read('Session.cookieTimeout'));
		$this->assertEquals(400, Configure::read('Session.timeout'));
		$this->assertEquals(400 * 60, ini_get('session.cookie_lifetime'));
		$this->assertEquals(400 * 60, ini_get('session.gc_maxlifetime'));

		$_SESSION = null;
		Configure::write('Session', array(
			'defaults' => 'cake',
			'timeout' => 400,
			'cookieTimeout' => 600
		));
		TestCakeSession::start();
		$this->assertEquals(600, Configure::read('Session.cookieTimeout'));
		$this->assertEquals(400, Configure::read('Session.timeout'));
	}

/**
 * Proves that invalid sessions will be destroyed and re-created
 * if invalid
 *
 * @return void
 */
	public function testInvalidSessionRenew() {
		TestCakeSession::start();
		$this->assertNotEmpty($_SESSION['Config']);
		$data = $_SESSION;

		session_write_close();
		$_SESSION = null;

		TestCakeSession::start();
		$this->assertEquals($data, $_SESSION);
		TestCakeSession::write('Foo', 'Bar');

		session_write_close();
		$_SESSION = null;

		TestCakeSession::userAgent('bogus!');
		TestCakeSession::start();
		$this->assertNotEquals($data, $_SESSION);
		$this->assertEquals('bogus!', $_SESSION['Config']['userAgent']);
	}

}
