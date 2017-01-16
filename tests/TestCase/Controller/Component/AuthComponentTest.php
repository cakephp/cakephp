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
namespace Cake\Test\TestCase\Controller\Component;

use Cake\Auth\BaseAuthorize;
use Cake\Auth\FormAuthenticate;
use Cake\Controller\Component\AuthComponent;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\ORM\TableRegistry;
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
     * name property
     *
     * @var string
     */
    public $name = 'Auth';

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

        Security::salt('YJfIxfs2guVoUubWDYhG93b0qyJfIxfs2guwvniR2G0FgaC9mi');
        Configure::write('App.namespace', 'TestApp');

        Router::scope('/', function ($routes) {
            $routes->fallbacks('InflectedRoute');
        });

        $request = new Request();
        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['stop'])
            ->getMock();

        $this->Controller = new AuthTestController($request, $response);
        $this->Auth = new TestAuthComponent($this->Controller->components());

        $Users = TableRegistry::get('AuthUsers');
        $Users->updateAll(['password' => password_hash('cake', PASSWORD_BCRYPT)], []);
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
     * testIsErrorOrTests
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testIsErrorOrTests()
    {
        $event = new Event('Controller.startup', $this->Controller);

        $this->Controller->name = 'Error';
        $this->assertNull($this->Controller->Auth->startup($event));

        $this->Controller->name = 'Post';
        $this->Controller->request['action'] = 'thisdoesnotexist';
        $this->assertNull($this->Controller->Auth->startup($event));
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

        $this->Auth->request->data = [
            'AuthUsers' => [
                'username' => 'mark',
                'password' => Security::hash('cake', null, true)
            ]
        ];

        $user = [
            'id' => 1,
            'username' => 'mark'
        ];

        $AuthLoginFormAuthenticate->expects($this->once())
            ->method('authenticate')
            ->with($this->Auth->request)
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

        $this->Auth->request->data = [
            'AuthUsers' => [
                'username' => 'mark',
                'password' => Security::hash('cake', null, true)
            ]
        ];

        $user = new \ArrayObject([
            'id' => 1,
            'username' => 'mark'
        ]);

        $AuthLoginFormAuthenticate->expects($this->once())
            ->method('authenticate')
            ->with($this->Auth->request)
            ->will($this->returnValue($user));

        $result = $this->Auth->identify();
        $this->assertEquals($user, $result);
        $this->assertSame($AuthLoginFormAuthenticate, $this->Auth->authenticationProvider());
    }

    /**
     * testRedirectVarClearing method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testRedirectVarClearing()
    {
        $this->Controller->request['controller'] = 'auth_test';
        $this->Controller->request['action'] = 'add';
        $this->Controller->request->here = '/auth_test/add';
        $this->assertNull($this->Auth->session->read('Auth.redirect'));

        $this->Auth->config('authenticate', ['Form']);
        $event = new Event('Controller.startup', $this->Controller);
        $this->Auth->startup($event);
        $this->assertEquals('/auth_test/add', $this->Auth->session->read('Auth.redirect'));

        $this->Auth->storage()->write(['username' => 'admad']);
        $this->Auth->startup($event, $this->Controller);
        $this->assertNull($this->Auth->session->read('Auth.redirect'));
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
        $Users = TableRegistry::get('Users');
        $user = $Users->find('all')->hydrate(false)->first();
        $this->Controller->Auth->storage()->write($user);
        $this->Controller->Auth->config('userModel', 'Users');
        $this->Controller->Auth->config('authorize', false);
        $this->Controller->request->addParams(Router::parse('auth_test/add'));
        $result = $this->Controller->Auth->startup($event);
        $this->assertNull($result);

        $this->Controller->Auth->storage()->delete();
        $result = $this->Controller->Auth->startup($event);
        $this->assertTrue($event->isStopped());
        $this->assertInstanceOf('Cake\Network\Response', $result);
        $this->assertTrue($this->Auth->session->check('Flash.auth'));

        $this->Controller->request->addParams(Router::parse('auth_test/camelCase'));
        $result = $this->Controller->Auth->startup($event);
        $this->assertInstanceOf('Cake\Network\Response', $result);
    }

    /**
     * testIsAuthorizedMissingFile function
     *
     * @expectedException \Cake\Core\Exception\Exception
     * @return void
     */
    public function testIsAuthorizedMissingFile()
    {
        $this->Controller->Auth->config('authorize', 'Missing');
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
        $request = $this->Auth->request;

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
        $request = $this->Auth->request;

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
        $this->Auth->config('authorize', ['AuthMockFour']);
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
        $this->Controller->Auth->config('authorize', ['Controller']);
        $result = $this->Controller->Auth->constructAuthorize();
        $this->assertCount(1, $result);

        $result = $this->Controller->Auth->constructAuthorize();
        $this->assertCount(1, $result);
    }

    /**
     * testLoadAuthenticateNoFile function
     *
     * @expectedException \Cake\Core\Exception\Exception
     * @return void
     */
    public function testLoadAuthenticateNoFile()
    {
        $this->Controller->Auth->config('authenticate', 'Missing');
        $this->Controller->Auth->identify($this->Controller->request, $this->Controller->response);
    }

    /**
     * test the * key with authenticate
     *
     * @return void
     */
    public function testAllConfigWithAuthorize()
    {
        $this->Controller->Auth->config('authorize', [
            AuthComponent::ALL => ['actionPath' => 'controllers/'],
            'Controller',
        ]);
        $objects = array_values($this->Controller->Auth->constructAuthorize());
        $result = $objects[0];
        $this->assertEquals('controllers/', $result->config('actionPath'));
    }

    /**
     * test that loadAuthorize resets the loaded objects each time.
     *
     * @return void
     */
    public function testLoadAuthenticateResets()
    {
        $this->Controller->Auth->config('authenticate', ['Form']);
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
        $this->Controller->Auth->config('authenticate', [
            AuthComponent::ALL => ['userModel' => 'AuthUsers'],
            'Form'
        ]);
        $objects = array_values($this->Controller->Auth->constructAuthenticate());
        $result = $objects[0];
        $this->assertEquals('AuthUsers', $result->config('userModel'));
    }

    /**
     * test defining the same Authenticate object but with different password hashers
     *
     * @return void
     */
    public function testSameAuthenticateWithDifferentHashers()
    {
        $this->Controller->Auth->config('authenticate', [
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

        $this->Controller->request['action'] = 'delete';
        $this->assertNull($this->Controller->Auth->startup($event));

        $this->Controller->request['action'] = 'add';
        $this->assertInstanceOf('Cake\Network\Response', $this->Controller->Auth->startup($event));

        $this->Controller->request['action'] = 'camelCase';
        $this->assertInstanceOf('Cake\Network\Response', $this->Controller->Auth->startup($event));

        $this->Controller->Auth->allow();
        $this->Controller->Auth->deny(['add', 'camelCase']);

        $this->Controller->request['action'] = 'delete';
        $this->assertNull($this->Controller->Auth->startup($event));

        $this->Controller->request['action'] = 'camelCase';
        $this->assertInstanceOf('Cake\Network\Response', $this->Controller->Auth->startup($event));

        $this->Controller->Auth->allow();
        $this->Controller->Auth->deny();

        $this->Controller->request['action'] = 'camelCase';
        $this->assertInstanceOf('Cake\Network\Response', $this->Controller->Auth->startup($event));

        $this->Controller->request['action'] = 'add';
        $this->assertInstanceOf('Cake\Network\Response', $this->Controller->Auth->startup($event));

        $this->Controller->Auth->allow('camelCase');
        $this->Controller->Auth->deny();

        $this->Controller->request['action'] = 'camelCase';
        $this->assertInstanceOf('Cake\Network\Response', $this->Controller->Auth->startup($event));

        $this->Controller->request['action'] = 'login';
        $this->assertInstanceOf('Cake\Network\Response', $this->Controller->Auth->startup($event));

        $this->Controller->Auth->deny();
        $this->Controller->Auth->allow(null);

        $this->Controller->request['action'] = 'camelCase';
        $this->assertNull($this->Controller->Auth->startup($event));

        $this->Controller->Auth->allow();
        $this->Controller->Auth->deny(null);

        $this->Controller->request['action'] = 'camelCase';
        $this->assertInstanceOf('Cake\Network\Response', $this->Controller->Auth->startup($event));
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
        $this->Controller->request->addParams(Router::parse($url));
        $this->Controller->request->query['url'] = Router::normalize($url);

        $this->assertInstanceOf('Cake\Network\Response', $this->Controller->Auth->startup($event));

        $url = '/auth_test/CamelCase';
        $this->Controller->request->addParams(Router::parse($url));
        $this->Controller->request->query['url'] = Router::normalize($url);
        $this->assertInstanceOf('Cake\Network\Response', $this->Controller->Auth->startup($event));
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
        $this->Controller->request->addParams(Router::parse($url));
        $this->Controller->request->query['url'] = Router::normalize($url);
        $this->Controller->Auth->loginAction = ['controller' => 'AuthTest', 'action' => 'login'];
        $this->Controller->Auth->userModel = 'AuthUsers';
        $this->Controller->Auth->allow();
        $result = $this->Controller->Auth->startup($event);
        $this->assertNull($result, 'startup() should return null, as action is allowed. %s');

        $url = '/auth_test/camelCase';
        $this->Controller->request->addParams(Router::parse($url));
        $this->Controller->request->query['url'] = Router::normalize($url);
        $this->Controller->Auth->loginAction = ['controller' => 'AuthTest', 'action' => 'login'];
        $this->Controller->Auth->userModel = 'AuthUsers';
        $this->Controller->Auth->allowedActions = ['delete', 'camelCase', 'add'];
        $result = $this->Controller->Auth->startup($event);
        $this->assertNull($result, 'startup() should return null, as action is allowed. %s');

        $this->Controller->Auth->allowedActions = ['delete', 'add'];
        $this->assertInstanceOf('Cake\Network\Response', $this->Controller->Auth->startup($event));

        $url = '/auth_test/delete';
        $this->Controller->request->addParams(Router::parse($url));
        $this->Controller->request->query['url'] = Router::normalize($url);
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
        $this->Controller->request->addParams(Router::parse($url));
        $this->Controller->request->query['url'] = Router::normalize($url);
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
        $url = '/auth_test/camelCase';

        $this->Auth->session->write('Auth', [
            'AuthUsers' => ['id' => '1', 'username' => 'nate']
        ]);

        $this->Auth->request->addParams(Router::parse('users/login'));
        $this->Auth->request->url = 'users/login';
        $this->Auth->request->env('HTTP_REFERER', false);

        $this->Auth->config('loginRedirect', [
            'controller' => 'pages',
            'action' => 'display',
            'welcome'
        ]);
        $event = new Event('Controller.startup', $this->Controller);
        $this->Auth->startup($event);
        $expected = Router::normalize($this->Auth->config('loginRedirect'));
        $this->assertEquals($expected, $this->Auth->redirectUrl());

        $this->Auth->session->delete('Auth');

        $url = '/posts/view/1';

        $this->Auth->session->write(
            'Auth',
            ['AuthUsers' => ['id' => '1', 'username' => 'nate']]
        );
        $this->Controller->testUrl = null;
        $this->Auth->request->addParams(Router::parse($url));
        $this->Auth->request->env('HTTP_REFERER', false);

        $this->Auth->config('authorize', 'controller');

        $this->Auth->config('loginAction', [
            'controller' => 'AuthTest', 'action' => 'login'
        ]);
        $event = new Event('Controller.startup', $this->Controller);
        $this->Auth->startup($event);
        $expected = Router::normalize('/auth_test/login');
        $this->assertEquals($expected, $this->Controller->testUrl);

        // Auth.redirect gets set when accessing a protected action without being authenticated
        $this->Auth->session->delete('Auth');
        $url = '/posts/view/1';
        $this->Auth->request->addParams(Router::parse($url));
        $this->Auth->request->url = $this->Auth->request->here = Router::normalize($url);
        $this->Auth->config('loginAction', ['controller' => 'AuthTest', 'action' => 'login']);
        $event = new Event('Controller.startup', $this->Controller);
        $this->Auth->startup($event);
        $expected = Router::normalize('posts/view/1');
        $this->assertEquals($expected, $this->Auth->session->read('Auth.redirect'));

        // QueryString parameters are preserved when setting Auth.redirect
        $this->Auth->session->delete('Auth');
        $url = '/posts/view/29';
        $this->Auth->request->addParams(Router::parse($url));
        $this->Auth->request->url = $this->Auth->request->here = Router::normalize($url);
        $this->Auth->request->query = [
            'print' => 'true',
            'refer' => 'menu'
        ];

        $this->Auth->config('loginAction', ['controller' => 'AuthTest', 'action' => 'login']);
        $event = new Event('Controller.startup', $this->Controller);
        $this->Auth->startup($event);
        $expected = Router::normalize('posts/view/29?print=true&refer=menu');
        $this->assertEquals($expected, $this->Auth->session->read('Auth.redirect'));

        // Different base urls.
        $appConfig = Configure::read('App');

        Configure::write('App', [
            'dir' => APP_DIR,
            'webroot' => 'webroot',
            'base' => false,
            'baseUrl' => '/cake/index.php'
        ]);

        $this->Auth->session->delete('Auth');

        $url = '/posts/add';
        $this->Auth->request = $this->Controller->request = new Request($url);
        $this->Auth->request->addParams(Router::parse($url));
        $this->Auth->request->url = Router::normalize($url);

        $this->Auth->config('loginAction', ['controller' => 'users', 'action' => 'login']);
        $event = new Event('Controller.startup', $this->Controller);
        $this->Auth->startup($event);
        $expected = Router::normalize('/posts/add');
        $this->assertEquals($expected, $this->Auth->session->read('Auth.redirect'));

        $this->Auth->session->delete('Auth');
        Configure::write('App', $appConfig);

        // External Authed Action
        $this->Auth->session->delete('Auth');
        $url = '/posts/view/1';
        $request = new Request($url);
        $request->env('HTTP_REFERER', 'http://webmail.example.com/view/message');
        $request->query = [];
        $this->Auth->request = $this->Controller->request = $request;
        $this->Auth->request->addParams(Router::parse($url));
        $this->Auth->request->url = $this->Auth->request->here = Router::normalize($url);
        $this->Auth->config('loginAction', ['controller' => 'AuthTest', 'action' => 'login']);
        $event = new Event('Controller.startup', $this->Controller);
        $this->Auth->startup($event);
        $expected = Router::normalize('/posts/view/1');
        $this->assertEquals($expected, $this->Auth->session->read('Auth.redirect'));

        $this->Auth->session->delete('Auth');
    }

    /**
     * testNoLoginRedirectForAuthenticatedUser method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testNoLoginRedirectForAuthenticatedUser()
    {
        $this->Controller->request['controller'] = 'auth_test';
        $this->Controller->request['action'] = 'login';
        $this->Controller->here = '/auth_test/login';
        $this->Auth->request->url = 'auth_test/login';

        $this->Auth->session->write('Auth.User.id', '1');
        $this->Auth->config('authenticate', ['Form']);
        $this->getMockBuilder(BaseAuthorize::class)
            ->setMethods(['authorize'])
            ->disableOriginalConstructor()
            ->setMockClassName('NoLoginRedirectMockAuthorize')
            ->getMock();
        $this->Auth->config('authorize', ['NoLoginRedirectMockAuthorize']);
        $this->Auth->config('loginAction', ['controller' => 'auth_test', 'action' => 'login']);

        $event = new Event('Controller.startup', $this->Controller);
        $return = $this->Auth->startup($event);
        $this->assertNull($return);
        $this->assertNull($this->Controller->testUrl);
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
        $this->Auth->request = $request = new Request($url);
        $request->env('HTTP_REFERER', false);
        $request->addParams(Router::parse($url));
        $request->addPaths([
            'base' => 'dirname',
            'webroot' => '/dirname/',
        ]);
        Router::pushRequest($request);

        $this->Auth->config('authorize', ['Controller']);
        $this->Auth->setUser(['username' => 'mariano', 'password' => 'cake']);
        $this->Auth->config('loginRedirect', [
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
        $this->Auth->request = $request = new Request([
            'url' => $url,
            'session' => $this->Auth->session
        ]);
        $this->Auth->request->addParams(Router::parse($url));
        $this->Auth->config('authorize', ['Controller']);
        $this->Auth->setUser(['username' => 'admad', 'password' => 'cake']);

        $expected = ['controller' => 'no_can_do', 'action' => 'jack'];
        $this->Auth->config('unauthorizedRedirect', $expected);

        $response = new Response();
        $Controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->setMethods(['on', 'redirect'])
            ->setConstructorArgs([$request, $response])
            ->getMock();

        $Controller->expects($this->once())
            ->method('redirect')
            ->with($this->equalTo($expected));

        $this->Auth->Flash->expects($this->once())
            ->method('set');

        $event = new Event('Controller.startup', $Controller);
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
        $this->Auth->request = $request = new Request([
            'url' => $url,
            'session' => $this->Auth->session
        ]);
        $this->Auth->request->addParams(Router::parse($url));
        $this->Auth->config('authorize', ['Controller']);
        $this->Auth->setUser(['username' => 'admad', 'password' => 'cake']);

        $this->Auth->config('unauthorizedRedirect', true);
        $this->Auth->config('loginAction', '/users/login');

        $response = new Response();
        $Controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->setMethods(['on', 'redirect'])
            ->setConstructorArgs([$request, $response])
            ->getMock();

        // Uses referrer instead of loginAction.
        $Controller->expects($this->once())
            ->method('redirect')
            ->with($this->equalTo('/'));

        $event = new Event('Controller.startup', $Controller);
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
        $this->Auth->session = $this->getMockBuilder('Cake\Network\Session')
            ->setMethods(['flash'])
            ->getMock();
        $this->Auth->request = $Request = new Request($url);
        $this->Auth->request->addParams(Router::parse($url));
        $this->Auth->config('authorize', ['Controller']);
        $this->Auth->setUser(['username' => 'admad', 'password' => 'cake']);
        $expected = ['controller' => 'no_can_do', 'action' => 'jack'];
        $this->Auth->config('unauthorizedRedirect', $expected);
        $this->Auth->config('authError', false);

        $Response = new Response();
        $Controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->setMethods(['on', 'redirect'])
            ->setConstructorArgs([$Request, $Response])
            ->getMock();

        $Controller->expects($this->once())
            ->method('redirect')
            ->with($this->equalTo($expected));

        $this->Auth->session->expects($this->never())
            ->method('flash');

        $event = new Event('Controller.startup', $Controller);
        $this->Auth->startup($event);
    }

    /**
     * Throw ForbiddenException if config `unauthorizedRedirect` is set to false
     *
     * @expectedException \Cake\Network\Exception\ForbiddenException
     * @return void
     * @triggers Controller.startup $Controller
     */
    public function testForbiddenException()
    {
        $url = '/party/on';
        $this->Auth->request = $request = new Request($url);
        $this->Auth->request->addParams(Router::parse($url));
        $this->Auth->config([
            'authorize' => ['Controller'],
            'unauthorizedRedirect' => false
        ]);
        $this->Auth->setUser(['username' => 'baker', 'password' => 'cake']);

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
        $this->Auth->request = $controller->request = new Request($url);
        $this->Auth->request->addParams(Router::parse($url));
        $this->Auth->config([
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
        $this->Auth->request->addParams(Router::parse('auth_test/something_totally_wrong'));
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
            $routes->fallbacks('InflectedRoute');
        });
        Router::scope('/', function ($routes) {
            $routes->fallbacks('InflectedRoute');
        });

        $url = '/admin/auth_test/add';
        $this->Auth->request->addParams(Router::parse($url));
        $this->Auth->request->query['url'] = ltrim($url, '/');
        $this->Auth->request->base = '';

        Router::setRequestInfo($this->Auth->request);

        $this->Auth->config('loginAction', [
            'prefix' => 'admin',
            'controller' => 'auth_test',
            'action' => 'login'
        ]);

        $this->Auth->startup($event);
        $this->assertEquals('/admin/auth_test/login', $this->Controller->testUrl);
    }

    /**
     * testAjaxLogin method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testAjaxLogin()
    {
        $this->Controller->request = new Request([
            'url' => '/ajax_auth/add',
            'environment' => ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
        ]);
        $this->Controller->request->params['action'] = 'add';

        $event = new Event('Controller.startup', $this->Controller);
        $this->Auth->config('ajaxLogin', 'test_element');
        $this->Auth->RequestHandler->ajaxLayout = 'ajax2';

        $response = $this->Auth->startup($event);

        $this->assertTrue($event->isStopped());
        $this->assertEquals(403, $response->statusCode());
        $this->assertEquals(
            "Ajax!\nthis is the test element",
            str_replace("\r\n", "\n", $response->body())
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
        $this->Controller->request = new Request([
            'url' => '/ajax_auth/add',
            'environment' => ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
        ]);
        $this->Controller->request->params['action'] = 'add';

        $event = new Event('Controller.startup', $this->Controller);
        $response = $this->Auth->startup($event);

        $this->assertTrue($event->isStopped());
        $this->assertEquals(403, $response->statusCode());
        $this->assertArrayNotHasKey('Location', $response->header());
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
            $routes->fallbacks('InflectedRoute');
        });
        Router::scope('/', function ($routes) {
            $routes->fallbacks('InflectedRoute');
        });

        $url = '/admin/auth_test/login';
        $request = $this->Auth->request;
        $request->addParams([
            'plugin' => null,
            'controller' => 'auth_test',
            'action' => 'login',
            'prefix' => 'admin',
            'pass' => [],
        ])->addPaths([
            'base' => null,
            'here' => $url,
            'webroot' => '/',
        ]);
        $request->url = ltrim($url, '/');
        Router::setRequestInfo($request);

        $this->Auth->config('loginAction', [
            'prefix' => 'admin',
            'controller' => 'auth_test',
            'action' => 'login'
        ]);
        $this->Auth->startup($event);

        $this->assertNull($this->Controller->testUrl);
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
        $url = '/auth_test/add';
        $this->Auth->request->addParams(Router::parse($url));
        $this->Auth->request->env('PHP_AUTH_USER', 'mariano');
        $this->Auth->request->env('PHP_AUTH_PW', 'cake');

        $this->Auth->config('authenticate', [
            'Basic' => ['userModel' => 'AuthUsers']
        ]);
        $this->Auth->config('storage', 'Memory');
        $this->Auth->startup($event);

        $result = $this->Auth->user();
        $this->assertEquals('mariano', $result['username']);

        $this->assertInstanceOf(
            'Cake\Auth\BasicAuthenticate',
            $this->Auth->authenticationProvider()
        );

        $result = $this->Auth->user('username');
        $this->assertEquals('mariano', $result);
        $this->assertFalse(isset($_SESSION));
    }

    /**
     * test $settings in Controller::$components
     *
     * @return void
     */
    public function testComponentSettings()
    {
        $this->Auth->config([
            'loginAction' => ['controller' => 'people', 'action' => 'login'],
            'logoutRedirect' => ['controller' => 'people', 'action' => 'login'],
        ]);

        $expected = [
            'loginAction' => ['controller' => 'people', 'action' => 'login'],
            'logoutRedirect' => ['controller' => 'people', 'action' => 'login'],
        ];
        $this->assertEquals(
            $expected['loginAction'],
            $this->Auth->config('loginAction')
        );
        $this->assertEquals(
            $expected['logoutRedirect'],
            $this->Auth->config('logoutRedirect')
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
        $this->Auth->session->write('Auth.redirect', '/Users/login');
        $this->Auth->config('logoutRedirect', '/');
        $result = $this->Auth->logout();

        $this->assertEquals('/', $result);
        $this->assertNull($this->Auth->session->read('Auth.AuthUsers'));
        $this->assertNull($this->Auth->session->read('Auth.redirect'));
    }

    /**
     * Test that Auth.afterIdentify and Auth.logout events are triggered
     *
     * @return void
     */
    public function testEventTriggering()
    {
        $this->Auth->config('authenticate', [
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
        $this->Auth->request->addParams(Router::parse($url));
        $this->Auth->request->env('PHP_AUTH_USER', 'mariano');
        $this->Auth->request->env('PHP_AUTH_PW', 'cake');

        $this->Auth->config('authenticate', [
            'Basic' => ['userModel' => 'AuthUsers']
        ]);
        $this->Auth->config('storage', 'Memory');

        EventManager::instance()->on('Auth.afterIdentify', function ($event) {
            $user = $event->data[0];
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
            ->setConstructorArgs([$this->Auth->request, $this->Auth->response])
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
        $this->Controller->request->params['action'] = 'add';
        $this->Auth->startup(new Event('Controller.startup', $this->Controller));

        $this->Auth->Flash->expects($this->at(0))
            ->method('set')
            ->with(
                'Auth failure',
                [
                    'key' => 'auth-key',
                    'element' => 'default',
                    'params' => ['class' => 'error']
                ]
            );

        $this->Auth->Flash->expects($this->at(1))
            ->method('set')
            ->with('Auth failure', ['key' => 'auth-key', 'element' => 'custom']);

        $this->Auth->config('flash', [
            'key' => 'auth-key'
        ]);
        $this->Auth->flash('Auth failure');

        $this->Auth->config('flash', [
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
        $this->assertEquals($value, $this->Auth->session->read('Auth.redirect'));

        $request = new Request();
        $request->base = '/base';
        Router::setRequestInfo($request);

        $result = $this->Auth->redirectUrl($value);
        $this->assertEquals('/users/home', $result);
    }

    /**
     * test redirect using Auth.redirect from the session.
     *
     * @return void
     */
    public function testRedirectSessionRead()
    {
        $this->Auth->config('loginAction', ['controller' => 'users', 'action' => 'login']);
        $this->Auth->session->write('Auth.redirect', '/users/home');

        $result = $this->Auth->redirectUrl();
        $this->assertEquals('/users/home', $result);
        $this->assertFalse($this->Auth->session->check('Auth.redirect'));
    }

    /**
     * test redirectUrl with duplicate base.
     *
     * @return void
     */
    public function testRedirectSessionReadDuplicateBase()
    {
        $this->Auth->request->webroot = '/waves/';
        $this->Auth->request->base = '/waves';

        Router::setRequestInfo($this->Auth->request);

        $this->Auth->session->write('Auth.redirect', '/waves/add');

        $result = $this->Auth->redirectUrl();
        $this->assertEquals('/waves/add', $result);
    }

    /**
     * test that redirect does not return loginAction if that is what's stored in Auth.redirect.
     * instead loginRedirect should be used.
     *
     * @return void
     */
    public function testRedirectSessionReadEqualToLoginAction()
    {
        $this->Auth->config([
            'loginAction' => ['controller' => 'users', 'action' => 'login'],
            'loginRedirect' => ['controller' => 'users', 'action' => 'home']
        ]);
        $this->Auth->session->write('Auth.redirect', ['controller' => 'users', 'action' => 'login']);

        $result = $this->Auth->redirectUrl();
        $this->assertEquals('/users/home', $result);
        $this->assertFalse($this->Auth->session->check('Auth.redirect'));
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
        $this->Auth->request = $this->Controller->request = new Request($url);
        $this->Auth->request->addParams(Router::parse($url));
        $this->Auth->request->url = Router::normalize($url);

        Router::setRequestInfo($this->Auth->request);

        $this->Auth->config('loginAction', ['controller' => 'users', 'action' => 'login']);
        $this->Auth->config('loginRedirect', ['controller' => 'users', 'action' => 'home']);

        $result = $this->Auth->redirectUrl();
        $this->assertEquals('/users/home', $result);
        $this->assertFalse($this->Auth->session->check('Auth.redirect'));

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
            $this->assertEquals(null, $result);

            $result = $this->Auth->user('Company.invalid');
            $this->assertEquals(null, $result);

            $result = $this->Auth->user('is_admin');
            $this->assertFalse($result);
    }

    /**
     * testStatelessAuthNoRedirect method
     *
     * @expectedException \Cake\Network\Exception\UnauthorizedException
     * @expectedExceptionCode 401
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testStatelessAuthNoRedirect()
    {
        $event = new Event('Controller.startup', $this->Controller);
        $_SESSION = [];

        $this->Auth->config('authenticate', ['Basic']);
        $this->Controller->request['action'] = 'add';

        $result = $this->Auth->startup($event);
    }

    /**
     * testStatelessAuthRedirect method
     *
     * @return void
     * @triggers Controller.startup $this->Controller
     */
    public function testStatelessFollowedByStatefulAuth()
    {
        $this->Auth->response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['stop', 'statusCode', 'send'])
            ->getMock();
        $event = new Event('Controller.startup', $this->Controller);
        $this->Auth->authenticate = ['Basic', 'Form'];
        $this->Controller->request['action'] = 'add';

        $this->Auth->response->expects($this->never())->method('statusCode');
        $this->Auth->response->expects($this->never())->method('send');

        $this->assertInstanceOf('Cake\Network\Response', $this->Auth->startup($event));

        $this->assertEquals('/users/login', $this->Controller->testUrl);
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
        $this->assertEquals('Auth.Member', $this->Auth->storage()->config('key'));

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
        $this->Auth->config('checkAuthIn', 'Controller.initialize');
        $this->Controller->startupProcess();
        $this->assertEquals('Controller.initialize', $this->Auth->authCheckCalledFrom);
    }
}
