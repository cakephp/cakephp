<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Auth;

use Cake\Auth\BasicAuthenticate;
use Cake\Controller\ComponentRegistry;
use Cake\Http\Exception\UnauthorizedException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\I18n\FrozenTime;
use Cake\TestSuite\TestCase;

/**
 * Test case for BasicAuthentication
 */
class BasicAuthenticateTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = ['core.AuthUsers', 'core.Users'];

    /**
     * @var \Cake\Controller\ComponentRegistry
     */
    protected $collection;

    /**
     * @var \Cake\Auth\BasicAuthenticate
     */
    protected $auth;

    /**
     * setup
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->collection = new ComponentRegistry();
        $this->auth = new BasicAuthenticate($this->collection, [
            'userModel' => 'Users',
            'realm' => 'localhost',
        ]);

        $password = password_hash('password', PASSWORD_BCRYPT);
        $User = $this->getTableLocator()->get('Users');
        $User->updateAll(['password' => $password], []);
    }

    /**
     * test applying settings in the constructor
     */
    public function testConstructor(): void
    {
        $object = new BasicAuthenticate($this->collection, [
            'userModel' => 'AuthUser',
            'fields' => ['username' => 'user', 'password' => 'password'],
        ]);
        $this->assertSame('AuthUser', $object->getConfig('userModel'));
        $this->assertEquals(['username' => 'user', 'password' => 'password'], $object->getConfig('fields'));
    }

    /**
     * test the authenticate method
     */
    public function testAuthenticateNoData(): void
    {
        $request = new ServerRequest(['url' => 'posts/index']);

        $this->assertFalse($this->auth->getUser($request));
    }

    /**
     * test the authenticate method
     */
    public function testAuthenticateNoUsername(): void
    {
        $request = new ServerRequest([
            'url' => 'posts/index',
            'environment' => ['PHP_AUTH_PW' => 'foobar'],
        ]);

        $this->assertFalse($this->auth->authenticate($request, new Response()));
    }

    /**
     * test the authenticate method
     */
    public function testAuthenticateNoPassword(): void
    {
        $request = new ServerRequest([
            'url' => 'posts/index',
            'environment' => ['PHP_AUTH_USER' => 'mariano'],
        ]);

        $this->assertFalse($this->auth->authenticate($request, new Response()));
    }

    /**
     * test the authenticate method
     */
    public function testAuthenticateInjection(): void
    {
        $request = new ServerRequest([
            'url' => 'posts/index',
            'environment' => [
                'PHP_AUTH_USER' => '> 1',
                'PHP_AUTH_PW' => "' OR 1 = 1",
            ],
        ]);

        $this->assertFalse($this->auth->getUser($request));
        $this->assertFalse($this->auth->authenticate($request, new Response()));
    }

    /**
     * Test that username of 0 works.
     */
    public function testAuthenticateUsernameZero(): void
    {
        $User = $this->getTableLocator()->get('Users');
        $User->updateAll(['username' => '0'], ['username' => 'mariano']);

        $request = new ServerRequest([
            'url' => 'posts/index',
            'data' => [
                'User' => [
                    'user' => '0',
                    'password' => 'password',
                ],
            ],
        ]);
        $_SERVER['PHP_AUTH_USER'] = '0';
        $_SERVER['PHP_AUTH_PW'] = 'password';

        $expected = [
            'id' => 1,
            'username' => '0',
            'created' => new FrozenTime('2007-03-17 01:16:23'),
            'updated' => new FrozenTime('2007-03-17 01:18:31'),
        ];
        $this->assertEquals($expected, $this->auth->authenticate($request, new Response()));
    }

    /**
     * test that challenge headers are sent when no credentials are found.
     */
    public function testAuthenticateChallenge(): void
    {
        $request = new ServerRequest(['url' => 'posts/index']);

        $e = null;
        try {
            $this->auth->unauthenticated($request, new Response());
        } catch (UnauthorizedException $e) {
        }

        $this->assertNotEmpty($e);

        $expected = ['WWW-Authenticate' => 'Basic realm="localhost"'];
        $this->assertEquals($expected, $e->getHeaders());
    }

    /**
     * test authenticate success
     */
    public function testAuthenticateSuccess(): void
    {
        $request = new ServerRequest([
            'url' => 'posts/index',
            'environment' => [
                'PHP_AUTH_USER' => 'mariano',
                'PHP_AUTH_PW' => 'password',
            ],
        ]);

        $result = $this->auth->authenticate($request, new Response());
        $expected = [
            'id' => 1,
            'username' => 'mariano',
            'created' => new FrozenTime('2007-03-17 01:16:23'),
            'updated' => new FrozenTime('2007-03-17 01:18:31'),
        ];
        $this->assertEquals($expected, $result);
    }
}
