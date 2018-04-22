<?php
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
namespace Cake\Test\TestCase\Controller\Component;

use Cake\Auth\BaseAuthorize;
use Cake\Auth\FormAuthenticate;
use Cake\Controller\Component\AuthComponent;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Cake\Routing\Route\InflectedRoute;
use Cake\TestSuite\TestCase;
use Cake\Utility\Security;
use TestApp\Controller\AuthTestController;
use TestApp\Controller\Component\TestAuthComponent;

/**
 * AuthComponentTest class
 */
class AuthComponentTest extends TestCase
{

    /**
     * AuthComponent property
     *
     * @var \TestApp\Controller\Component\TestAuthComponent
     */
    public $Auth;

    /**
     * fixtures property
     *
     * @var array
     */
    public $fixtures = ['core.auth_users', 'core.users'];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        Security::setSalt('YJfIxfs2guVoUubWDYhG93b0qyJfIxfs2guwvniR2G0FgaC9mi');
        static::setAppNamespace();

        Router::scope('/', function ($routes) {
            $routes->fallbacks(InflectedRoute::class);
        });

        $request = new ServerRequest([
            'url' => '/auth_test',
            'environment' => [
                'REQUEST_METHOD' => 'GET'
            ],
            'params' => [
                'plugin' => null,
                'controller' => 'AuthTest',
                'action' => 'index'
            ],
            'webroot' => '/'
        ]);

        $response = new Response();
        $this->Controller = new AuthTestController($request, $response);
        $this->Auth = new TestAuthComponent($this->Controller->components());

        $Users = $this->getTableLocator()->get('AuthUsers');
        $Users->updateAll(['password' => password_hash('cake', PASSWORD_BCRYPT)], []);
        $this->request = $request;
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        $_SESSION = [];
        unset($this->Controller, $this->Auth);
    }

    /**
     * testNoAuth method
     *
     * @return void
     */
    public function testNoAuth()
    {
        $this->assertFalse($this->Auth->isAuthorized());
    }

    /**
     * testIdentify method
     *
     * @return void
     */
    public function testIdentify()
    {
        $AuthLoginFormAuthenticate = $this->getMockBuilder(FormAuthenticate::class)
            ->setMethods(['authenticate'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->Auth->authenticate = [
            'AuthLoginForm' => [
                'userModel' => 'AuthUsers'
            ]
        ];

        $this->Auth->setAuthenticateObject(0, $AuthLoginFormAuthenticate);

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'AuthUsers' => [
                'username' => 'mark',
                'password' => Security::hash('cake', null, true)
            ]
        ]);

        $user = [
            'id' => 1,
            'username' => 'mark'
        ];

        $AuthLoginFormAuthenticate->expects($this->once())
            ->method('authenticate')
            ->with($this->Controller->request)
            ->will($this->returnValue($user));

        $result = $this->Auth->identify();
        $this->assertEquals($user, $result);
        $this->assertSame($AuthLoginFormAuthenticate, $this->Auth->authenticationProvider());
    }

    /**
     * Test identify with user record as ArrayObject instance.
     *
     * @return void
     */
    public function testIdentifyArrayAccess()
    {
        $AuthLoginFormAuthenticate = $this->getMockBuilder(FormAuthenticate::class)
            ->setMethods(['authenticate'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->Auth->authenticate = [
            'AuthLoginForm' => [
                'userModel' => 'AuthUsers'
            ]
        ];

        $this->Auth->setAuthenticateObject(0, $AuthLoginFormAuthenticate);

        $this->Controller->request = $this->Controller->request->withParsedBody([
            'AuthUsers' => [
                'username' => 'mark',
                'password' => Security::hash('cake', null, true)
            ]
        ]);

        $user = new \ArrayObject([
            'id' => 1,
            'username' => 'mark'
        ]);

        $AuthLoginFormAuthenticate->expects($this->once())
            ->method('authenticate')
            ->with($this->Controller->request)
            ->will($this->returnValue($user));

        $result = $this->Auth->identify();
        $this->assertEquals($user, $result);
        $this->assertSame($AuthLoginFormAuthenticate, $this->Auth->authenticationProvider());
    }

    /**
     * testAuthorizeFalse method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testAuthorizeFalse()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $Users = $this->getTableLocator()->get('Users');
        $user = $Users->find('all')->enableHydration(false)->first();
        $this->Controller->Auth->storage()->write($user);
        $this->Controller->Auth->setConfig('userModel', 'Users');
        $this->Controller->Auth->setConfig('authorize', false);
        $this->Controller->request = $this->request->withAttribute('params', ['controller' => 'AuthTest', 'action' => 'add']);
        $result = $this->Controller->Auth->startup($event);
        $this->assertNull($result);

        $this->Controller->Auth->storage()->delete();
        $result = $this->Controller->Auth->startup($event);
        $this->assertTrue($event->isStopped());
        $this->assertInstanceOf('Cake\Http\Response', $result);
        $this->assertTrue($this->Auth->session->check('Flash.flash'));

        $this->Controller->request = $this->request->withAttribute('params', ['controller' => 'AuthTest', 'action' => 'camelCase']);
        $result = $this->Controller->Auth->startup($event);
        $this->assertInstanceOf('Cake\Http\Response', $result);
    }

    /**
     * testIsAuthorizedMissingFile function
     *
     * @return void
     */
    public function testIsAuthorizedMissingFile()
    {
        $this->expectException(\Cake\Core\Exception\Exception::class);
        $this->Controller->Auth->setConfig('authorize', 'Missing');
        $this->Controller->Auth->isAuthorized(['User' => ['id' => 1]]);
    }

    /**
     * test that isAuthorized calls methods correctly
     *
     * @return void
     */
    public function testIsAuthorizedDelegation()
    {
        $AuthMockOneAuthorize = $this->getMockBuilder(BaseAuthorize::class)
            ->setMethods(['authorize'])
            ->disableOriginalConstructor()
            ->getMock();
        $AuthMockTwoAuthorize = $this->getMockBuilder(BaseAuthorize::class)
            ->setMethods(['authorize'])
            ->disableOriginalConstructor()
            ->getMock();
        $AuthMockThreeAuthorize = $this->getMockBuilder(BaseAuthorize::class)
            ->setMethods(['authorize'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->Auth->setAuthorizeObject(0, $AuthMockOneAuthorize);
        $this->Auth->setAuthorizeObject(1, $AuthMockTwoAuthorize);
        $this->Auth->setAuthorizeObject(2, $AuthMockThreeAuthorize);
        $request = $this->Controller->request;

        $AuthMockOneAuthorize->expects($this->once())
            ->method('authorize')
            ->with(['User'], $request)
            ->will($this->returnValue(false));

        $AuthMockTwoAuthorize->expects($this->once())
            ->method('authorize')
            ->with(['User'], $request)
            ->will($this->returnValue(true));

        $AuthMockThreeAuthorize->expects($this->never())
            ->method('authorize');

        $this->assertTrue($this->Auth->isAuthorized(['User'], $request));
        $this->assertSame($AuthMockTwoAuthorize, $this->Auth->authorizationProvider());
    }

    /**
     * test isAuthorized passing it an ArrayObject instance.
     *
     * @return void
     */
    public function testIsAuthorizedWithArrayObject()
    {
        $AuthMockOneAuthorize = $this->getMockBuilder(BaseAuthorize::class)
            ->setMethods(['authorize'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->Auth->setAuthorizeObject(0, $AuthMockOneAuthorize);
        $request = $this->Controller->request;

        $user = new \ArrayObject(['User']);

        $AuthMockOneAuthorize->expects($this->once())
            ->method('authorize')
            ->with($user, $request)
            ->will($this->returnValue(true));

        $this->assertTrue($this->Auth->isAuthorized($user, $request));
        $this->assertSame($AuthMockOneAuthorize, $this->Auth->authorizationProvider());
    }

    /**
     * test that isAuthorized will use the session user if none is given.
     *
     * @return void
     */
    public function testIsAuthorizedUsingUserInSession()
    {
        $AuthMockFourAuthorize = $this->getMockBuilder(BaseAuthorize::class)
            ->setMethods(['authorize'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->Auth->setConfig('authorize', ['AuthMockFour']);
        $this->Auth->setAuthorizeObject(0, $AuthMockFourAuthorize);

        $user = ['user' => 'mark'];
        $this->Auth->session->write('Auth.User', $user);
        $request = $this->Controller->request;

        $AuthMockFourAuthorize->expects($this->once())
            ->method('authorize')
            ->with($user, $request)
            ->will($this->returnValue(true));

        $this->assertTrue($this->Auth->isAuthorized(null, $request));
    }

    /**
     * test that loadAuthorize resets the loaded objects each time.
     *
     * @return void
     */
    public function testLoadAuthorizeResets()
    {
        $this->Controller->Auth->setConfig('authorize', ['Controller']);
        $result = $this->Controller->Auth->constructAuthorize();
        $this->assertCount(1, $result);

        $result = $this->Controller->Auth->constructAuthorize();
        $this->assertCount(1, $result);
    }

    /**
     * testLoadAuthenticateNoFile function
     *
     * @return void
     */
    public function testLoadAuthenticateNoFile()
    {
        $this->expectException(\Cake\Core\Exception\Exception::class);
        $this->Controller->Auth->setConfig('authenticate', 'Missing');
        $this->Controller->Auth->identify($this->Controller->request, $this->Controller->response);
    }

    /**
     * test the * key with authenticate
     *
     * @return void
     */
    public function testAllConfigWithAuthorize()
    {
        $this->Controller->Auth->setConfig('authorize', [
            AuthComponent::ALL => ['actionPath' => 'controllers/'],
            'Controller',
        ]);
        $objects = array_values($this->Controller->Auth->constructAuthorize());
        $result = $objects[0];
        $this->assertEquals('controllers/', $result->getConfig('actionPath'));
    }

    /**
     * test that loadAuthorize resets the loaded objects each time.
     *
     * @return void
     */
    public function testLoadAuthenticateResets()
    {
        $this->Controller->Auth->setConfig('authenticate', ['Form']);
        $result = $this->Controller->Auth->constructAuthenticate();
        $this->assertCount(1, $result);

        $result = $this->Controller->Auth->constructAuthenticate();
        $this->assertCount(1, $result);
    }

    /**
     * test the * key with authenticate
     *
     * @return void
     */
    public function testAllConfigWithAuthenticate()
    {
        $this->Controller->Auth->setConfig('authenticate', [
            AuthComponent::ALL => ['userModel' => 'AuthUsers'],
            'Form'
        ]);
        $objects = array_values($this->Controller->Auth->constructAuthenticate());
        $result = $objects[0];
        $this->assertEquals('AuthUsers', $result->getConfig('userModel'));
    }

    /**
     * test defining the same Authenticate object but with different password hashers
     *
     * @return void
     */
    public function testSameAuthenticateWithDifferentHashers()
    {
        $this->Controller->Auth->setConfig('authenticate', [
            'FormSimple' => ['className' => 'Form', 'passwordHasher' => 'Default'],
            'FormBlowfish' => ['className' => 'Form', 'passwordHasher' => 'Fallback'],
        ]);

        $objects = $this->Controller->Auth->constructAuthenticate();
        $this->assertCount(2, $objects);

        $this->assertInstanceOf('Cake\Auth\FormAuthenticate', $objects['FormSimple']);
        $this->assertInstanceOf('Cake\Auth\FormAuthenticate', $objects['FormBlowfish']);

        $this->assertInstanceOf('Cake\Auth\DefaultPasswordHasher', $objects['FormSimple']->passwordHasher());
        $this->assertInstanceOf('Cake\Auth\FallbackPasswordHasher', $objects['FormBlowfish']->passwordHasher());
    }

    /**
     * Tests that deny always takes precedence over allow
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testAllowDenyAll()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->Auth->allow();
        $this->Controller->Auth->deny(['add', 'camelCase']);

        $this->Controller->request = $this->Controller->request->withParam('action', 'delete');
        $this->assertNull($this->Controller->Auth->startup($event));

        $this->Controller->request = $this->Controller->request->withParam('action', 'add');
        $this->assertInstanceOf('Cake\Http\Response', $this->Controller->Auth->startup($event));

        $this->Controller->request = $this->Controller->request->withParam('action', 'camelCase');
        $this->assertInstanceOf('Cake\Http\Response', $this->Controller->Auth->startup($event));

        $this->Controller->Auth->allow();
        $this->Controller->Auth->deny(['add', 'camelCase']);

        $this->Controller->request = $this->Controller->request->withParam('action', 'delete');
        $this->assertNull($this->Controller->Auth->startup($event));

        $this->Controller->request = $this->Controller->request->withParam('action', 'camelCase');
        $this->assertInstanceOf('Cake\Http\Response', $this->Controller->Auth->startup($event));

        $this->Controller->Auth->allow();
        $this->Controller->Auth->deny();

        $this->Controller->request = $this->Controller->request->withParam('action', 'camelCase');
        $this->assertInstanceOf('Cake\Http\Response', $this->Controller->Auth->startup($event));

        $this->Controller->request = $this->Controller->request->withParam('action', 'add');
        $this->assertInstanceOf('Cake\Http\Response', $this->Controller->Auth->startup($event));

        $this->Controller->Auth->allow('camelCase');
        $this->Controller->Auth->deny();

        $this->Controller->request = $this->Controller->request->withParam('action', 'camelCase');
        $this->assertInstanceOf('Cake\Http\Response', $this->Controller->Auth->startup($event));

        $this->Controller->request = $this->Controller->request->withParam('action', 'login');
        $this->assertInstanceOf('Cake\Http\Response', $this->Controller->Auth->startup($event));

        $this->Controller->Auth->deny();
        $this->Controller->Auth->allow(null);

        $this->Controller->request = $this->Controller->request->withParam('action', 'camelCase');
        $this->assertNull($this->Controller->Auth->startup($event));

        $this->Controller->Auth->allow();
        $this->Controller->Auth->deny(null);

        $this->assertInstanceOf('Cake\Http\Response', $this->Controller->Auth->startup($event));
    }

    /**
     * test that deny() converts camel case inputs to lowercase.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testDenyWithCamelCaseMethods()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->Auth->allow();
        $this->Controller->Auth->deny(['add', 'camelCase']);

        $url = '/auth_test/camelCase';
        $this->Controller->request = $this->request->withAttribute(
            'params',
            ['controller' => 'AuthTest', 'action' => 'camelCase']
        )->withQueryParams(['url' => Router::normalize($url)]);

        $this->assertInstanceOf('Cake\Http\Response', $this->Controller->Auth->startup($event));

        $url = '/auth_test/CamelCase';
        $this->Controller->request = $this->request->withAttribute(
            'params',
            ['controller' => 'AuthTest', 'action' => 'camelCase']
        )->withQueryParams(['url' => Router::normalize($url)]);
        $this->assertInstanceOf('Cake\Http\Response', $this->Controller->Auth->startup($event));
    }

    /**
     * test that allow() and allowedActions work with camelCase method names.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testAllowedActionsWithCamelCaseMethods()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $url = '/auth_test/camelCase';
        $this->Controller->request = $this->request
            ->withAttribute('params', ['controller' => 'AuthTest', 'action' => 'camelCase'])
            ->withRequestTarget($url);
        $this->Controller->Auth->loginAction = ['controller' => 'AuthTest', 'action' => 'login'];
        $this->Controller->Auth->userModel = 'AuthUsers';
        $this->Controller->Auth->allow();
        $result = $this->Controller->Auth->startup($event);
        $this->assertNull($result, 'startup() should return null, as action is allowed. %s');

        $url = '/auth_test/camelCase';
        $this->Controller->request = $this->request
            ->withAttribute('params', ['controller' => 'AuthTest', 'action' => 'camelCase'])
            ->withRequestTarget($url);
        $this->Controller->Auth->loginAction = ['controller' => 'AuthTest', 'action' => 'login'];
        $this->Controller->Auth->userModel = 'AuthUsers';
        $this->Controller->Auth->allowedActions = ['delete', 'camelCase', 'add'];
        $result = $this->Controller->Auth->startup($event);
        $this->assertNull($result, 'startup() should return null, as action is allowed. %s');

        $this->Controller->Auth->allowedActions = ['delete', 'add'];
        $this->assertInstanceOf('Cake\Http\Response', $this->Controller->Auth->startup($event));

        $url = '/auth_test/delete';
        $this->Controller->request = $this->request
            ->withAttribute('params', ['controller' => 'AuthTest', 'action' => 'delete'])
            ->withRequestTarget($url);
        $this->Controller->Auth->loginAction = ['controller' => 'AuthTest', 'action' => 'login'];
        $this->Controller->Auth->userModel = 'AuthUsers';

        $this->Controller->Auth->allow(['delete', 'add']);
        $result = $this->Controller->Auth->startup($event);
        $this->assertNull($result, 'startup() should return null, as action is allowed. %s');
    }

    /**
     * testAllowedActionsSetWithAllowMethod method
     *
     * @return void
     */
    public function testAllowedActionsSetWithAllowMethod()
    {
        $url = '/auth_test/action_name';
        $this->Controller->request = $this->request
            ->withAttribute('params', ['controller' => 'AuthTest', 'action' => 'action_name']);
        $this->Controller->Auth->allow(['action_name', 'anotherAction']);
        $this->assertEquals(['action_name', 'anotherAction'], $this->Controller->Auth->allowedActions);
    }

    /**
     * testLoginRedirect method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testLoginRedirect()
    {
        $this->Auth->session->write('Auth', [
            'AuthUsers' => ['id' => '1', 'username' => 'nate']
        ]);

        $this->Controller->request = $this->Controller->request = new ServerRequest([
            'params' => ['controller' => 'Users', 'action' => 'login'],
            'url' => '/users/login',
            'environment' => ['HTTP_REFERER' => false],
            'session' => $this->Auth->session
        ]);

        $this->Auth->setConfig('loginRedirect', [
            'controller' => 'pages',
            'action' => 'display',
            'welcome'
        ]);
        $event = new Event('Controller.startup', $this->Controller);
        $this->Auth->startup($event);
        $expected = Router::normalize($this->Auth->getConfig('loginRedirect'));
        $this->assertEquals($expected, $this->Auth->redirectUrl());

        $this->Auth->session->delete('Auth');

        $this->Auth->session->write(
            'Auth',
            ['AuthUsers' => ['id' => '1', 'username' => 'nate']]
        );
        $this->Controller->request = $this->Controller->request = new ServerRequest([
            'params' => ['controller' => 'Posts', 'action' => 'view', 'pass' => [1]],
            'url' => '/posts/view/1',
            'environment' => ['HTTP_REFERER' => false, 'REQUEST_METHOD' => 'GET'],
            'session' => $this->Auth->session
        ]);

        $this->Auth->setConfig('authorize', 'controller');

        $this->Auth->setConfig('loginAction', [
            'controller' => 'AuthTest', 'action' => 'login'
        ]);
        $event = new Event('Controller.startup', $this->Controller);
        $response = $this->Auth->startup($event);
        $expected = Router::url([
            'controller' => 'AuthTest',
            'action' => 'login',
            '?' => ['redirect' => '/posts/view/1']
        ], true);
        $redirectHeader = $response->getHeaderLine('Location');
        $this->assertEquals($expected, $redirectHeader);

        // Auth.redirect gets set when accessing a protected action without being authenticated
        $this->Auth->session->delete('Auth');

        $this->Controller->request = $this->Controller->request = new ServerRequest([
            'params' => ['controller' => 'Posts', 'action' => 'view', 'pass' => [1]],
            'url' => '/posts/view/1',
            'environment' => ['HTTP_REFERER' => false, 'REQUEST_METHOD' => 'GET'],
            'session' => $this->Auth->session
        ]);
        $this->Auth->setConfig('loginAction', ['controller' => 'AuthTest', 'action' => 'login']);
        $event = new Event('Controller.startup', $this->Controller);
        $response = $this->Auth->startup($event);

        $this->assertInstanceOf('Cake\Http\Response', $response);
        $expected = Router::url(['controller' => 'AuthTest', 'action' => 'login', '?' => ['redirect' => '/posts/view/1']], true);
        $redirectHeader = $response->getHeaderLine('Location');
        $this->assertEquals($expected, $redirectHeader);
    }

    /**
     * testLoginRedirect method with non GET
     *
     * @return void
     */
    public function testLoginRedirectPost()
    {
        $this->Auth->session->delete('Auth');
        $this->Controller->request = new ServerRequest([
            'environment' => [
                'HTTP_REFERER' => Router::url('/foo/bar', true),
                'REQUEST_METHOD' => 'POST'
            ],
            'params' => ['controller' => 'Posts', 'action' => 'view', 'pass' => [1]],
            'url' => '/posts/view/1?print=true&refer=menu',
            'session' => $this->Auth->session
        ]);
        $this->Auth->setConfig('loginAction', ['controller' => 'AuthTest', 'action' => 'login']);
        $event = new Event('Controller.startup', $this->Controller);
        $response = $this->Auth->startup($event);

        $this->assertInstanceOf('Cake\Http\Response', $response);
        $expected = Router::url(['controller' => 'AuthTest', 'action' => 'login', '?' => ['redirect' => '/foo/bar']], true);
        $redirectHeader = $response->getHeaderLine('Location');
        $this->assertEquals($expected, $redirectHeader);
    }

    /**
     * testLoginRedirect method with non GET and no referrer
     *
     * @return void
     */
    public function testLoginRedirectPostNoReferer()
    {
        $this->Auth->session->delete('Auth');
        $this->Controller->request = new ServerRequest([
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'params' => ['controller' => 'Posts', 'action' => 'view', 'pass' => [1]],
            'url' => '/posts/view/1?print=true&refer=menu',
            'session' => $this->Auth->session
        ]);
        $this->Auth->setConfig('loginAction', ['controller' => 'AuthTest', 'action' => 'login']);
        $event = new Event('Controller.startup', $this->Controller);
        $response = $this->Auth->startup($event);

        $this->assertInstanceOf('Cake\Http\Response', $response);
        $expected = Router::url(['controller' => 'AuthTest', 'action' => 'login'], true);
        $redirectHeader = $response->getHeaderLine('Location');
        $this->assertEquals($expected, $redirectHeader);
    }

    /**
     * @return void
     */
    public function testLoginRedirectQueryString()
    {
        // QueryString parameters are preserved when redirecting with redirect key
        $this->Auth->session->delete('Auth');
        $this->Controller->request = new ServerRequest([
            'environment' => ['REQUEST_METHOD' => 'GET'],
            'params' => ['controller' => 'Posts', 'action' => 'view', 'pass' => [29]],
            'url' => '/posts/view/29?print=true&refer=menu',
            'session' => $this->Auth->session
        ]);

        $this->Auth->setConfig('loginAction', ['controller' => 'AuthTest', 'action' => 'login']);
        $event = new Event('Controller.startup', $this->Controller);
        $response = $this->Auth->startup($event);

        $expected = Router::url([
            'controller' => 'AuthTest',
            'action' => 'login',
            '?' => ['redirect' => '/posts/view/29?print=true&refer=menu']
        ], true);
        $redirectHeader = $response->getHeaderLine('Location');
        $this->assertEquals($expected, $redirectHeader);
    }

    /**
     * @return void
     */
    public function testLoginRedirectQueryStringWithComplexLoginActionUrl()
    {
        $this->Auth->session->delete('Auth');
        $this->Controller->request = new ServerRequest([
            'environment' => ['REQUEST_METHOD' => 'GET'],
            'params' => ['controller' => 'Posts', 'action' => 'view', 'pass' => [29]],
            'url' => '/posts/view/29?print=true&refer=menu',
            'session' => $this->Auth->session
        ]);

        $this->Auth->session->delete('Auth');
        $this->Auth->setConfig('loginAction', '/auth_test/login/passed-param?a=b');
        $event = new Event('Controller.startup', $this->Controller);
        $response = $this->Auth->startup($event);

        $redirectHeader = $response->getHeaderLine('Location');
        $expected = Router::url([
            'controller' => 'AuthTest',
            'action' => 'login',
            'passed-param',
            '?' => ['a' => 'b', 'redirect' => '/posts/view/29?print=true&refer=menu']
        ], true);
        $this->assertEquals($expected, $redirectHeader);
    }

    /**
     * @return void
     */
    public function testLoginRedirectDifferentBaseUrl()
    {
        $appConfig = Configure::read('App');

        Configure::write('App', [
            'dir' => APP_DIR,
            'webroot' => 'webroot',
            'base' => false,
            'baseUrl' => '/cake/index.php'
        ]);

        $this->Auth->session->delete('Auth');

        $request = new ServerRequest([
            'url' => '/posts/add',
            'params' => [
                'plugin' => null,
                'controller' => 'Posts',
                'action' => 'add'
            ],
            'environment' => [
                'REQUEST_METHOD' => 'GET'
            ],
            'session' => $this->Auth->session,
            'base' => '',
            'webroot' => '/'
        ]);
        $this->Controller->request = $request;

        $this->Auth->setConfig('loginAction', ['controller' => 'Users', 'action' => 'login']);
        $event = new Event('Controller.startup', $this->Controller);
        $response = $this->Auth->startup($event);

        $expected = Router::url(['controller' => 'Users', 'action' => 'login', '?' => ['redirect' => '/posts/add']], true);
        $redirectHeader = $response->getHeaderLine('Location');
        $this->assertEquals($expected, $redirectHeader);

        $this->Auth->session->delete('Auth');
        Configure::write('App', $appConfig);
    }

    /**
     * testNoLoginRedirectForAuthenticatedUser method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testNoLoginRedirectForAuthenticatedUser()
    {
        $request = new ServerRequest([
            'params' => [
                'plugin' => null,
                'controller' => 'auth_test',
                'action' => 'login'
            ],
            'url' => '/auth_test/login',
            'session' => $this->Auth->session
        ]);
        $this->Controller->request = $request;

        $this->Auth->session->write('Auth.User.id', '1');
        $this->Auth->setConfig('authenticate', ['Form']);
        $this->getMockBuilder(BaseAuthorize::class)
            ->setMethods(['authorize'])
            ->disableOriginalConstructor()
            ->setMockClassName('NoLoginRedirectMockAuthorize')
            ->getMock();
        $this->Auth->setConfig('authorize', ['NoLoginRedirectMockAuthorize']);
        $this->Auth->setConfig('loginAction', ['controller' => 'auth_test', 'action' => 'login']);

        $event = new Event('Controller.startup', $this->Controller);
        $return = $this->Auth->startup($event);
        $this->assertNull($return);
        $this->assertNull($this->Controller->testUrl);
    }

    /**
     * testNoLoginRedirectForAuthenticatedUser method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testStartupLoginActionIgnoreQueryString()
    {
        $request = new ServerRequest([
            'params' => [
                'plugin' => null,
                'controller' => 'auth_test',
                'action' => 'login'
            ],
            'query' => ['redirect' => '/admin/articles'],
            'url' => '/auth_test/login?redirect=%2Fadmin%2Farticles',
            'session' => $this->Auth->session
        ]);
        $this->Controller->request = $request;

        $this->Auth->session->clear();
        $this->Auth->setConfig('authenticate', ['Form']);
        $this->Auth->setConfig('authorize', false);
        $this->Auth->setConfig('loginAction', ['controller' => 'auth_test', 'action' => 'login']);

        $event = new Event('Controller.startup', $this->Controller);
        $return = $this->Auth->startup($event);
        $this->assertNull($return);
    }

    /**
     * Default to loginRedirect, if set, on authError.
     *
     * @return void
     * @triggers Controller.startup $Controller
     */
    public function testDefaultToLoginRedirect()
    {
        $url = '/party/on';
        $this->Controller->request = $request = new ServerRequest([
            'url' => $url,
            'environment' => [
                'HTTP_REFERER' => false,
            ],
            'params' => [
                'plugin' => null,
                'controller' => 'Part',
                'action' => 'on'
            ],
            'base' => 'dirname',
            'webroot' => '/dirname/'
        ]);
        Router::pushRequest($request);

        $this->Auth->setConfig('authorize', ['Controller']);
        $this->Auth->setUser(['username' => 'mariano', 'password' => 'cake']);
        $this->Auth->setConfig('loginRedirect', [
            'controller' => 'something',
            'action' => 'else'
        ]);

        $response = new Response();
        $Controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->setMethods(['on', 'redirect'])
            ->setConstructorArgs([$request, $response])
            ->getMock();
        $event = new Event('Controller.startup', $Controller);

        // Should not contain basedir when redirect is called.
        $expected = '/something/else';
        $Controller->expects($this->once())
            ->method('redirect')
            ->with($this->equalTo($expected));
        $this->Auth->startup($event);
    }

    /**
     * testRedirectToUnauthorizedRedirect
     *
     * @return void
     * @triggers Controller.startup $Controller
     */
    public function testRedirectToUnauthorizedRedirect()
    {
        $url = '/party/on';
        $this->Auth->Flash = $this->getMockBuilder('Cake\Controller\Component\FlashComponent')
            ->setMethods(['set'])
            ->setConstructorArgs([$this->Controller->components()])
            ->getMock();
        $request = new ServerRequest([
            'url' => $url,
            'session' => $this->Auth->session,
            'params' => ['controller' => 'Party', 'action' => 'on']
        ]);
        $this->Auth->setConfig('authorize', ['Controller']);
        $this->Auth->setUser(['username' => 'admad', 'password' => 'cake']);

        $expected = ['controller' => 'no_can_do', 'action' => 'jack'];
        $this->Auth->setConfig('unauthorizedRedirect', $expected);

        $response = new Response();
        $controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->setMethods(['on', 'redirect'])
            ->setConstructorArgs([$request, $response])
            ->getMock();

        $controller->expects($this->once())
            ->method('redirect')
            ->with($this->equalTo($expected));

        $this->Auth->Flash->expects($this->once())
            ->method('set');

        $event = new Event('Controller.startup', $controller);
        $this->Auth->startup($event);
    }

    /**
     * test unauthorized redirect defaults to loginRedirect
     * which is a string URL.
     *
     * @return void
     */
    public function testRedirectToUnauthorizedRedirectLoginAction()
    {
        $url = '/party/on';
        $this->Auth->Flash = $this->getMockBuilder('Cake\Controller\Component\FlashComponent')
            ->setMethods(['set'])
            ->setConstructorArgs([$this->Controller->components()])
            ->getMock();
        $request = new ServerRequest([
            'url' => $url,
            'session' => $this->Auth->session,
            'params' => ['controller' => 'Party', 'action' => 'on']
        ]);
        $this->Auth->setConfig('authorize', ['Controller']);
        $this->Auth->setUser(['username' => 'admad', 'password' => 'cake']);

        $this->Auth->setConfig('unauthorizedRedirect', true);
        $this->Auth->setConfig('loginAction', '/users/login');

        $response = new Response();
        $controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->setMethods(['on', 'redirect'])
            ->setConstructorArgs([$request, $response])
            ->getMock();

        // Uses referrer instead of loginAction.
        $controller->expects($this->once())
            ->method('redirect')
            ->with($this->equalTo('/'));

        $event = new Event('Controller.startup', $controller);
        $this->Auth->startup($event);
    }

    /**
     * testRedirectToUnauthorizedRedirectSuppressedAuthError
     *
     * @return void
     * @triggers Controller.startup $Controller
     */
    public function testRedirectToUnauthorizedRedirectSuppressedAuthError()
    {
        $url = '/party/on';
        $this->Auth->session = $this->getMockBuilder(Session::class)
            ->setMethods(['flash'])
            ->getMock();
        $request = new ServerRequest([
            'url' => $url,
            'params' => ['controller' => 'Party', 'action' => 'on']
        ]);
        $this->Auth->setConfig('authorize', ['Controller']);
        $this->Auth->setUser(['username' => 'admad', 'password' => 'cake']);
        $expected = ['controller' => 'no_can_do', 'action' => 'jack'];
        $this->Auth->setConfig('unauthorizedRedirect', $expected);
        $this->Auth->setConfig('authError', false);

        $response = new Response();
        $controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->setMethods(['on', 'redirect'])
            ->setConstructorArgs([$request, $response])
            ->getMock();

        $controller->expects($this->once())
            ->method('redirect')
            ->with($this->equalTo($expected));

        $this->Auth->session->expects($this->never())
            ->method('flash');

        $event = new Event('Controller.startup', $controller);
        $this->Auth->startup($event);
    }

    /**
     * Throw ForbiddenException if config `unauthorizedRedirect` is set to false
     *
     * @return void
     * @triggers Controller.startup $Controller
     */
    public function testForbiddenException()
    {
        $this->expectException(\Cake\Http\Exception\ForbiddenException::class);
        $this->Auth->setConfig([
            'authorize' => ['Controller'],
            'unauthorizedRedirect' => false
        ]);
        $this->Auth->setUser(['username' => 'baker', 'password' => 'cake']);

        $request = $this->request
            ->withAttribute('params', ['controller' => 'Party', 'action' => 'on'])
            ->withRequestTarget('/party/on');
        $response = new Response();
        $Controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->setMethods(['on', 'redirect'])
            ->setConstructorArgs([$request, $response])
            ->getMock();

        $event = new Event('Controller.startup', $Controller);
        $this->Auth->startup($event);
    }

    /**
     * Test that no redirects or authorization tests occur on the loginAction
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testNoRedirectOnLoginAction()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->setMethods(['redirect'])
            ->getMock();
        $controller->methods = ['login'];

        $url = '/AuthTest/login';
        $this->Controller->request = $this->request
            ->withAttribute('params', ['controller' => 'AuthTest', 'action' => 'login'])
            ->withRequestTarget($url);

        $this->Auth->setConfig([
            'loginAction', ['controller' => 'AuthTest', 'action' => 'login'],
            'authorize', ['Controller']
        ]);

        $controller->expects($this->never())
            ->method('redirect');

        $this->Auth->startup($event);
    }

    /**
     * Ensure that no redirect is performed when a 404 is reached
     * And the user doesn't have a session.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testNoRedirectOn404()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Auth->session->delete('Auth');
        $this->Controller->request = $this->request->withAttribute(
            'params',
            ['controller' => 'AuthTest', 'action' => 'something_totally_wrong']
        );
        $result = $this->Auth->startup($event);
        $this->assertNull($result, 'Auth redirected a missing action %s');
    }

    /**
     * testAdminRoute method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testAdminRoute()
    {
        $event = new Event('Controller.startup', $this->Controller);
        Router::reload();
        Router::prefix('admin', function ($routes) {
            $routes->fallbacks(InflectedRoute::class);
        });
        Router::scope('/', function ($routes) {
            $routes->fallbacks(InflectedRoute::class);
        });
        $this->Controller->request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => 'GET',
            ],
            'params' => [
                'controller' => 'AuthTest',
                'action' => 'add',
                'plugin' => null,
                'prefix' => 'admin'
            ],
            'url' => '/admin/auth_test/add',
            'session' => $this->Auth->session
        ]);

        Router::setRequestInfo($this->Controller->request);

        $this->Auth->setConfig('loginAction', [
            'prefix' => 'admin',
            'controller' => 'auth_test',
            'action' => 'login'
        ]);

        $response = $this->Auth->startup($event);
        $redirectHeader = $response->getHeaderLine('Location');
        $expected = Router::url([
            'prefix' => 'admin',
            'controller' => 'auth_test',
            'action' => 'login',
            '?' => ['redirect' => '/admin/auth_test/add']
        ], true);
        $this->assertEquals($expected, $redirectHeader);
    }

    /**
     * testAjaxLogin method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testAjaxLogin()
    {
        $this->Controller->request = new ServerRequest([
            'url' => '/ajax_auth/add',
            'environment' => ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
        ]);
        $this->Controller->request = $this->Controller->request->withParam('action', 'add');

        $event = new Event('Controller.startup', $this->Controller);
        $this->Auth->setConfig('ajaxLogin', 'test_element');
        $this->Auth->RequestHandler->ajaxLayout = 'ajax2';

        $response = $this->Auth->startup($event);

        $this->assertTrue($event->isStopped());
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals(
            "Ajax!\nthis is the test element",
            str_replace("\r\n", "\n", $response->getBody())
        );
    }

    /**
     * test ajax unauthenticated
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testAjaxUnauthenticated()
    {
        $this->Controller->request = new ServerRequest([
            'url' => '/ajax_auth/add',
            'environment' => ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
        ]);
        $this->Controller->request = $this->Controller->request->withParam('action', 'add');

        $event = new Event('Controller.startup', $this->Controller);
        $response = $this->Auth->startup($event);

        $this->assertTrue($event->isStopped());
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($response->hasHeader('Location'));
    }

    /**
     * testLoginActionRedirect method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testLoginActionRedirect()
    {
        $event = new Event('Controller.startup', $this->Controller);
        Router::reload();
        Router::prefix('admin', function ($routes) {
            $routes->fallbacks(InflectedRoute::class);
        });
        Router::scope('/', function ($routes) {
            $routes->fallbacks(InflectedRoute::class);
        });

        $url = '/admin/auth_test/login';
        $request = new ServerRequest([
            'params' => [
                'plugin' => null,
                'controller' => 'auth_test',
                'action' => 'login',
                'prefix' => 'admin',
                'pass' => [],
            ],
            'webroot' => '/',
            'url' => $url
        ]);
        Router::setRequestInfo($request);

        $this->Auth->setConfig('loginAction', [
            'prefix' => 'admin',
            'controller' => 'auth_test',
            'action' => 'login'
        ]);
        $result = $this->Auth->startup($event);

        $this->assertNull($result);
    }

    /**
     * Stateless auth methods like Basic should populate data that can be
     * accessed by $this->user().
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testStatelessAuthWorksWithUser()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => 'POST',
                'PHP_AUTH_USER' => 'mariano',
                'PHP_AUTH_PW' => 'cake',
            ],
            'params' => ['controller' => 'AuthTest', 'action' => 'add'],
            'url' => '/auth_test/add',
            'session' => $this->Auth->session
        ]);

        $this->Auth->setConfig('authenticate', [
            'Basic' => ['userModel' => 'AuthUsers']
        ]);
        $this->Auth->setConfig('storage', 'Memory');
        $this->Auth->startup($event);

        $result = $this->Auth->user();
        $this->assertEquals('mariano', $result['username']);

        $this->assertInstanceOf(
            'Cake\Auth\BasicAuthenticate',
            $this->Auth->authenticationProvider()
        );

        $result = $this->Auth->user('username');
        $this->assertEquals('mariano', $result);
        $this->assertFalse(isset($_SESSION['Auth']), 'No user data in session');
    }

    /**
     * test $settings in Controller::$components
     *
     * @return void
     */
    public function testComponentSettings()
    {
        $this->Auth->setConfig([
            'loginAction' => ['controller' => 'people', 'action' => 'login'],
            'logoutRedirect' => ['controller' => 'people', 'action' => 'login'],
        ]);

        $expected = [
            'loginAction' => ['controller' => 'people', 'action' => 'login'],
            'logoutRedirect' => ['controller' => 'people', 'action' => 'login'],
        ];
        $this->assertEquals(
            $expected['loginAction'],
            $this->Auth->getConfig('loginAction')
        );
        $this->assertEquals(
            $expected['logoutRedirect'],
            $this->Auth->getConfig('logoutRedirect')
        );
    }

    /**
     * test that logout deletes the session variables. and returns the correct URL
     *
     * @return void
     */
    public function testLogout()
    {
        $this->Auth->session->write('Auth.User.id', '1');
        $this->Auth->setConfig('logoutRedirect', '/');
        $result = $this->Auth->logout();

        $this->assertEquals('/', $result);
        $this->assertNull($this->Auth->session->read('Auth.AuthUsers'));
    }

    /**
     * Test that Auth.afterIdentify and Auth.logout events are triggered
     *
     * @return void
     */
    public function testEventTriggering()
    {
        $this->Auth->setConfig('authenticate', [
            'Test' => ['className' => 'TestApp\Auth\TestAuthenticate']
        ]);

        $user = $this->Auth->identify();
        $this->Auth->logout();
        $authObject = $this->Auth->authenticationProvider();

        $expected = ['afterIdentify', 'logout'];
        $this->assertEquals($expected, $authObject->callStack);
        $expected = ['id' => 1, 'username' => 'admad'];
        $this->assertEquals($expected, $user);
        $this->assertInstanceOf(
            'TestApp\Auth\TestAuthenticate',
            $authObject->authenticationProvider
        );

        // Callback for Auth.afterIdentify returns a value
        $authObject->modifiedUser = true;
        $user = $this->Auth->identify();
        $expected = ['id' => 1, 'username' => 'admad', 'extra' => 'foo'];
        $this->assertEquals($expected, $user);
    }

    /**
     * testAfterIdentifyForStatelessAuthentication
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testAfterIdentifyForStatelessAuthentication()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $url = '/auth_test/add';
        $this->Controller->request = $this->Controller->request
            ->withParam('controller', 'AuthTest')
            ->withParam('action', 'add')
            ->withEnv('PHP_AUTH_USER', 'mariano')
            ->withEnv('PHP_AUTH_PW', 'cake');

        $this->Auth->setConfig('authenticate', [
            'Basic' => ['userModel' => 'AuthUsers']
        ]);
        $this->Auth->setConfig('storage', 'Memory');

        EventManager::instance()->on('Auth.afterIdentify', function (Event $event) {
            $user = $event->getData(0);
            $user['from_callback'] = true;

            return $user;
        });

        $this->Auth->startup($event);
        $this->assertEquals('mariano', $this->Auth->user('username'));
        $this->assertTrue($this->Auth->user('from_callback'));
    }

    /**
     * test setting user info to session.
     *
     * @return void
     */
    public function testSetUser()
    {
        $storage = $this->getMockBuilder('Cake\Auth\Storage\SessionStorage')
            ->setMethods(['write'])
            ->setConstructorArgs([$this->Controller->request, $this->Auth->response])
            ->getMock();
        $this->Auth->storage($storage);

        $user = ['username' => 'mark', 'role' => 'admin'];

        $storage->expects($this->once())
            ->method('write')
            ->with($user);

        $this->Auth->setUser($user);
    }

    /**
     * testGettingUserAfterSetUser
     *
     * @return void
     */
    public function testGettingUserAfterSetUser()
    {
        $this->assertFalse((bool)$this->Auth->user());

        $user = [
            'username' => 'mariano',
            'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO',
            'created' => new \DateTime('2007-03-17 01:16:23'),
            'updated' => new \DateTime('2007-03-17 01:18:31')
        ];
        $this->Auth->setUser($user);
        $this->assertTrue((bool)$this->Auth->user());
        $this->assertEquals($user['username'], $this->Auth->user('username'));
    }

    /**
     * test flash settings.
     *
     * @return void
     * @triggers Controller.startup $this->Controller)
     */
    public function testFlashSettings()
    {
        $this->Auth->Flash = $this->getMockBuilder('Cake\Controller\Component\FlashComponent')
            ->setConstructorArgs([$this->Controller->components()])
            ->getMock();
        $this->Controller->request = $this->Controller->request->withParam('action', 'add');
        $this->Auth->startup(new Event('Controller.startup', $this->Controller));

        $this->Auth->Flash->expects($this->at(0))
            ->method('set')
            ->with(
                'Auth failure',
                [
                    'key' => 'auth-key',
                    'element' => 'error',
                    'params' => ['class' => 'error']
                ]
            );

        $this->Auth->Flash->expects($this->at(1))
            ->method('set')
            ->with('Auth failure', ['key' => 'auth-key', 'element' => 'custom']);

        $this->Auth->setConfig('flash', [
            'key' => 'auth-key'
        ]);
        $this->Auth->flash('Auth failure');

        $this->Auth->setConfig('flash', [
            'key' => 'auth-key',
            'element' => 'custom'
        ], false);
        $this->Auth->flash('Auth failure');
    }

    /**
     * test the various states of Auth::redirect()
     *
     * @return void
     */
    public function testRedirectSet()
    {
        $value = ['controller' => 'users', 'action' => 'home'];
        $result = $this->Auth->redirectUrl($value);
        $this->assertEquals('/users/home', $result);
    }

    /**
     * Tests redirect using redirect key from the query string.
     *
     * @return void
     */
    public function testRedirectQueryStringRead()
    {
        $this->Auth->setConfig('loginAction', ['controller' => 'users', 'action' => 'login']);
        $this->Controller->request = $this->Controller->request->withQueryParams(['redirect' => '/users/custom']);

        $result = $this->Auth->redirectUrl();
        $this->assertEquals('/users/custom', $result);
    }

    /**
     * Tests redirectUrl with duplicate base.
     *
     * @return void
     */
    public function testRedirectQueryStringReadDuplicateBase()
    {
        $this->Controller->request = $this->Controller->request
            ->withAttribute('webroot', '/waves/')
            ->withAttribute('base', '/waves')
            ->withQueryParams(['redirect' => '/waves/add']);

        Router::setRequestInfo($this->Controller->request);

        $result = $this->Auth->redirectUrl();
        $this->assertEquals('/waves/add', $result);
    }

    /**
     * test that redirect does not return loginAction if that is what's passed as redirect.
     * instead loginRedirect should be used.
     *
     * @return void
     */
    public function testRedirectQueryStringReadEqualToLoginAction()
    {
        $this->Auth->setConfig([
            'loginAction' => ['controller' => 'users', 'action' => 'login'],
            'loginRedirect' => ['controller' => 'users', 'action' => 'home']
        ]);
        $this->Controller->request = $this->Controller->request->withQueryParams(['redirect' => '/users/login']);

        $result = $this->Auth->redirectUrl();
        $this->assertEquals('/users/home', $result);
    }

    /**
     * Tests that redirect does not return loginAction if that contains a host,
     * instead loginRedirect should be used.
     *
     * @return void
     */
    public function testRedirectQueryStringInvalid()
    {
        $this->Auth->setConfig([
            'loginAction' => ['controller' => 'users', 'action' => 'login'],
            'loginRedirect' => ['controller' => 'users', 'action' => 'home']
        ]);
        $this->Controller->request = $this->Controller->request->withQueryParams(['redirect' => 'http://some.domain.example/users/login']);

        $result = $this->Auth->redirectUrl();
        $this->assertEquals('/users/home', $result);

        $this->Controller->request = $this->Controller->request->withQueryParams(['redirect' => '//some.domain.example/users/login']);

        $result = $this->Auth->redirectUrl();
        $this->assertEquals('/users/home', $result);
    }

    /**
     * test that the returned URL doesn't contain the base URL.
     *
     * @return void This test method doesn't return anything.
     */
    public function testRedirectUrlWithBaseSet()
    {
        $App = Configure::read('App');

        Configure::write('App', [
            'dir' => APP_DIR,
            'webroot' => 'webroot',
            'base' => false,
            'baseUrl' => '/cake/index.php'
        ]);

        $url = '/users/login';
        $this->Controller->request = $this->Controller->request = new ServerRequest([
            'url' => $url,
            'params' => ['plugin' => null, 'controller' => 'Users', 'action' => 'login']
        ]);
        Router::setRequestInfo($this->Controller->request);

        $this->Auth->setConfig('loginAction', ['controller' => 'users', 'action' => 'login']);
        $this->Auth->setConfig('loginRedirect', ['controller' => 'users', 'action' => 'home']);

        $result = $this->Auth->redirectUrl();
        $this->assertEquals('/users/home', $result);

        Configure::write('App', $App);
        Router::reload();
    }

    /**
     * testUser method
     *
     * @return void
     */
    public function testUser()
    {
        $data = [
            'User' => [
                'id' => '2',
                'username' => 'mark',
                'group_id' => 1,
                'Group' => [
                    'id' => '1',
                    'name' => 'Members'
                ],
                'is_admin' => false,
            ]];
            $this->Auth->session->write('Auth', $data);

            $result = $this->Auth->user();
            $this->assertEquals($data['User'], $result);

            $result = $this->Auth->user('username');
            $this->assertEquals($data['User']['username'], $result);

            $result = $this->Auth->user('Group.name');
            $this->assertEquals($data['User']['Group']['name'], $result);

            $result = $this->Auth->user('invalid');
            $this->assertNull($result);

            $result = $this->Auth->user('Company.invalid');
            $this->assertNull($result);

            $result = $this->Auth->user('is_admin');
            $this->assertFalse($result);
    }

    /**
     * testStatelessAuthNoRedirect method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testStatelessAuthNoRedirect()
    {
        $this->expectException(\Cake\Http\Exception\UnauthorizedException::class);
        $this->expectExceptionCode(401);
        $event = new Event('Controller.startup', $this->Controller);
        $_SESSION = [];

        $this->Auth->setConfig('authenticate', ['Basic']);
        $this->Controller->request = $this->Controller->request->withParam('action', 'add');

        $result = $this->Auth->startup($event);
    }

    /**
     * testStatelessAuthRedirect method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testStatelessAuthRedirectToLogin()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Auth->authenticate = ['Basic', 'Form'];
        $this->Controller->request = $this->Controller->request->withParam('action', 'add');

        $response = $this->Auth->startup($event);
        $this->assertInstanceOf(Response::class, $response);

        $this->assertEquals(
            'http://localhost/users/login?redirect=%2Fauth_test',
            $response->getHeaderLine('Location')
        );
    }

    /**
     * test for BC getting/setting AuthComponent::$sessionKey gets/sets `key`
     * config of session storage.
     *
     * @return void
     */
    public function testSessionKeyBC()
    {
        $this->assertEquals('Auth.User', $this->Auth->sessionKey);

        $this->Auth->sessionKey = 'Auth.Member';
        $this->assertEquals('Auth.Member', $this->Auth->sessionKey);
        $this->assertEquals('Auth.Member', $this->Auth->storage()->getConfig('key'));

        $this->Auth->sessionKey = false;
        $this->assertInstanceOf('Cake\Auth\Storage\MemoryStorage', $this->Auth->storage());
    }

    /**
     * Test that setting config 'earlyAuth' to true make AuthComponent do the initial
     * checks in beforeFilter() instead of startup().
     *
     * @return void
     */
    public function testCheckAuthInConfig()
    {
        $this->Controller->components()->set('Auth', $this->Auth);
        $this->Auth->earlyAuthTest = true;

        $this->Auth->authCheckCalledFrom = null;
        $this->Controller->startupProcess();
        $this->assertEquals('Controller.startup', $this->Auth->authCheckCalledFrom);

        $this->Auth->authCheckCalledFrom = null;
        $this->Auth->setConfig('checkAuthIn', 'Controller.initialize');
        $this->Controller->startupProcess();
        $this->assertEquals('Controller.initialize', $this->Auth->authCheckCalledFrom);
    }
}
