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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Network;

use Cake\Cache\Cache;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\Network\Session;
use Cake\Network\Session\CacheSession;
use Cake\Network\Session\DatabaseSession;
use Cake\TestSuite\TestCase;

/**
 * Class TestCacheSession
 *
 */
class TestCacheSession extends CacheSession {

	protected function _writeSession() {
		return true;
	}

}

/**
 * Class TestDatabaseSession
 *
 */
class TestDatabaseSession extends DatabaseSession {

	protected function _writeSession() {
		return true;
	}

}

/**
 * SessionTest class
 *
 */
class SessionTest extends TestCase {

	protected static $_gcDivisor;

/**
 * Fixtures used in the SessionTest
 *
 * @var array
 */
	public $fixtures = array('core.session', 'core.cake_session');

/**
 * setup before class.
 *
 * @return void
 */
	public static function setupBeforeClass() {
		// Make sure garbage colector will be called
		static::$_gcDivisor = ini_get('session.gc_divisor');
		ini_set('session.gc_divisor', '1');
	}

/**
 * teardown after class
 *
 * @return void
 */
	public static function teardownAfterClass() {
		// Revert to the default setting
		ini_set('session.gc_divisor', static::$_gcDivisor);
	}

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
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

		$config = array(
			'cookie' => 'test',
			'checkAgent' => false,
			'timeout' => 86400,
			'ini' => array(
				'session.referer_check' => 'example.com',
				'session.use_trans_sid' => false
			)
		);

		$session = Session::create($config);
		$this->assertEquals('', ini_get('session.use_trans_sid'), 'Ini value is incorrect');
		$this->assertEquals('example.com', ini_get('session.referer_check'), 'Ini value is incorrect');
		$this->assertEquals('test', ini_get('session.name'), 'Ini value is incorrect');
	}

/**
 * testCheck method
 *
 * @return void
 */
	public function testCheck() {
		$session = new Session();
		$session->write('SessionTestCase', 'value');
		$this->assertTrue($session->check('SessionTestCase'));
		$this->assertFalse($session->check('NotExistingSessionTestCase'));
		$this->assertFalse($session->check('Crazy.foo'));
		$session->write('Crazy.foo', ['bar' => 'baz']);
		$this->assertTrue($session->check('Crazy.foo'));
		$this->assertTrue($session->check('Crazy.foo.bar'));
	}

/**
 * testSimpleRead method
 *
 * @return void
 */
	public function testSimpleRead() {
		$session = new Session();
		$session->write('testing', '1,2,3');
		$result = $session->read('testing');
		$this->assertEquals('1,2,3', $result);

		$session->write('testing', ['1' => 'one', '2' => 'two', '3' => 'three']);
		$result = $session->read('testing.1');
		$this->assertEquals('one', $result);

		$result = $session->read('testing');
		$this->assertEquals(['1' => 'one', '2' => 'two', '3' => 'three'], $result);

		$result = $session->read();
		$this->assertTrue(isset($result['testing']));

		$session->write('This.is.a.deep.array.my.friend', 'value');
		$result = $session->read('This.is.a.deep.array');
		$this->assertEquals(['my' => ['friend' => 'value']], $result);
	}

/**
 * testReadEmpty
 *
 * @return void
 */
	public function testReadEmpty() {
		$session = new Session();
		$this->assertNull($session->read(''));
	}

/**
 * test writing a hash of values/
 *
 * @return void
 */
	public function testWriteArray() {
		$session = new Session();
		$session->write([
			'one' => 1,
			'two' => 2,
			'three' => ['something'],
			'null' => null
		]);
		$this->assertEquals(1, $session->read('one'));
		$this->assertEquals(['something'], $session->read('three'));
		$this->assertEquals(null, $session->read('null'));
	}

/**
 * Test overwriting a string value as if it were an array.
 *
 * @return void
 */
	public function testWriteOverwriteStringValue() {
		$session = new Session();
		$session->write('Some.string', 'value');
		$this->assertEquals('value', $session->read('Some.string'));

		$session->write('Some.string.array', ['values']);
		$this->assertEquals(['values'], $session->read('Some.string.array'));
	}

/**
 * testId method
 *
 * @return void
 */
	public function testId() {
		$session = new Session();
		$result = $session->id();
		$expected = session_id();
		$this->assertEquals($expected, $result);

		$session->id('MySessionId');
		$this->assertEquals('MySessionId', $session->id());
		$this->assertEquals('MySessionId', session_id());

		$session->id('');
		$this->assertEquals('', session_id());
	}

/**
 * testStarted method
 *
 * @return void
 */
	public function testStarted() {
		$session = new Session();
		$this->assertFalse($session->started());
		$this->assertTrue($session->start());
		$this->assertTrue($session->started());
	}

/**
 * testDel method
 *
 * @return void
 */
	public function testDelete() {
		$session = new Session();
		$session->write('Delete.me', 'Clearing out');
		$session->delete('Delete.me');
		$this->assertFalse($session->check('Delete.me'));
		$this->assertTrue($session->check('Delete'));

		$session->write('Clearing.sale', 'everything must go');
		$session->delete('Clearing');
		$this->assertFalse($session->check('Clearing.sale'));
		$this->assertFalse($session->check('Clearing'));
	}

/**
 * testDestroy method
 *
 * @return void
 */
	public function testDestroy() {
		$session = new Session();
		$session->start();
		$session->write('bulletProof', 'invincible');
		$session->id('foo');
		$session->destroy();

		$this->assertFalse($session->check('bulletProof'));
		$this->assertEmpty($session->id());
	}

/**
 * testCheckingSavedEmpty method
 *
 * @return void
 */
	public function testCheckingSavedEmpty() {
		$session = new Session();
		$session->write('SessionTestCase', 0);
		$this->assertTrue($session->check('SessionTestCase'));

		$session->write('SessionTestCase', '0');
		$this->assertTrue($session->check('SessionTestCase'));

		$session->write('SessionTestCase', false);
		$this->assertTrue($session->check('SessionTestCase'));

		$session->write('SessionTestCase', null);
		$this->assertFalse($session->check('SessionTestCase'));
	}

/**
 * testCheckKeyWithSpaces method
 *
 * @return void
 */
	public function testCheckKeyWithSpaces() {
		$session = new Session();
		$session->write('Session Test', "test");
		$this->assertTrue($session->check('Session Test'));
		$session->delete('Session Test');

		$session->write('Session Test.Test Case', "test");
		$this->assertTrue($session->check('Session Test.Test Case'));
	}

/**
 * testCheckEmpty
 *
 * @return void
 */
	public function testCheckEmpty() {
		$session = new Session();
		$this->assertFalse($session->check());
	}

/**
 * test key exploitation
 *
 * @return void
 */
	public function testKeyExploit() {
		$session = new Session();
		$key = "a'] = 1; phpinfo(); \$_SESSION['a";
		$session->write($key, 'haxored');

		$result = $session->read($key);
		$this->assertNull($result);
	}

/**
 * testReadingSavedEmpty method
 *
 * @return void
 */
	public function testReadingSavedEmpty() {
		$session = new Session();
		$session->write('SessionTestCase', 0);
		$this->assertEquals(0, $session->read('SessionTestCase'));

		$session->write('SessionTestCase', '0');
		$this->assertEquals('0', $session->read('SessionTestCase'));
		$this->assertFalse($session->read('SessionTestCase') === 0);

		$session->write('SessionTestCase', false);
		$this->assertFalse($session->read('SessionTestCase'));

		$session->write('SessionTestCase', null);
		$this->assertEquals(null, $session->read('SessionTestCase'));
	}

/**
 * test using a handler from app/Model/Datasource/Session.
 *
 * @return void
 */
	public function testUsingAppLibsHandler() {
		\Cake\Core\Configure::write('App.namespace', 'TestApp');
		$config = [
			'defaults' => 'cake',
			'handler' => array(
				'engine' => 'TestAppLibSession'
			)
		];

		$session = Session::create($config);
		$this->assertInstanceOf('TestApp\Network\Session\TestAppLibSession', $session->engine());
		$this->assertEquals('user', ini_get('session.save_handler'));
	}

/**
 * test using a handler from a plugin.
 *
 * @return void
 */
	public function testUsingPluginHandler() {
		\Cake\Core\Configure::write('App.namespace', 'TestApp');
		\Cake\Core\Plugin::load('TestPlugin');

		$config = [
			'defaults' => 'cake',
			'handler' => array(
				'engine' => 'TestPlugin.TestPluginSession'
			)
		];

		$session = Session::create($config);
		$this->assertInstanceOf('TestPlugin\Network\Session\TestPluginSession', $session->engine());
		$this->assertEquals('user', ini_get('session.save_handler'));
	}

/**
 * testSessionTimeout method
 *
 * @return void
 */
	public function testSessionTimeout() {
		Configure::write('debug', true);
		Configure::write('Session.defaults', 'cake');
		Configure::write('Session.autoRegenerate', false);

		$timeoutSeconds = Configure::read('Session.timeout') * 60;

		TestCakeSession::destroy();
		TestCakeSession::write('Test', 'some value');

		$this->assertWithinMargin(time() + $timeoutSeconds, Session::$sessionTime, 1);
		$this->assertEquals(10, $_SESSION['Config']['countdown']);
		$this->assertWithinMargin(Session::$sessionTime, $_SESSION['Config']['time'], 1);
		$this->assertWithinMargin(time(), Session::$time, 1);
		$this->assertWithinMargin(time() + $timeoutSeconds, $_SESSION['Config']['time'], 1);

		Configure::write('Session.harden', true);
		TestCakeSession::destroy();

		TestCakeSession::write('Test', 'some value');
		$this->assertWithinMargin(time() + $timeoutSeconds, Session::$sessionTime, 1);
		$this->assertEquals(10, $_SESSION['Config']['countdown']);
		$this->assertWithinMargin(Session::$sessionTime, $_SESSION['Config']['time'], 1);
		$this->assertWithinMargin(time(), Session::$time, 1);
		$this->assertWithinMargin(Session::$time + $timeoutSeconds, $_SESSION['Config']['time'], 1);
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
	}

}
