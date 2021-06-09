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
namespace Cake\Test\TestCase\Controller\Component;

use Cake\Auth\BaseAuthorize;
use Cake\Auth\FormAuthenticate;
use Cake\Controller\Component\AuthComponent;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\Event\EventManager;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Http\Session;
use Cake\Routing\Route\InflectedRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
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
     * @var \TestApp\Controller\AuthTestController
     */
    protected $Controller;

    /**
     * AuthComponent property
     *
     * @var \TestApp\Controller\Component\TestAuthComponent
     */
    protected $Auth;

    /**
     * fixtures property
     *
     * @var array
     */
    protected $fixtures = ['core.AuthUsers', 'core.Users'];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        Security::setSalt('YJfIxfs2guVoUubWDYhG93b0qyJfIxfs2guwvniR2G0FgaC9mi');
        static::setAppNamespace();

        Router::scope('/', function (RouteBuilder $routes): void {
            $routes->fallbacks(InflectedRoute::class);
        });

        $request = new ServerRequest([
            'url' => '/auth_test',
            'environment' => [
                'REQUEST_METHOD' => 'GET',
            ],
            'params' => [
                'plugin' => null,
                'controller' => 'AuthTest',
                'action' => 'index',
            ],
            'session' => new Session(),
            'webroot' => '/',
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
    public function tearDown(): void
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
    public function testNoAuth(): void
    {
        $this->assertFalse($this->Auth->isAuthorized());
    }

    /**
     * testIdentify method
     *
     * @return void
     */
    public function testIdentify(): void
    {
        $AuthLoginFormAuthenticate = $this->getMockBuilder(FormAuthenticate::class)
            ->onlyMethods(['authenticate'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->Auth->authenticate = [
            'AuthLoginForm' => [
                'userModel' => 'AuthUsers',
            ],
        ];

        $this->Auth->setAuthenticateObject(0, $AuthLoginFormAuthenticate);

        $this->Controller->setRequest($this->Controller->getRequest()->withParsedBody([
            'AuthUsers' => [
                'username' => 'mark',
                'password' => Security::hash('cake', null, true),
            ],
        ]));

        $user = [
            'id' => 1,
            'username' => 'mark',
        ];

        $AuthLoginFormAuthenticate->expects($this->once())
            ->method('authenticate')
            ->with($this->Controller->getRequest())
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
    public function testIdentifyArrayAccess(): void
    {
        $AuthLoginFormAuthenticate = $this->getMockBuilder(FormAuthenticate::class)
            ->onlyMethods(['authenticate'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->Auth->authenticate = [
            'AuthLoginForm' => [
                'userModel' => 'AuthUsers',
            ],
        ];

        $this->Auth->setAuthenticateObject(0, $AuthLoginFormAuthenticate);

        $this->Controller->setRequest($this->Controller->getRequest()->withParsedBody([
            'AuthUsers' => [
                'username' => 'mark',
                'password' => Security::hash('cake', null, true),
            ],
        ]));

        $user = new \ArrayObject([
            'id' => 1,
            'username' => 'mark',
        ]);

        $AuthLoginFormAuthenticate->expects($this->once())
            ->method('authenticate')
            ->with($this->Controller->getRequest())
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
    public function testAuthorizeFalse(): void
    {
        $event = new Event('Controller.startup', $this->Controller);
        $Users = $this->getTableLocator()->get('Users');
        $user = $Users->find('all')->enableHydration(false)->first();
        $this->Controller->Auth->storage()->write($user);
        $this->Controller->Auth->setConfig('userModel', 'Users');
        $this->Controller->Auth->setConfig('authorize', false);
        $this->Controller->setRequest($this->request->withAttribute('params', ['controller' => 'AuthTest', 'action' => 'add']));
        $result = $this->Controller->Auth->startup($event);
        $this->assertNull($result);

        $this->Controller->Auth->storage()->delete();
        $result = $this->Controller->Auth->startup($event);
        $this->assertTrue($event->isStopped());
        $this->assertInstanceOf('Cake\Http\Response', $result);
        $this->assertTrue($this->Auth->getController()->getRequest()->getSession()->check('Flash.flash'));

        $this->Controller->setRequest($this->request->withAttribute('params', ['controller' => 'AuthTest', 'action' => 'camelCase']));
        $result = $this->Controller->Auth->startup($event);
        $this->assertInstanceOf('Cake\Http\Response', $result);
    }

    /**
     * testIsAuthorizedMissingFile function
     *
     * @return void
     */
    public function testIsAuthorizedMissingFile(): void
    {
        $this->expectException(\Cake\Core\Exception\CakeException::class);
        $this->Controller->Auth->setConfig('authorize', 'Missing');
        $this->Controller->Auth->isAuthorized(['User' => ['id' => 1]]);
    }

    /**
     * test that isAuthorized calls methods correctly
     *
     * @return void
     */
    public function testIsAuthorizedDelegation(): void
    {
        $AuthMockOneAuthorize = $this->getMockBuilder(BaseAuthorize::class)
            ->onlyMethods(['authorize'])
            ->disableOriginalConstructor()
            ->getMock();
        $AuthMockTwoAuthorize = $this->getMockBuilder(BaseAuthorize::class)
            ->onlyMethods(['authorize'])
            ->disableOriginalConstructor()
            ->getMock();
        $AuthMockThreeAuthorize = $this->getMockBuilder(BaseAuthorize::class)
            ->onlyMethods(['authorize'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->Auth->setAuthorizeObject(0, $AuthMockOneAuthorize);
        $this->Auth->setAuthorizeObject(1, $AuthMockTwoAuthorize);
        $this->Auth->setAuthorizeObject(2, $AuthMockThreeAuthorize);
        $request = $this->Controller->getRequest();

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
    public function testIsAuthorizedWithArrayObject(): void
    {
        $AuthMockOneAuthorize = $this->getMockBuilder(BaseAuthorize::class)
            ->onlyMethods(['authorize'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->Auth->setAuthorizeObject(0, $AuthMockOneAuthorize);
        $request = $this->Controller->getRequest();

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
    public function testIsAuthorizedUsingUserInSession(): void
    {
        $AuthMockFourAuthorize = $this->getMockBuilder(BaseAuthorize::class)
            ->onlyMethods(['authorize'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->Auth->setConfig('authorize', ['AuthMockFour']);
        $this->Auth->setAuthorizeObject(0, $AuthMockFourAuthorize);

        $user = ['user' => 'mark'];
        $this->Auth->getController()->getRequest()->getSession()->write('Auth.User', $user);
        $request = $this->Controller->getRequest();

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
    public function testLoadAuthorizeResets(): void
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
    public function testLoadAuthenticateNoFile(): void
    {
        $this->expectException(\Cake\Core\Exception\CakeException::class);
        $this->Controller->Auth->setConfig('authenticate', 'Missing');
        $this->Controller->Auth->identify(
            $this->Controller->getRequest(),
            $this->Controller->getResponse()
        );
    }

    /**
     * test the * key with authenticate
     *
     * @return void
     */
    public function testAllConfigWithAuthorize(): void
    {
        $this->Controller->Auth->setConfig('authorize', [
            AuthComponent::ALL => ['actionPath' => 'controllers/'],
            'Controller',
        ]);
        $objects = array_values($this->Controller->Auth->constructAuthorize());
        $result = $objects[0];
        $this->assertSame('controllers/', $result->getConfig('actionPath'));
    }

    /**
     * test that loadAuthorize resets the loaded objects each time.
     *
     * @return void
     */
    public function testLoadAuthenticateResets(): void
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
    public function testAllConfigWithAuthenticate(): void
    {
        $this->Controller->Auth->setConfig('authenticate', [
            AuthComponent::ALL => ['userModel' => 'AuthUsers'],
            'Form',
        ]);
        $objects = array_values($this->Controller->Auth->constructAuthenticate());
        $result = $objects[0];
        $this->assertSame('AuthUsers', $result->getConfig('userModel'));
    }

    /**
     * test defining the same Authenticate object but with different password hashers
     *
     * @return void
     */
    public function testSameAuthenticateWithDifferentHashers(): void
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
    public function testAllowDenyAll(): void
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->Auth->allow();
        $this->Controller->Auth->deny(['add', 'camelCase']);

        $this->Controller->setRequest($this->Controller->getRequest()->withParam('action', 'delete'));
        $this->assertNull($this->Controller->Auth->startup($event));

        $this->Controller->setRequest($this->Controller->getRequest()->withParam('action', 'add'));
        $this->assertInstanceOf('Cake\Http\Response', $this->Controller->Auth->startup($event));

        $this->Controller->setRequest($this->Controller->getRequest()->withParam('action', 'camelCase'));
        $this->assertInstanceOf('Cake\Http\Response', $this->Controller->Auth->startup($event));

        $this->Controller->Auth->allow();
        $this->Controller->Auth->deny(['add', 'camelCase']);

        $this->Controller->setRequest($this->Controller->getRequest()->withParam('action', 'delete'));
        $this->assertNull($this->Controller->Auth->startup($event));

        $this->Controller->setRequest($this->Controller->getRequest()->withParam('action', 'camelCase'));
        $this->assertInstanceOf('Cake\Http\Response', $this->Controller->Auth->startup($event));

        $this->Controller->Auth->allow();
        $this->Controller->Auth->deny();

        $this->Controller->setRequest($this->Controller->getRequest()->withParam('action', 'camelCase'));
        $this->assertInstanceOf('Cake\Http\Response', $this->Controller->Auth->startup($event));

        $this->Controller->setRequest($this->Controller->getRequest()->withParam('action', 'add'));
        $this->assertInstanceOf('Cake\Http\Response', $this->Controller->Auth->startup($event));

        $this->Controller->Auth->allow('camelCase');
        $this->Controller->Auth->deny();

        $this->Controller->setRequest($this->Controller->getRequest()->withParam('action', 'camelCase'));
        $this->assertInstanceOf('Cake\Http\Response', $this->Controller->Auth->startup($event));

        $this->Controller->setRequest($this->Controller->getRequest()->withParam('action', 'login'));
        $this->assertInstanceOf('Cake\Http\Response', $this->Controller->Auth->startup($event));

        $this->Controller->Auth->deny();
        $this->Controller->Auth->allow(null);

        $this->Controller->setRequest($this->Controller->getRequest()->withParam('action', 'camelCase'));
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
    public function testDenyWithCamelCaseMethods(): void
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->Auth->allow();
        $this->Controller->Auth->deny(['add', 'camelCase']);

        $url = '/auth_test/camelCase';
        $this->Controller->setRequest($this->request->withAttribute(
            'params',
            ['controller' => 'AuthTest', 'action' => 'camelCase']
        )->withQueryParams(['url' => Router::normalize($url)]));

        $this->assertInstanceOf('Cake\Http\Response', $this->Controller->Auth->startup($event));

        $url = '/auth_test/CamelCase';
        $this->Controller->setRequest($this->request->withAttribute(
            'params',
            ['controller' => 'AuthTest', 'action' => 'camelCase']
        )->withQueryParams(['url' => Router::normalize($url)]));
        $this->assertInstanceOf('Cake\Http\Response', $this->Controller->Auth->startup($event));
    }

    /**
     * test that allow() and allowedActions work with camelCase method names.
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testAllowedActionsWithCamelCaseMethods(): void
    {
        $event = new Event('Controller.startup', $this->Controller);
        $url = '/auth_test/camelCase';
        $this->Controller->setRequest($this->request
            ->withAttribute('params', ['controller' => 'AuthTest', 'action' => 'camelCase'])
            ->withRequestTarget($url));
        $this->Controller->Auth->loginAction = ['controller' => 'AuthTest', 'action' => 'login'];
        $this->Controller->Auth->userModel = 'AuthUsers';
        $this->Controller->Auth->allow();
        $result = $this->Controller->Auth->startup($event);
        $this->assertNull($result, 'startup() should return null, as action is allowed. %s');

        $url = '/auth_test/camelCase';
        $this->Controller->setRequest($this->request
            ->withAttribute('params', ['controller' => 'AuthTest', 'action' => 'camelCase'])
            ->withRequestTarget($url));
        $this->Controller->Auth->loginAction = ['controller' => 'AuthTest', 'action' => 'login'];
        $this->Controller->Auth->userModel = 'AuthUsers';
        $this->Controller->Auth->allowedActions = ['delete', 'camelCase', 'add'];
        $result = $this->Controller->Auth->startup($event);
        $this->assertNull($result, 'startup() should return null, as action is allowed. %s');

        $this->Controller->Auth->allowedActions = ['delete', 'add'];
        $this->assertInstanceOf('Cake\Http\Response', $this->Controller->Auth->startup($event));

        $url = '/auth_test/delete';
        $this->Controller->setRequest($this->request
            ->withAttribute('params', ['controller' => 'AuthTest', 'action' => 'delete'])
            ->withRequestTarget($url));
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
    public function testAllowedActionsSetWithAllowMethod(): void
    {
        $url = '/auth_test/action_name';
        $this->Controller->setRequest($this->request
            ->withAttribute('params', ['controller' => 'AuthTest', 'action' => 'action_name']));
        $this->Controller->Auth->allow(['action_name', 'anotherAction']);
        $this->assertEquals(['action_name', 'anotherAction'], $this->Controller->Auth->allowedActions);
    }

    /**
     * testLoginRedirect method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testLoginRedirect(): void
    {
        $this->Auth->getController()->getRequest()->getSession()->write('Auth', [
            'AuthUsers' => ['id' => '1', 'username' => 'nate'],
        ]);

        $this->Controller->setRequest(new ServerRequest([
            'params' => ['controller' => 'Users', 'action' => 'login'],
            'url' => '/users/login',
            'environment' => ['HTTP_REFERER' => false],
            //'session' => $this->Auth->session
        ]));

        $this->Auth->setConfig('loginRedirect', [
            'controller' => 'Pages',
            'action' => 'display',
            'welcome',
        ]);
        $event = new Event('Controller.startup', $this->Controller);
        $this->Auth->startup($event);
        $expected = Router::normalize($this->Auth->getConfig('loginRedirect'));
        $this->assertSame($expected, $this->Auth->redirectUrl());

        $this->Auth->getController()->getRequest()->getSession()->delete('Auth');

        $this->Auth->getController()->getRequest()->getSession()->write(
            'Auth',
            ['AuthUsers' => ['id' => '1', 'username' => 'nate']]
        );
        $this->Controller->setRequest(new ServerRequest([
            'params' => ['controller' => 'Posts', 'action' => 'view', 'pass' => [1]],
            'url' => '/posts/view/1',
            'environment' => ['HTTP_REFERER' => false, 'REQUEST_METHOD' => 'GET'],
            'session' => $this->Auth->session,
        ]));

        $this->Auth->setConfig('authorize', 'controller');

        $this->Auth->setConfig('loginAction', [
            'controller' => 'AuthTest', 'action' => 'login',
        ]);
        $event = new Event('Controller.startup', $this->Controller);
        $response = $this->Auth->startup($event);
        $expected = Router::url([
            'controller' => 'AuthTest',
            'action' => 'login',
            '?' => ['redirect' => '/posts/view/1'],
        ], true);
        $redirectHeader = $response->getHeaderLine('Location');
        $this->assertSame($expected, $redirectHeader);

        // Auth.redirect gets set when accessing a protected action without being authenticated
        $this->Auth->getController()->getRequest()->getSession()->delete('Auth');

        $this->Controller->setRequest(new ServerRequest([
            'params' => ['controller' => 'Posts', 'action' => 'view', 'pass' => [1]],
            'url' => '/posts/view/1',
            'environment' => ['HTTP_REFERER' => false, 'REQUEST_METHOD' => 'GET'],
            'session' => $this->Auth->session,
        ]));
        $this->Auth->setConfig('loginAction', ['controller' => 'AuthTest', 'action' => 'login']);
        $event = new Event('Controller.startup', $this->Controller);
        $response = $this->Auth->startup($event);

        $this->assertInstanceOf('Cake\Http\Response', $response);
        $expected = Router::url(['controller' => 'AuthTest', 'action' => 'login', '?' => ['redirect' => '/posts/view/1']], true);
        $redirectHeader = $response->getHeaderLine('Location');
        $this->assertSame($expected, $redirectHeader);
    }

    /**
     * testLoginRedirect method with non GET
     *
     * @return void
     */
    public function testLoginRedirectPost(): void
    {
        $this->Auth->getController()->getRequest()->getSession()->delete('Auth');
        $this->Controller->setRequest(new ServerRequest([
            'environment' => [
                'HTTP_REFERER' => Router::url('/foo/bar', true),
                'REQUEST_METHOD' => 'POST',
            ],
            'params' => ['controller' => 'Posts', 'action' => 'view', 'pass' => [1]],
            'url' => '/posts/view/1?print=true&refer=menu',
            'session' => $this->Auth->session,
        ]));
        $this->Auth->setConfig('loginAction', ['controller' => 'AuthTest', 'action' => 'login']);
        $event = new Event('Controller.startup', $this->Controller);
        $response = $this->Auth->startup($event);

        $this->assertInstanceOf('Cake\Http\Response', $response);
        $expected = Router::url(['controller' => 'AuthTest', 'action' => 'login', '?' => ['redirect' => '/foo/bar']], true);
        $redirectHeader = $response->getHeaderLine('Location');
        $this->assertSame($expected, $redirectHeader);
    }

    /**
     * testLoginRedirect method with non GET and no referrer
     *
     * @return void
     */
    public function testLoginRedirectPostNoReferer(): void
    {
        $this->Auth->getController()->getRequest()->getSession()->delete('Auth');
        $this->Controller->setRequest(new ServerRequest([
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'params' => ['controller' => 'Posts', 'action' => 'view', 'pass' => [1]],
            'url' => '/posts/view/1?print=true&refer=menu',
            'session' => $this->Auth->session,
        ]));
        $this->Auth->setConfig('loginAction', ['controller' => 'AuthTest', 'action' => 'login']);
        $event = new Event('Controller.startup', $this->Controller);
        $response = $this->Auth->startup($event);

        $this->assertInstanceOf('Cake\Http\Response', $response);
        $expected = Router::url(['controller' => 'AuthTest', 'action' => 'login'], true);
        $redirectHeader = $response->getHeaderLine('Location');
        $this->assertSame($expected, $redirectHeader);
    }

    public function testLoginRedirectQueryString(): void
    {
        // QueryString parameters are preserved when redirecting with redirect key
        $this->Auth->getController()->getRequest()->getSession()->delete('Auth');
        $this->Controller->setRequest(new ServerRequest([
            'environment' => ['REQUEST_METHOD' => 'GET'],
            'params' => ['controller' => 'Posts', 'action' => 'view', 'pass' => [29]],
            'url' => '/posts/view/29?print=true&refer=menu',
            'session' => $this->Auth->session,
        ]));

        $this->Auth->setConfig('loginAction', ['controller' => 'AuthTest', 'action' => 'login']);
        $event = new Event('Controller.startup', $this->Controller);
        $response = $this->Auth->startup($event);

        $expected = Router::url([
            'controller' => 'AuthTest',
            'action' => 'login',
            '?' => ['redirect' => '/posts/view/29?print=true&refer=menu'],
        ], true);
        $redirectHeader = $response->getHeaderLine('Location');
        $this->assertSame($expected, $redirectHeader);
    }

    public function testLoginRedirectQueryStringWithComplexLoginActionUrl(): void
    {
        $this->Auth->getController()->getRequest()->getSession()->delete('Auth');
        $this->Controller->setRequest(new ServerRequest([
            'environment' => ['REQUEST_METHOD' => 'GET'],
            'params' => ['controller' => 'Posts', 'action' => 'view', 'pass' => [29]],
            'url' => '/posts/view/29?print=true&refer=menu',
            'session' => $this->Auth->session,
        ]));

        $this->Auth->getController()->getRequest()->getSession()->delete('Auth');
        $this->Auth->setConfig('loginAction', '/auth_test/login/passed-param?a=b');
        $event = new Event('Controller.startup', $this->Controller);
        $response = $this->Auth->startup($event);

        $redirectHeader = $response->getHeaderLine('Location');
        $expected = Router::url([
            'controller' => 'AuthTest',
            'action' => 'login',
            'passed-param',
            '?' => ['a' => 'b', 'redirect' => '/posts/view/29?print=true&refer=menu'],
        ], true);
        $this->assertSame($expected, $redirectHeader);
    }

    public function testLoginRedirectDifferentBaseUrl(): void
    {
        $appConfig = Configure::read('App');

        Configure::write('App', [
            'namespace' => 'TestApp',
            'dir' => APP_DIR,
            'webroot' => 'webroot',
            'base' => false,
            'baseUrl' => '/cake/index.php',
            'fullBaseUrl' => 'http://localhost',
        ]);

        $this->Auth->getController()->getRequest()->getSession()->delete('Auth');

        $request = new ServerRequest([
            'url' => '/posts/add',
            'params' => [
                'plugin' => null,
                'controller' => 'Posts',
                'action' => 'add',
            ],
            'environment' => [
                'REQUEST_METHOD' => 'GET',
            ],
            'session' => $this->Auth->session,
            'base' => '',
            'webroot' => '/',
        ]);
        $this->Controller->setRequest($request);

        $this->Auth->setConfig('loginAction', ['controller' => 'Users', 'action' => 'login']);
        $event = new Event('Controller.startup', $this->Controller);
        $response = $this->Auth->startup($event);

        $expected = Router::url(['controller' => 'Users', 'action' => 'login', '?' => ['redirect' => '/posts/add']], true);
        $redirectHeader = $response->getHeaderLine('Location');
        $this->assertSame($expected, $redirectHeader);

        $this->Auth->getController()->getRequest()->getSession()->delete('Auth');
        Configure::write('App', $appConfig);
    }

    /**
     * testNoLoginRedirectForAuthenticatedUser method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testNoLoginRedirectForAuthenticatedUser(): void
    {
        $request = new ServerRequest([
            'params' => [
                'plugin' => null,
                'controller' => 'AuthTest',
                'action' => 'login',
            ],
            'url' => '/auth_test/login',
            'session' => $this->Auth->session,
        ]);
        $this->Controller->setRequest($request);

        $this->Auth->getController()->getRequest()->getSession()->write('Auth.User.id', '1');
        $this->Auth->setConfig('authenticate', ['Form']);
        $this->getMockBuilder(BaseAuthorize::class)
            ->onlyMethods(['authorize'])
            ->disableOriginalConstructor()
            ->setMockClassName('NoLoginRedirectMockAuthorize')
            ->getMock();
        $this->Auth->setConfig('authorize', ['NoLoginRedirectMockAuthorize']);
        $this->Auth->setConfig('loginAction', ['controller' => 'AuthTest', 'action' => 'login']);

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
    public function testStartupLoginActionIgnoreQueryString(): void
    {
        $request = new ServerRequest([
            'params' => [
                'plugin' => null,
                'controller' => 'AuthTest',
                'action' => 'login',
            ],
            'query' => ['redirect' => '/admin/articles'],
            'url' => '/auth_test/login?redirect=%2Fadmin%2Farticles',
            'session' => $this->Auth->session,
        ]);
        $this->Controller->setRequest($request);

        $this->Auth->getController()->getRequest()->getSession()->clear();
        $this->Auth->setConfig('authenticate', ['Form']);
        $this->Auth->setConfig('authorize', false);
        $this->Auth->setConfig('loginAction', ['controller' => 'AuthTest', 'action' => 'login']);

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
    public function testDefaultToLoginRedirect(): void
    {
        $url = '/party/on';
        $request = new ServerRequest([
            'url' => $url,
            'environment' => [
                'HTTP_REFERER' => false,
            ],
            'params' => [
                'plugin' => null,
                'controller' => 'Part',
                'action' => 'on',
            ],
            'base' => 'dirname',
            'webroot' => '/dirname/',
        ]);
        $this->Controller->setRequest($request);
        Router::setRequest($request);

        $this->Auth->setConfig('authorize', ['Controller']);
        $this->Auth->setUser(['username' => 'mariano', 'password' => 'cake']);
        $this->Auth->setConfig('loginRedirect', [
            'controller' => 'Something',
            'action' => 'else',
        ]);

        $response = new Response();
        $Controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->onlyMethods(['redirect'])
            ->addMethods(['on'])
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
    public function testRedirectToUnauthorizedRedirect(): void
    {
        $url = '/party/on';
        $this->Auth->Flash = $this->getMockBuilder('Cake\Controller\Component\FlashComponent')
            ->onlyMethods(['set'])
            ->setConstructorArgs([$this->Controller->components()])
            ->getMock();
        $request = new ServerRequest([
            'url' => $url,
            'session' => $this->Auth->session,
            'params' => ['controller' => 'Party', 'action' => 'on'],
        ]);
        $this->Auth->setConfig('authorize', ['Controller']);
        $this->Auth->setUser(['username' => 'admad', 'password' => 'cake']);

        $expected = ['controller' => 'NoCanDo', 'action' => 'jack'];
        $this->Auth->setConfig('unauthorizedRedirect', $expected);

        $response = new Response();
        $controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->onlyMethods(['redirect'])
            ->addMethods(['on'])
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
    public function testRedirectToUnauthorizedRedirectLoginAction(): void
    {
        $url = '/party/on';
        $this->Auth->Flash = $this->getMockBuilder('Cake\Controller\Component\FlashComponent')
            ->onlyMethods(['set'])
            ->setConstructorArgs([$this->Controller->components()])
            ->getMock();
        $request = new ServerRequest([
            'url' => $url,
            'session' => $this->Auth->session,
            'params' => ['controller' => 'Party', 'action' => 'on'],
        ]);
        $this->Auth->setConfig('authorize', ['Controller']);
        $this->Auth->setUser(['username' => 'admad', 'password' => 'cake']);

        $this->Auth->setConfig('unauthorizedRedirect', true);
        $this->Auth->setConfig('loginAction', '/users/login');

        $response = new Response();
        $controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->onlyMethods(['redirect'])
            ->addMethods(['on'])
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
    public function testRedirectToUnauthorizedRedirectSuppressedAuthError(): void
    {
        $url = '/party/on';
        $session = $this->getMockBuilder(Session::class)
            ->addMethods(['flash'])
            ->getMock();
        $request = new ServerRequest([
            'session' => $session,
            'url' => $url,
            'params' => ['controller' => 'Party', 'action' => 'on'],
        ]);
        $this->Auth->setConfig('authorize', ['Controller']);
        $this->Auth->setUser(['username' => 'admad', 'password' => 'cake']);
        $expected = ['controller' => 'NoCanDo', 'action' => 'jack'];
        $this->Auth->setConfig('unauthorizedRedirect', $expected);
        $this->Auth->setConfig('authError', false);

        $response = new Response();
        $controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->onlyMethods(['redirect'])
            ->addMethods(['on'])
            ->setConstructorArgs([$request, $response])
            ->getMock();

        $controller->expects($this->once())
            ->method('redirect')
            ->with($this->equalTo($expected));

        $session->expects($this->never())
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
    public function testForbiddenException(): void
    {
        $this->expectException(\Cake\Http\Exception\ForbiddenException::class);
        $this->Auth->setConfig([
            'authorize' => ['Controller'],
            'unauthorizedRedirect' => false,
        ]);
        $this->Auth->setUser(['username' => 'baker', 'password' => 'cake']);

        $request = $this->request
            ->withAttribute('params', ['controller' => 'Party', 'action' => 'on'])
            ->withRequestTarget('/party/on');
        $response = new Response();
        $Controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->onlyMethods(['redirect'])
            ->addMethods(['on'])
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
    public function testNoRedirectOnLoginAction(): void
    {
        $event = new Event('Controller.startup', $this->Controller);
        $controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->onlyMethods(['redirect'])
            ->getMock();
        $controller->methods = ['login'];

        $url = '/AuthTest/login';
        $this->Controller->setRequest($this->request
            ->withAttribute('params', ['controller' => 'AuthTest', 'action' => 'login'])
            ->withRequestTarget($url));

        $this->Auth->setConfig([
            'loginAction', ['controller' => 'AuthTest', 'action' => 'login'],
            'authorize', ['Controller'],
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
    public function testNoRedirectOn404(): void
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Auth->getController()->getRequest()->getSession()->delete('Auth');
        $this->Controller->setRequest($this->request->withAttribute(
            'params',
            ['controller' => 'AuthTest', 'action' => 'something_totally_wrong']
        ));
        $result = $this->Auth->startup($event);
        $this->assertNull($result, 'Auth redirected a missing action %s');
    }

    /**
     * testAdminRoute method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testAdminRoute(): void
    {
        $event = new Event('Controller.startup', $this->Controller);
        Router::reload();
        Router::prefix('admin', function (RouteBuilder $routes): void {
            $routes->fallbacks(InflectedRoute::class);
        });
        Router::scope('/', function (RouteBuilder $routes): void {
            $routes->fallbacks(InflectedRoute::class);
        });
        $this->Controller->setRequest(new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => 'GET',
            ],
            'params' => [
                'controller' => 'AuthTest',
                'action' => 'add',
                'plugin' => null,
                'prefix' => 'Admin',
            ],
            'url' => '/admin/auth_test/add',
            'session' => $this->Auth->session,
        ]));

        Router::setRequest($this->Controller->getRequest());

        $this->Auth->setConfig('loginAction', [
            'prefix' => 'Admin',
            'controller' => 'AuthTest',
            'action' => 'login',
        ]);

        $response = $this->Auth->startup($event);
        $redirectHeader = $response->getHeaderLine('Location');
        $expected = Router::url([
            'prefix' => 'Admin',
            'controller' => 'AuthTest',
            'action' => 'login',
            '?' => ['redirect' => '/admin/auth_test/add'],
        ], true);
        $this->assertSame($expected, $redirectHeader);
    }

    /**
     * test AJAX unauthenticated
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testAjaxUnauthenticated(): void
    {
        $this->Controller->setRequest(new ServerRequest([
            'url' => '/ajax_auth/add',
            'environment' => ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
        ]));
        $this->Controller->setRequest($this->Controller->getRequest()->withParam('action', 'add'));

        $event = new Event('Controller.startup', $this->Controller);
        $response = $this->Auth->startup($event);

        $this->assertTrue($event->isStopped());
        $this->assertSame(403, $response->getStatusCode());
        $this->assertFalse($response->hasHeader('Location'));
    }

    /**
     * testLoginActionRedirect method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testLoginActionRedirect(): void
    {
        $event = new Event('Controller.startup', $this->Controller);
        Router::reload();
        Router::prefix('admin', function (RouteBuilder $routes): void {
            $routes->fallbacks(InflectedRoute::class);
        });
        Router::scope('/', function (RouteBuilder $routes): void {
            $routes->fallbacks(InflectedRoute::class);
        });

        $url = '/admin/auth_test/login';
        $request = new ServerRequest([
            'params' => [
                'plugin' => null,
                'controller' => 'AuthTest',
                'action' => 'login',
                'prefix' => 'Admin',
                'pass' => [],
            ],
            'webroot' => '/',
            'url' => $url,
        ]);
        Router::setRequest($request);

        $this->Auth->setConfig('loginAction', [
            'prefix' => 'Admin',
            'controller' => 'AuthTest',
            'action' => 'login',
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
    public function testStatelessAuthWorksWithUser(): void
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Controller->setRequest(new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => 'POST',
                'PHP_AUTH_USER' => 'mariano',
                'PHP_AUTH_PW' => 'cake',
            ],
            'params' => ['controller' => 'AuthTest', 'action' => 'add'],
            'url' => '/auth_test/add',
            'session' => $this->Auth->session,
        ]));

        $this->Auth->setConfig('authenticate', [
            'Basic' => ['userModel' => 'AuthUsers'],
        ]);
        $this->Auth->setConfig('storage', 'Memory');
        $this->Auth->startup($event);

        $result = $this->Auth->user();
        $this->assertSame('mariano', $result['username']);

        $this->assertInstanceOf(
            'Cake\Auth\BasicAuthenticate',
            $this->Auth->authenticationProvider()
        );

        $result = $this->Auth->user('username');
        $this->assertSame('mariano', $result);
        $this->assertFalse(isset($_SESSION['Auth']), 'No user data in session');
    }

    /**
     * test $settings in Controller::$components
     *
     * @return void
     */
    public function testComponentSettings(): void
    {
        $this->Auth->setConfig([
            'loginAction' => ['controller' => 'People', 'action' => 'login'],
            'logoutRedirect' => ['controller' => 'People', 'action' => 'login'],
        ]);

        $expected = [
            'loginAction' => ['controller' => 'People', 'action' => 'login'],
            'logoutRedirect' => ['controller' => 'People', 'action' => 'login'],
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
    public function testLogout(): void
    {
        $this->Auth->getController()->getRequest()->getSession()->write('Auth.User.id', '1');
        $this->Auth->setConfig('logoutRedirect', '/');
        $result = $this->Auth->logout();

        $this->assertSame('/', $result);
        $this->assertNull($this->Auth->getController()->getRequest()->getSession()->read('Auth.AuthUsers'));
    }

    /**
     * Test that Auth.afterIdentify and Auth.logout events are triggered
     *
     * @return void
     */
    public function testEventTriggering(): void
    {
        $this->Auth->setConfig('authenticate', [
            'Test' => ['className' => 'TestApp\Auth\TestAuthenticate'],
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
    public function testAfterIdentifyForStatelessAuthentication(): void
    {
        $event = new Event('Controller.startup', $this->Controller);
        $url = '/auth_test/add';
        $this->Controller->setRequest($this->Controller->getRequest()
            ->withParam('controller', 'AuthTest')
            ->withParam('action', 'add')
            ->withEnv('PHP_AUTH_USER', 'mariano')
            ->withEnv('PHP_AUTH_PW', 'cake'));

        $this->Auth->setConfig('authenticate', [
            'Basic' => ['userModel' => 'AuthUsers'],
        ]);
        $this->Auth->setConfig('storage', 'Memory');

        EventManager::instance()->on('Auth.afterIdentify', function (EventInterface $event) {
            $user = $event->getData('0');
            $user['from_callback'] = true;

            return $user;
        });

        $this->Auth->startup($event);
        $this->assertSame('mariano', $this->Auth->user('username'));
        $this->assertTrue($this->Auth->user('from_callback'));
    }

    /**
     * test setting user info to session.
     *
     * @return void
     */
    public function testSetUser(): void
    {
        $storage = $this->getMockBuilder('Cake\Auth\Storage\SessionStorage')
            ->onlyMethods(['write'])
            ->setConstructorArgs([$this->Controller->getRequest(), $this->Controller->getResponse()])
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
    public function testGettingUserAfterSetUser(): void
    {
        $this->assertFalse((bool)$this->Auth->user());

        $user = [
            'username' => 'mariano',
            'password' => '$2a$10$u05j8FjsvLBNdfhBhc21LOuVMpzpabVXQ9OpC2wO3pSO0q6t7HHMO',
            'created' => new \DateTime('2007-03-17 01:16:23'),
            'updated' => new \DateTime('2007-03-17 01:18:31'),
        ];
        $this->Auth->setUser($user);
        $this->assertTrue((bool)$this->Auth->user());
        $this->assertSame($user['username'], $this->Auth->user('username'));
    }

    /**
     * test flash settings.
     *
     * @return void
     * @triggers Controller.startup $this->Controller)
     */
    public function testFlashSettings(): void
    {
        $this->Auth->Flash = $this->getMockBuilder('Cake\Controller\Component\FlashComponent')
            ->setConstructorArgs([$this->Controller->components()])
            ->getMock();
        $this->Controller->setRequest($this->Controller->getRequest()->withParam('action', 'add'));
        $this->Auth->startup(new Event('Controller.startup', $this->Controller));

        $this->Auth->Flash->expects($this->exactly(2))
            ->method('set')
            ->withConsecutive(
                [
                    'Auth failure',
                    [
                        'key' => 'auth-key',
                        'element' => 'error',
                        'params' => ['class' => 'error'],
                    ],
                ],
                [
                    'Auth failure', ['key' => 'auth-key', 'element' => 'custom'],
                ]
            );

        $this->Auth->setConfig('flash', [
            'key' => 'auth-key',
        ]);
        $this->Auth->flash('Auth failure');

        $this->Auth->setConfig('flash', [
            'key' => 'auth-key',
            'element' => 'custom',
        ], false);
        $this->Auth->flash('Auth failure');
    }

    /**
     * test the various states of Auth::redirect()
     *
     * @return void
     */
    public function testRedirectSet(): void
    {
        $value = ['controller' => 'Users', 'action' => 'home'];
        $result = $this->Auth->redirectUrl($value);
        $this->assertSame('/users/home', $result);
    }

    /**
     * Tests redirect using redirect key from the query string.
     *
     * @return void
     */
    public function testRedirectQueryStringRead(): void
    {
        $this->Auth->setConfig('loginAction', ['controller' => 'Users', 'action' => 'login']);
        $this->Controller->setRequest($this->Controller->getRequest()->withQueryParams(['redirect' => '/users/custom']));

        $result = $this->Auth->redirectUrl();
        $this->assertSame('/users/custom', $result);
    }

    /**
     * Tests redirectUrl with duplicate base.
     *
     * @return void
     */
    public function testRedirectQueryStringReadDuplicateBase(): void
    {
        $this->Controller->setRequest($this->Controller->getRequest()
            ->withAttribute('webroot', '/waves/')
            ->withAttribute('base', '/waves')
            ->withQueryParams(['redirect' => '/waves/add']));

        Router::setRequest($this->Controller->getRequest());

        $result = $this->Auth->redirectUrl();
        $this->assertSame('/waves/add', $result);
    }

    /**
     * test that redirect does not return loginAction if that is what's passed as redirect.
     * instead loginRedirect should be used.
     *
     * @return void
     */
    public function testRedirectQueryStringReadEqualToLoginAction(): void
    {
        $this->Auth->setConfig([
            'loginAction' => ['controller' => 'Users', 'action' => 'login'],
            'loginRedirect' => ['controller' => 'Users', 'action' => 'home'],
        ]);
        $this->Controller->setRequest($this->Controller->getRequest()->withQueryParams(['redirect' => '/users/login']));

        $result = $this->Auth->redirectUrl();
        $this->assertSame('/users/home', $result);
    }

    /**
     * Tests that redirect does not return loginAction if that contains a host,
     * instead loginRedirect should be used.
     *
     * @return void
     */
    public function testRedirectQueryStringInvalid(): void
    {
        $this->Auth->setConfig([
            'loginAction' => ['controller' => 'Users', 'action' => 'login'],
            'loginRedirect' => ['controller' => 'Users', 'action' => 'home'],
        ]);
        $this->Controller->setRequest($this->Controller->getRequest()->withQueryParams(['redirect' => 'http://some.domain.example/users/login']));

        $result = $this->Auth->redirectUrl();
        $this->assertSame('/users/home', $result);

        $this->Controller->setRequest($this->Controller->getRequest()->withQueryParams(['redirect' => '//some.domain.example/users/login']));

        $result = $this->Auth->redirectUrl();
        $this->assertSame('/users/home', $result);
    }

    /**
     * test that the returned URL doesn't contain the base URL.
     *
     * @return void This test method doesn't return anything.
     */
    public function testRedirectUrlWithBaseSet(): void
    {
        $App = Configure::read('App');

        Configure::write('App', [
            'dir' => APP_DIR,
            'webroot' => 'webroot',
            'base' => false,
            'baseUrl' => '/cake/index.php',
        ]);

        $url = '/users/login';
        $this->Controller->setRequest(new ServerRequest([
            'url' => $url,
            'params' => ['plugin' => null, 'controller' => 'Users', 'action' => 'login'],
        ]));
        Router::setRequest($this->Controller->getRequest());

        $this->Auth->setConfig('loginAction', ['controller' => 'Users', 'action' => 'login']);
        $this->Auth->setConfig('loginRedirect', ['controller' => 'Users', 'action' => 'home']);

        $result = $this->Auth->redirectUrl();
        $this->assertSame('/users/home', $result);

        Configure::write('App', $App);
        Router::reload();
    }

    /**
     * testUser method
     *
     * @return void
     */
    public function testUser(): void
    {
        $data = [
            'User' => [
                'id' => '2',
                'username' => 'mark',
                'group_id' => 1,
                'Group' => [
                    'id' => '1',
                    'name' => 'Members',
                ],
                'is_admin' => false,
            ],
        ];
        $this->Auth->getController()->getRequest()->getSession()->write('Auth', $data);

        $result = $this->Auth->user();
        $this->assertEquals($data['User'], $result);

        $result = $this->Auth->user('username');
        $this->assertSame($data['User']['username'], $result);

        $result = $this->Auth->user('Group.name');
        $this->assertSame($data['User']['Group']['name'], $result);

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
    public function testStatelessAuthNoRedirect(): void
    {
        $this->expectException(\Cake\Http\Exception\UnauthorizedException::class);
        $this->expectExceptionCode(401);
        $event = new Event('Controller.startup', $this->Controller);
        $_SESSION = [];

        $this->Auth->setConfig('authenticate', ['Basic']);
        $this->Controller->setRequest($this->Controller->getRequest()->withParam('action', 'add'));

        $result = $this->Auth->startup($event);
    }

    /**
     * testStatelessAuthRedirect method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testStatelessAuthRedirectToLogin(): void
    {
        $event = new Event('Controller.startup', $this->Controller);
        $this->Auth->authenticate = ['Basic', 'Form'];
        $this->Controller->setRequest($this->Controller->getRequest()->withParam('action', 'add'));

        $response = $this->Auth->startup($event);
        $this->assertInstanceOf(Response::class, $response);

        $this->assertSame(
            'http://localhost/users/login?redirect=%2Fauth_test',
            $response->getHeaderLine('Location')
        );
    }

    /**
     * test null action no error
     *
     * @return void
     */
    public function testStartupNullAction(): void
    {
        $request = new ServerRequest([
            'params' => [
                'plugin' => null,
                'controller' => null,
                'action' => null,
            ],
            'url' => '/',
            'session' => $this->Auth->session,
        ]);
        $this->Controller->setRequest($request);

        $this->Auth->getController()->getRequest()->getSession()->clear();
        $this->Auth->setConfig([
            'authenticate' => ['Form'],
            'authorize' => false,
            'loginAction' => ['controller' => 'AuthTest', 'action' => 'login'],
        ]);

        $event = new Event('Controller.startup', $this->Controller);
        $return = $this->Auth->startup($event);
        $this->assertNull($return);
    }

    /**
     * test for BC getting/setting AuthComponent::$sessionKey gets/sets `key`
     * config of session storage.
     *
     * @return void
     */
    public function testSessionKeyBC(): void
    {
        $this->assertSame('Auth.User', $this->Auth->sessionKey);

        $this->Auth->sessionKey = 'Auth.Member';
        $this->assertSame('Auth.Member', $this->Auth->sessionKey);
        $this->assertSame('Auth.Member', $this->Auth->storage()->getConfig('key'));

        $this->Auth->sessionKey = false;
        $this->assertInstanceOf('Cake\Auth\Storage\MemoryStorage', $this->Auth->storage());
    }

    /**
     * Test that setting config 'earlyAuth' to true make AuthComponent do the initial
     * checks in beforeFilter() instead of startup().
     *
     * @return void
     */
    public function testCheckAuthInConfig(): void
    {
        $this->Controller->components()->set('Auth', $this->Auth);
        $this->Auth->earlyAuthTest = true;

        $this->Auth->authCheckCalledFrom = null;
        $this->Controller->startupProcess();
        $this->assertSame('Controller.startup', $this->Auth->authCheckCalledFrom);

        $this->Auth->authCheckCalledFrom = null;
        $this->Auth->setConfig('checkAuthIn', 'Controller.initialize');
        $this->Controller->startupProcess();
        $this->assertSame('Controller.initialize', $this->Auth->authCheckCalledFrom);
    }
}
