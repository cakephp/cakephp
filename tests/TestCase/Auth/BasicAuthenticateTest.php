<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Auth;

use Cake\Auth\BasicAuthenticate;
use Cake\Controller\ComponentRegistry;
use Cake\I18n\Time;
use Cake\Network\Exception\UnauthorizedException;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Test case for BasicAuthentication
 */
class BasicAuthenticateTest extends TestCase
{

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = ['core.auth_users', 'core.users'];

    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->Collection = $this->getMockBuilder(ComponentRegistry::class)->getMock();
        $this->auth = new BasicAuthenticate($this->Collection, [
            'userModel' => 'Users',
            'realm' => 'localhost'
        ]);

        $password = password_hash('password', PASSWORD_BCRYPT);
        $User = TableRegistry::get('Users');
        $User->updateAll(['password' => $password], []);
        $this->response = $this->getMockBuilder(Response::class)->getMock();
    }

    /**
     * test applying settings in the constructor
     *
     * @return void
     */
    public function testConstructor()
    {
        $object = new BasicAuthenticate($this->Collection, [
            'userModel' => 'AuthUser',
            'fields' => ['username' => 'user', 'password' => 'password']
        ]);
        $this->assertEquals('AuthUser', $object->config('userModel'));
        $this->assertEquals(['username' => 'user', 'password' => 'password'], $object->config('fields'));
    }

    /**
     * test the authenticate method
     *
     * @return void
     */
    public function testAuthenticateNoData()
    {
        $request = new Request('posts/index');

        $this->response->expects($this->never())
            ->method('header');

        $this->assertFalse($this->auth->getUser($request));
    }

    /**
     * test the authenticate method
     *
     * @return void
     */
    public function testAuthenticateNoUsername()
    {
        $request = new Request([
            'url' => 'posts/index',
            'environment' => ['PHP_AUTH_PW' => 'foobar']
        ]);

        $this->assertFalse($this->auth->authenticate($request, $this->response));
    }

    /**
     * test the authenticate method
     *
     * @return void
     */
    public function testAuthenticateNoPassword()
    {
        $request = new Request([
            'url' => 'posts/index',
            'environment' => ['PHP_AUTH_USER' => 'mariano']
        ]);

        $this->assertFalse($this->auth->authenticate($request, $this->response));
    }

    /**
     * test the authenticate method
     *
     * @return void
     */
    public function testAuthenticateInjection()
    {
        $request = new Request([
            'url' => 'posts/index',
            'environment' => [
                'PHP_AUTH_USER' => '> 1',
                'PHP_AUTH_PW' => "' OR 1 = 1"
            ]
        ]);
        $request->addParams(['pass' => []]);

        $this->assertFalse($this->auth->getUser($request));
        $this->assertFalse($this->auth->authenticate($request, $this->response));
    }

    /**
     * Test that username of 0 works.
     *
     * @return void
     */
    public function testAuthenticateUsernameZero()
    {
        $User = TableRegistry::get('Users');
        $User->updateAll(['username' => '0'], ['username' => 'mariano']);

        $request = new Request('posts/index');
        $request->data = ['User' => [
            'user' => '0',
            'password' => 'password'
        ]];
        $_SERVER['PHP_AUTH_USER'] = '0';
        $_SERVER['PHP_AUTH_PW'] = 'password';

        $expected = [
            'id' => 1,
            'username' => '0',
            'created' => new Time('2007-03-17 01:16:23'),
            'updated' => new Time('2007-03-17 01:18:31'),
        ];
        $this->assertEquals($expected, $this->auth->authenticate($request, $this->response));
    }

    /**
     * test that challenge headers are sent when no credentials are found.
     *
     * @return void
     */
    public function testAuthenticateChallenge()
    {
        $request = new Request('posts/index');
        $request->addParams(['pass' => []]);

        try {
            $this->auth->unauthenticated($request, $this->response);
        } catch (UnauthorizedException $e) {
        }

        $this->assertNotEmpty($e);

        $expected = ['WWW-Authenticate: Basic realm="localhost"'];
        $this->assertEquals($expected, $e->responseHeader());
    }

    /**
     * test authenticate success
     *
     * @return void
     */
    public function testAuthenticateSuccess()
    {
        $request = new Request([
            'url' => 'posts/index',
            'environment' => [
                'PHP_AUTH_USER' => 'mariano',
                'PHP_AUTH_PW' => 'password'
            ]
        ]);
        $request->addParams(['pass' => []]);

        $result = $this->auth->authenticate($request, $this->response);
        $expected = [
            'id' => 1,
            'username' => 'mariano',
            'created' => new Time('2007-03-17 01:16:23'),
            'updated' => new Time('2007-03-17 01:18:31')
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * test scope failure.
     *
     * @expectedException \Cake\Network\Exception\UnauthorizedException
     * @expectedExceptionCode 401
     * @return void
     */
    public function testAuthenticateFailReChallenge()
    {
        $this->auth->config('scope.username', 'nate');
        $request = new Request([
            'url' => 'posts/index',
            'environment' => [
                'PHP_AUTH_USER' => 'mariano',
                'PHP_AUTH_PW' => 'password'
            ]
        ]);
        $request->addParams(['pass' => []]);

        $this->auth->unauthenticated($request, $this->response);
    }
}
