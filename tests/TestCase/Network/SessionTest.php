<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Network;

use Cake\Core\Configure;
use Cake\Network\Session;
use Cake\Network\Session\CacheSession;
use Cake\Network\Session\DatabaseSession;
use Cake\TestSuite\TestCase;

/**
 * TestCacheSession
 */
class TestCacheSession extends CacheSession
{

    protected function _writeSession()
    {
        return true;
    }
}

/**
 * TestDatabaseSession
 */
class TestDatabaseSession extends DatabaseSession
{

    protected function _writeSession()
    {
        return true;
    }
}

/**
 * SessionTest class
 */
class SessionTest extends TestCase
{

    protected static $_gcDivisor;

    /**
     * Fixtures used in the SessionTest
     *
     * @var array
     */
    public $fixtures = ['core.cake_sessions', 'core.sessions'];

    /**
     * setup before class.
     *
     * @return void
     */
    public static function setupBeforeClass()
    {
        // Make sure garbage collector will be called
        static::$_gcDivisor = ini_get('session.gc_divisor');
        ini_set('session.gc_divisor', '1');
    }

    /**
     * teardown after class
     *
     * @return void
     */
    public static function teardownAfterClass()
    {
        // Revert to the default setting
        ini_set('session.gc_divisor', static::$_gcDivisor);
    }

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($_SESSION);
        parent::tearDown();
    }

    /**
     * test setting ini properties with Session configuration.
     *
     * @return void
     */
    public function testSessionConfigIniSetting()
    {
        $_SESSION = null;

        $config = [
            'cookie' => 'test',
            'checkAgent' => false,
            'timeout' => 86400,
            'ini' => [
                'session.referer_check' => 'example.com',
                'session.use_trans_sid' => false
            ]
        ];

        $session = Session::create($config);
        $this->assertEquals('', ini_get('session.use_trans_sid'), 'Ini value is incorrect');
        $this->assertEquals('example.com', ini_get('session.referer_check'), 'Ini value is incorrect');
        $this->assertEquals('test', ini_get('session.name'), 'Ini value is incorrect');
    }

    /**
     * test session cookie path setting
     *
     * @return void
     */
    public function testCookiePath()
    {
        ini_set('session.cookie_path', '/foo');

        $session = new Session();
        $this->assertEquals('/', ini_get('session.cookie_path'));

        $session = new Session(['cookiePath' => '/base']);
        $this->assertEquals('/base', ini_get('session.cookie_path'));
    }

    /**
     * testCheck method
     *
     * @return void
     */
    public function testCheck()
    {
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
     * test read with simple values
     *
     * @return void
     */
    public function testReadSimple()
    {
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
        $this->assertArrayHasKey('testing', $result);

        $session->write('This.is.a.deep.array.my.friend', 'value');
        $result = $session->read('This.is.a.deep.array');
        $this->assertEquals(['my' => ['friend' => 'value']], $result);
    }

    /**
     * testReadEmpty
     *
     * @return void
     */
    public function testReadEmpty()
    {
        $session = new Session();
        $this->assertNull($session->read(''));
    }

    /**
     * Test writing simple keys
     *
     * @return void
     */
    public function testWriteSimple()
    {
        $session = new Session();
        $session->write('', 'empty');
        $this->assertEquals('empty', $session->read(''));

        $session->write('Simple', ['values']);
        $this->assertEquals(['values'], $session->read('Simple'));
    }

    /**
     * test writing a hash of values
     *
     * @return void
     */
    public function testWriteArray()
    {
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
    public function testWriteOverwriteStringValue()
    {
        $session = new Session();
        $session->write('Some.string', 'value');
        $this->assertEquals('value', $session->read('Some.string'));

        $session->write('Some.string.array', ['values']);
        $this->assertEquals(['values'], $session->read('Some.string.array'));
    }

    /**
     * Test consuming session data.
     *
     * @return void
     */
    public function testConsume()
    {
        $session = new Session();
        $session->write('Some.string', 'value');
        $session->write('Some.array', ['key1' => 'value1', 'key2' => 'value2']);

        $this->assertEquals('value', $session->read('Some.string'));

        $value = $session->consume('Some.string');
        $this->assertEquals('value', $value);
        $this->assertFalse($session->check('Some.string'));

        $value = $session->consume('');
        $this->assertNull($value);

        $value = $session->consume(null);
        $this->assertNull($value);

        $value = $session->consume('Some.array');
        $expected = ['key1' => 'value1', 'key2' => 'value2'];
        $this->assertEquals($expected, $value);
        $this->assertFalse($session->check('Some.array'));
    }

    /**
     * testId method
     *
     * @return void
     */
    public function testId()
    {
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
    public function testStarted()
    {
        $session = new Session();
        $this->assertFalse($session->started());
        $this->assertTrue($session->start());
        $this->assertTrue($session->started());
    }

    /**
     * testClear method
     *
     * @return void
     */
    public function testClear()
    {
        $session = new Session();
        $session->write('Delete.me', 'Clearing out');

        $session->clear();
        $this->assertFalse($session->check('Delete.me'));
        $this->assertFalse($session->check('Delete'));
    }

    /**
     * testDelete method
     *
     * @return void
     */
    public function testDelete()
    {
        $session = new Session();
        $session->write('Delete.me', 'Clearing out');
        $session->delete('Delete.me');
        $this->assertFalse($session->check('Delete.me'));
        $this->assertTrue($session->check('Delete'));

        $session->write('Clearing.sale', 'everything must go');
        $session->delete('');
        $this->assertTrue($session->check('Clearing.sale'));
        $session->delete(null);
        $this->assertTrue($session->check('Clearing.sale'));

        $session->delete('Clearing');
        $this->assertFalse($session->check('Clearing.sale'));
        $this->assertFalse($session->check('Clearing'));
    }

    /**
     * test delete
     *
     * @return void
     */
    public function testDeleteEmptyString()
    {
        $session = new Session();
        $session->write('', 'empty string');
        $session->delete('');
        $this->assertFalse($session->check(''));
    }

    /**
     * testDestroy method
     *
     * @return void
     */
    public function testDestroy()
    {
        $session = new Session();
        $session->start();
        $session->write('bulletProof', 'invincible');
        $session->id('foo');
        $session->destroy();

        $this->assertFalse($session->check('bulletProof'));
    }

    /**
     * testCheckingSavedEmpty method
     *
     * @return void
     */
    public function testCheckingSavedEmpty()
    {
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
    public function testCheckKeyWithSpaces()
    {
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
    public function testCheckEmpty()
    {
        $session = new Session();
        $this->assertFalse($session->check());
    }

    /**
     * test key exploitation
     *
     * @return void
     */
    public function testKeyExploit()
    {
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
    public function testReadingSavedEmpty()
    {
        $session = new Session();
        $session->write('', 'empty string');
        $this->assertTrue($session->check(''));
        $this->assertEquals('empty string', $session->read(''));

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
    public function testUsingAppLibsHandler()
    {
        Configure::write('App.namespace', 'TestApp');
        $config = [
            'defaults' => 'cake',
            'handler' => [
                'engine' => 'TestAppLibSession',
                'these' => 'are',
                'a few' => 'options'
            ]
        ];

        $session = Session::create($config);
        $this->assertInstanceOf('TestApp\Network\Session\TestAppLibSession', $session->engine());
        $this->assertEquals('user', ini_get('session.save_handler'));
        $this->assertEquals(['these' => 'are', 'a few' => 'options'], $session->engine()->options);
    }

    /**
     * test using a handler from a plugin.
     *
     * @return void
     */
    public function testUsingPluginHandler()
    {
        Configure::write('App.namespace', 'TestApp');
        \Cake\Core\Plugin::load('TestPlugin');

        $config = [
            'defaults' => 'cake',
            'handler' => [
                'engine' => 'TestPlugin.TestPluginSession'
            ]
        ];

        $session = Session::create($config);
        $this->assertInstanceOf('TestPlugin\Network\Session\TestPluginSession', $session->engine());
        $this->assertEquals('user', ini_get('session.save_handler'));
    }

    /**
     * Tests that it is possible to pass an already made instance as the session engine
     *
     * @return void
     */
    public function testEngineWithPreMadeInstance()
    {
        Configure::write('App.namespace', 'TestApp');
        $engine = new \TestApp\Network\Session\TestAppLibSession;
        $session = new Session(['handler' => ['engine' => $engine]]);
        $this->assertSame($engine, $session->engine());

        $session = new Session();
        $session->engine($engine);
        $this->assertSame($engine, $session->engine());
    }

    /**
     * Tests instantiating a missing engine
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The class "Derp" does not exist and cannot be used as a session engine
     * @return void
     */
    public function testBadEngine()
    {
        $session = new Session();
        $session->engine('Derp');
    }

    /**
     * Test that cookieTimeout matches timeout when unspecified.
     *
     * @return void
     */
    public function testCookieTimeoutFallback()
    {
        $config = [
            'defaults' => 'cake',
            'timeout' => 400,
        ];

        new Session($config);
        $this->assertEquals(0, ini_get('session.cookie_lifetime'));
        $this->assertEquals(400 * 60, ini_get('session.gc_maxlifetime'));
    }

    /**
     * Tests that the cookie name can be changed with configuration
     *
     * @return void
     */
    public function testSessionName()
    {
        new Session(['cookie' => 'made_up_name']);
        $this->assertEquals('made_up_name', session_name());
    }
}
