<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http;

use Cake\Http\Session;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use RuntimeException;
use TestApp\Http\Session\TestAppLibSession;
use TestApp\Http\Session\TestWebSession;

/**
 * SessionTest class
 */
class SessionTest extends TestCase
{
    /**
     * tearDown method
     */
    public function tearDown(): void
    {
        unset($_SESSION);
        parent::tearDown();
        $this->clearPlugins();
    }

    /**
     * test setting ini properties with Session configuration.
     *
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testSessionConfigIniSetting(): void
    {
        $_SESSION = null;

        $config = [
            'cookie' => 'test',
            'checkAgent' => false,
            'timeout' => 86400,
            'ini' => [
                'session.referer_check' => 'example.com',
                'session.use_trans_sid' => false,
            ],
        ];

        Session::create($config);
        $this->assertSame('', ini_get('session.use_trans_sid'), 'Ini value is incorrect');
        $this->assertSame('example.com', ini_get('session.referer_check'), 'Ini value is incorrect');
        $this->assertSame('test', ini_get('session.name'), 'Ini value is incorrect');
    }

    /**
     * test session cookie path setting
     *
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testCookiePath(): void
    {
        ini_set('session.cookie_path', '/foo');

        new Session();
        $this->assertSame('/', ini_get('session.cookie_path'));

        new Session(['cookiePath' => '/base']);
        $this->assertSame('/base', ini_get('session.cookie_path'));
    }

    /**
     * testCheck method
     */
    public function testCheck(): void
    {
        $session = new Session();
        $session->write('SessionTestCase', 'value');
        $this->assertTrue($session->check());
        $this->assertTrue($session->check('SessionTestCase'));
        $this->assertFalse($session->check('NotExistingSessionTestCase'));
        $this->assertFalse($session->check('Crazy.foo'));
        $session->write('Crazy.foo', ['bar' => 'baz']);
        $this->assertTrue($session->check('Crazy.foo'));
        $this->assertTrue($session->check('Crazy.foo.bar'));
    }

    /**
     * test read with simple values
     */
    public function testReadSimple(): void
    {
        $session = new Session();
        $session->write('testing', '1,2,3');
        $result = $session->read('testing');
        $this->assertSame('1,2,3', $result);

        $session->write('testing', ['1' => 'one', '2' => 'two', '3' => 'three']);
        $result = $session->read('testing.1');
        $this->assertSame('one', $result);

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
     */
    public function testReadEmpty(): void
    {
        $session = new Session();
        $this->assertNull($session->read(''));
    }

    /**
     * test read fallback
     */
    public function testReadFallback(): void
    {
        $_SESSION = null;
        $session = new Session();
        $this->assertSame('default', $session->read('no', 'default'));
    }

    /**
     * Tests read() with defaulting.
     */
    public function testReadDefault(): void
    {
        $session = new Session();
        $this->assertSame('bar', $session->read('foo', 'bar'));
    }

    /**
     * Tests readOrFail()
     */
    public function testReadOrFail(): void
    {
        $session = new Session();
        $session->write('testing', '1,2,3');
        $result = $session->readOrFail('testing');
        $this->assertSame('1,2,3', $result);

        $session->write('testing', ['1' => 'one', '2' => 'two', '3' => 'three']);
        $result = $session->readOrFail('testing.1');
        $this->assertSame('one', $result);
    }

    /**
     * Tests readOrFail() with nonexistent value
     */
    public function testReadOrFailException(): void
    {
        $session = new Session();

        $this->expectException(RuntimeException::class);

        $session->readOrFail('testing');
    }

    /**
     * Test writing simple keys
     */
    public function testWriteSimple(): void
    {
        $session = new Session();
        $session->write('', 'empty');
        $this->assertSame('empty', $session->read(''));

        $session->write('Simple', ['values']);
        $this->assertEquals(['values'], $session->read('Simple'));
    }

    /**
     * test writing a hash of values
     */
    public function testWriteArray(): void
    {
        $session = new Session();
        $session->write([
            'one' => 1,
            'two' => 2,
            'three' => ['something'],
            'null' => null,
        ]);
        $this->assertSame(1, $session->read('one'));
        $this->assertEquals(['something'], $session->read('three'));
        $this->assertNull($session->read('null'));
    }

    /**
     * Test overwriting a string value as if it were an array.
     */
    public function testWriteOverwriteStringValue(): void
    {
        $session = new Session();
        $session->write('Some.string', 'value');
        $this->assertSame('value', $session->read('Some.string'));

        $session->write('Some.string.array', ['values']);
        $this->assertEquals(['values'], $session->read('Some.string.array'));
    }

    /**
     * Test consuming session data.
     */
    public function testConsume(): void
    {
        $session = new Session();
        $session->write('Some.string', 'value');
        $session->write('Some.array', ['key1' => 'value1', 'key2' => 'value2']);

        $this->assertSame('value', $session->read('Some.string'));

        $value = $session->consume('Some.string');
        $this->assertSame('value', $value);
        $this->assertFalse($session->check('Some.string'));

        $value = $session->consume('');
        $this->assertNull($value);

        $value = $session->consume('Some.array');
        $expected = ['key1' => 'value1', 'key2' => 'value2'];
        $this->assertEquals($expected, $value);
        $this->assertFalse($session->check('Some.array'));
    }

    /**
     * testId method
     *
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testId(): void
    {
        $session = new Session();
        $session->start();
        $result = $session->id();
        $this->assertNotEmpty($result);
        $this->assertSame(session_id(), $result);

        $session->id('MySessionId');
        $this->assertSame('MySessionId', $session->id());
        $this->assertSame('MySessionId', session_id());

        $session->id('');
        $this->assertSame('', session_id());
    }

    /**
     * testStarted method
     */
    public function testStarted(): void
    {
        $session = new Session();
        $this->assertFalse($session->started());
        $this->assertTrue($session->start());
        $this->assertTrue($session->started());
    }

    /**
     * test close method
     */
    public function testCloseNotStarted(): void
    {
        $session = new Session();
        $this->assertTrue($session->start());

        $session->close();
        $this->assertFalse($session->started());
    }

    /**
     * testClear method
     */
    public function testClear(): void
    {
        $session = new Session();
        $session->write('Delete.me', 'Clearing out');

        $session->clear();
        $this->assertFalse($session->check('Delete.me'));
        $this->assertFalse($session->check('Delete'));
    }

    /**
     * testDelete method
     */
    public function testDelete(): void
    {
        $session = new Session();
        $session->write('Delete.me', 'Clearing out');
        $session->delete('Delete.me');
        $this->assertFalse($session->check('Delete.me'));
        $this->assertTrue($session->check('Delete'));

        $session->write('Clearing.sale', 'everything must go');
        $session->delete('');
        $this->assertTrue($session->check('Clearing.sale'));

        $session->delete('Clearing');
        $this->assertFalse($session->check('Clearing.sale'));
        $this->assertFalse($session->check('Clearing'));
    }

    /**
     * test delete
     */
    public function testDeleteEmptyString(): void
    {
        $session = new Session();
        $session->write('', 'empty string');
        $session->delete('');
        $this->assertFalse($session->check(''));
    }

    /**
     * testDestroy method
     */
    public function testDestroy(): void
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
     */
    public function testCheckingSavedEmpty(): void
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
     */
    public function testCheckKeyWithSpaces(): void
    {
        $session = new Session();
        $session->write('Session Test', 'test');
        $this->assertTrue($session->check('Session Test'));
        $session->delete('Session Test');

        $session->write('Session Test.Test Case', 'test');
        $this->assertTrue($session->check('Session Test.Test Case'));
    }

    /**
     * testCheckEmpty
     */
    public function testCheckEmpty(): void
    {
        $session = new Session();
        $this->assertFalse($session->check());
    }

    /**
     * test key exploitation
     */
    public function testKeyExploit(): void
    {
        $session = new Session();
        $key = "a'] = 1; phpinfo(); \$_SESSION['a";
        $session->write($key, 'haxored');

        $result = $session->read($key);
        $this->assertNull($result);
    }

    /**
     * testReadingSavedEmpty method
     */
    public function testReadingSavedEmpty(): void
    {
        $session = new Session();
        $session->write('', 'empty string');
        $this->assertTrue($session->check(''));
        $this->assertSame('empty string', $session->read(''));

        $session->write('SessionTestCase', 0);
        $this->assertSame(0, $session->read('SessionTestCase'));

        $session->write('SessionTestCase', '0');
        $this->assertSame('0', $session->read('SessionTestCase'));
        $this->assertNotSame($session->read('SessionTestCase'), 0);

        $session->write('SessionTestCase', false);
        $this->assertFalse($session->read('SessionTestCase'));

        $session->write('SessionTestCase', null);
        $this->assertNull($session->read('SessionTestCase'));
    }

    /**
     * test using a handler from app/Http/Session.
     *
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testUsingAppLibsHandler(): void
    {
        static::setAppNamespace();
        $config = [
            'defaults' => 'cake',
            'handler' => [
                'engine' => 'TestAppLibSession',
                'these' => 'are',
                'a few' => 'options',
            ],
        ];

        $session = Session::create($config);
        $this->assertInstanceOf('TestApp\Http\Session\TestAppLibSession', $session->engine());
        $this->assertSame('user', ini_get('session.save_handler'));
        $this->assertEquals(['these' => 'are', 'a few' => 'options'], $session->engine()->options);
    }

    /**
     * test using a handler from a plugin.
     *
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testUsingPluginHandler(): void
    {
        static::setAppNamespace();
        $this->loadPlugins(['TestPlugin']);

        $config = [
            'defaults' => 'cake',
            'handler' => [
                'engine' => 'TestPlugin.TestPluginSession',
            ],
        ];

        $session = Session::create($config);
        $this->assertInstanceOf('TestPlugin\Http\Session\TestPluginSession', $session->engine());
        $this->assertSame('user', ini_get('session.save_handler'));
    }

    /**
     * Tests that it is possible to pass an already made instance as the session engine
     *
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testEngineWithPreMadeInstance(): void
    {
        static::setAppNamespace();
        $engine = new TestAppLibSession();
        $session = new Session(['handler' => ['engine' => $engine]]);
        $this->assertSame($engine, $session->engine());

        $session = new Session();
        $session->engine($engine);
        $this->assertSame($engine, $session->engine());
    }

    /**
     * Tests instantiating a missing engine
     */
    public function testBadEngine(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The class "Derp" does not exist and cannot be used as a session engine');
        $session = new Session();
        $session->engine('Derp');
    }

    /**
     * Test that cookieTimeout matches timeout when unspecified.
     *
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testCookieTimeoutFallback(): void
    {
        $config = [
            'defaults' => 'cake',
            'timeout' => 400,
        ];

        new Session($config);
        $this->assertSame('0', ini_get('session.cookie_lifetime'));
        $this->assertSame((string)(400 * 60), ini_get('session.gc_maxlifetime'));
    }

    /**
     * Tests that the cookie name can be changed with configuration
     *
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testSessionName(): void
    {
        new Session(['cookie' => 'made_up_name']);
        $this->assertSame('made_up_name', session_name());
    }

    /**
     * Test that a call of check() starts the session when cookies are disabled in php.ini
     *
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testCheckStartsSessionWithCookiesDisabled(): void
    {
        $_COOKIE = [];
        $_GET = [];

        $session = new TestWebSession([
            'ini' => [
                'session.use_cookies' => 0,
                'session.use_trans_sid' => 0,
            ],
        ]);

        $this->assertFalse($session->started());
        $session->check('something');
        $this->assertTrue($session->started());
    }

    /**
     * Test that a call of check() starts the session when a cookie is already set
     */
    public function testCheckStartsSessionWithCookie(): void
    {
        $_COOKIE[session_name()] = '123abc';
        $_GET = [];

        $session = new TestWebSession([
            'ini' => [
                'session.use_cookies' => 1,
                'session.use_trans_sid' => 0,
            ],
        ]);

        $this->assertFalse($session->started());
        $session->check('something');
        $this->assertTrue($session->started());
    }

    /**
     * Test that a call of check() starts the session when the session ID is passed via URL and session.use_trans_sid is enabled
     *
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testCheckStartsSessionWithSIDinURL(): void
    {
        $_COOKIE = [];
        $_GET[session_name()] = '123abc';

        $session = new TestWebSession([
            'ini' => [
                'session.use_cookies' => 1,
                'session.use_trans_sid' => 1,
            ],
        ]);

        $this->assertFalse($session->started());
        $session->check('something');
        $this->assertTrue($session->started());
    }

    /**
     * Test that a call of check() does not start the session when the session ID is passed via URL and session.use_trans_sid is disabled
     */
    public function testCheckDoesntStartSessionWithoutTransSID(): void
    {
        $_COOKIE = [];
        $_GET[session_name()] = '123abc';

        $session = new TestWebSession([
            'ini' => [
                'session.use_cookies' => 1,
                'session.use_trans_sid' => 0,
            ],
        ]);

        $this->assertFalse($session->started());
        $session->check('something');
        $this->assertFalse($session->started());
    }
}
