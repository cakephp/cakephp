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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Error;

use Cake\Controller\Component;
use Cake\Controller\Controller;
use Cake\Controller\Exception\MissingActionException;
use Cake\Controller\Exception\MissingComponentException;
use Cake\Core\Configure;
use Cake\Core\Exception\Exception as CakeException;
use Cake\Core\Exception\MissingPluginException;
use Cake\Core\Plugin;
use Cake\Datasource\Exception\MissingDatasourceConfigException;
use Cake\Datasource\Exception\MissingDatasourceException;
use Cake\Error\ExceptionRenderer;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Http\Exception\HttpException;
use Cake\Http\Exception\InternalErrorException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\ServerRequest;
use Cake\Mailer\Exception\MissingActionException as MissingMailerActionException;
use Cake\ORM\Exception\MissingBehaviorException;
use Cake\Routing\DispatcherFactory;
use Cake\Routing\Exception\MissingControllerException;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\Exception\MissingHelperException;
use Cake\View\Exception\MissingLayoutException;
use Cake\View\Exception\MissingTemplateException;
use Exception;
use RuntimeException;
use TestApp\Controller\Admin\ErrorController;

/**
 * BlueberryComponent class
 */
class BlueberryComponent extends Component
{

    /**
     * testName property
     *
     * @return void
     */
    public $testName = null;

    /**
     * initialize method
     *
     * @param array $config
     * @return void
     */
    public function initialize(array $config)
    {
        $this->testName = 'BlueberryComponent';
    }
}

/**
 * TestErrorController class
 */
class TestErrorController extends Controller
{

    /**
     * uses property
     *
     * @var array
     */
    public $uses = [];

    /**
     * components property
     *
     * @return void
     */
    public $components = ['Blueberry'];

    /**
     * beforeRender method
     *
     * @return void
     */
    public function beforeRender(Event $event)
    {
        echo $this->Blueberry->testName;
    }

    /**
     * index method
     *
     * @return array
     */
    public function index()
    {
        $this->autoRender = false;

        return 'what up';
    }
}

/**
 * MyCustomExceptionRenderer class
 */
class MyCustomExceptionRenderer extends ExceptionRenderer
{
    public function setController($controller)
    {
        $this->controller = $controller;
    }

    /**
     * custom error message type.
     *
     * @return string
     */
    public function missingWidgetThing()
    {
        return 'widget thing is missing';
    }
}

/**
 * Exception class for testing app error handlers and custom errors.
 */
class MissingWidgetThingException extends NotFoundException
{
}

/**
 * Exception class for testing app error handlers and custom errors.
 */
class MissingWidgetThing extends \Exception
{
}

/**
 * ExceptionRendererTest class
 */
class ExceptionRendererTest extends TestCase
{

    /**
     * @var bool
     */
    protected $_restoreError = false;

    /**
     * setup create a request object to get out of router later.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Configure::write('Config.language', 'eng');
        Router::reload();

        $request = new ServerRequest(['base' => '']);
        Router::setRequestInfo($request);
        Configure::write('debug', true);
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        $this->clearPlugins();
        if ($this->_restoreError) {
            restore_error_handler();
        }
    }

    public function testControllerInstanceForPrefixedRequest()
    {
        $namespace = Configure::read('App.namespace');
        Configure::write('App.namespace', 'TestApp');

        $exception = new NotFoundException('Page not found');
        $request = new ServerRequest();
        $request = $request
            ->withParam('controller', 'Articles')
            ->withParam('prefix', 'admin');

        $ExceptionRenderer = new MyCustomExceptionRenderer($exception, $request);

        $this->assertInstanceOf(
            ErrorController::class,
            $ExceptionRenderer->__debugInfo()['controller']
        );

        Configure::write('App.namespace', $namespace);
    }

    /**
     * test that methods declared in an ExceptionRenderer subclass are not converted
     * into error400 when debug > 0
     *
     * @return void
     */
    public function testSubclassMethodsNotBeingConvertedToError()
    {
        $exception = new MissingWidgetThingException('Widget not found');
        $ExceptionRenderer = new MyCustomExceptionRenderer($exception);

        $result = $ExceptionRenderer->render();

        $this->assertEquals('widget thing is missing', (string)$result->getBody());
    }

    /**
     * test that subclass methods are not converted when debug = 0
     *
     * @return void
     */
    public function testSubclassMethodsNotBeingConvertedDebug0()
    {
        Configure::write('debug', false);
        $exception = new MissingWidgetThingException('Widget not found');
        $ExceptionRenderer = new MyCustomExceptionRenderer($exception);

        $result = $ExceptionRenderer->render();

        $this->assertEquals(
            'missingWidgetThing',
            $ExceptionRenderer->__debugInfo()['method']
        );
        $this->assertEquals(
            'widget thing is missing',
            (string)$result->getBody(),
            'Method declared in subclass converted to error400'
        );
    }

    /**
     * test that ExceptionRenderer subclasses properly convert framework errors.
     *
     * @return void
     */
    public function testSubclassConvertingFrameworkErrors()
    {
        Configure::write('debug', false);

        $exception = new MissingControllerException('PostsController');
        $ExceptionRenderer = new MyCustomExceptionRenderer($exception);

        $result = $ExceptionRenderer->render();

        $this->assertRegExp(
            '/Not Found/',
            (string)$result->getBody(),
            'Method declared in error handler not converted to error400. %s'
        );
    }

    /**
     * test things in the constructor.
     *
     * @return void
     */
    public function testConstruction()
    {
        $exception = new NotFoundException('Page not found');
        $ExceptionRenderer = new ExceptionRenderer($exception);

        $this->assertInstanceOf(
            'Cake\Controller\ErrorController',
            $ExceptionRenderer->__debugInfo()['controller']
        );
        $this->assertEquals($exception, $ExceptionRenderer->__debugInfo()['error']);
    }

    /**
     * test that exception message gets coerced when debug = 0
     *
     * @return void
     */
    public function testExceptionMessageCoercion()
    {
        Configure::write('debug', false);
        $exception = new MissingActionException('Secret info not to be leaked');
        $ExceptionRenderer = new ExceptionRenderer($exception);

        $this->assertInstanceOf(
            'Cake\Controller\ErrorController',
            $ExceptionRenderer->__debugInfo()['controller']
        );
        $this->assertEquals($exception, $ExceptionRenderer->__debugInfo()['error']);

        $result = (string)$ExceptionRenderer->render()->getBody();

        $this->assertEquals('error400', $ExceptionRenderer->__debugInfo()['template']);
        $this->assertContains('Not Found', $result);
        $this->assertNotContains('Secret info not to be leaked', $result);
    }

    /**
     * test that helpers in custom CakeErrorController are not lost
     *
     * @return void
     */
    public function testCakeErrorHelpersNotLost()
    {
        static::setAppNamespace();
        $exception = new NotFoundException();
        $renderer = new \TestApp\Error\TestAppsExceptionRenderer($exception);

        $result = $renderer->render();
        $this->assertContains('<b>peeled</b>', (string)$result->getBody());
    }

    /**
     * test that unknown exception types with valid status codes are treated correctly.
     *
     * @return void
     */
    public function testUnknownExceptionTypeWithExceptionThatHasA400Code()
    {
        $exception = new MissingWidgetThingException('coding fail.');
        $ExceptionRenderer = new ExceptionRenderer($exception);
        $response = $ExceptionRenderer->render();

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse(method_exists($ExceptionRenderer, 'missingWidgetThing'), 'no method should exist.');
        $this->assertContains('coding fail', (string)$response->getBody(), 'Text should show up.');
    }

    /**
     * test that unknown exception types with valid status codes are treated correctly.
     *
     * @return void
     */
    public function testUnknownExceptionTypeWithNoCodeIsA500()
    {
        $exception = new \OutOfBoundsException('foul ball.');
        $ExceptionRenderer = new ExceptionRenderer($exception);
        $result = $ExceptionRenderer->render();

        $this->assertEquals(500, $result->getStatusCode());
        $this->assertContains('foul ball.', (string)$result->getBody(), 'Text should show up as its debug mode.');
    }

    /**
     * test that unknown exceptions have messages ignored.
     *
     * @return void
     */
    public function testUnknownExceptionInProduction()
    {
        Configure::write('debug', false);

        $exception = new \OutOfBoundsException('foul ball.');
        $ExceptionRenderer = new ExceptionRenderer($exception);

        $response = $ExceptionRenderer->render();
        $result = (string)$response->getBody();

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertNotContains('foul ball.', $result, 'Text should no show up.');
        $this->assertContains('Internal Error', $result, 'Generic message only.');
    }

    /**
     * test that unknown exception types with valid status codes are treated correctly.
     *
     * @return void
     */
    public function testUnknownExceptionTypeWithCodeHigherThan500()
    {
        $exception = new \OutOfBoundsException('foul ball.', 501);
        $ExceptionRenderer = new ExceptionRenderer($exception);
        $response = $ExceptionRenderer->render();
        $result = (string)$response->getBody();

        $this->assertEquals(501, $response->getStatusCode());
        $this->assertContains('foul ball.', $result, 'Text should show up as its debug mode.');
    }

    /**
     * testerror400 method
     *
     * @return void
     */
    public function testError400()
    {
        Router::reload();

        $request = new ServerRequest('posts/view/1000');
        Router::setRequestInfo($request);

        $exception = new NotFoundException('Custom message');
        $ExceptionRenderer = new ExceptionRenderer($exception);

        $response = $ExceptionRenderer->render();
        $result = (string)$response->getBody();

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertContains('<h2>Custom message</h2>', $result);
        $this->assertRegExp("/<strong>'.*?\/posts\/view\/1000'<\/strong>/", $result);
    }

    /**
     * testerror400 method when returning as json
     *
     * @return void
     */
    public function testError400AsJson()
    {
        Router::reload();

        $request = new ServerRequest('posts/view/1000?sort=title&direction=desc');
        $request = $request->withHeader('Accept', 'application/json');
        $request = $request->withHeader('Content-Type', 'application/json');
        Router::setRequestInfo($request);

        $exception = new NotFoundException('Custom message');
        $exceptionLine = __LINE__ - 1;
        $ExceptionRenderer = new ExceptionRenderer($exception);

        $response = $ExceptionRenderer->render();
        $result = (string)$response->getBody();
        $expected = [
            'message' => 'Custom message',
            'url' => '/posts/view/1000?sort=title&amp;direction=desc',
            'code' => 404,
            'file' => __FILE__,
            'line' => $exceptionLine,
        ];
        $this->assertEquals($expected, json_decode($result, true));
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * test that error400 only modifies the messages on Cake Exceptions.
     *
     * @return void
     */
    public function testerror400OnlyChangingCakeException()
    {
        Configure::write('debug', false);

        $exception = new NotFoundException('Custom message');
        $ExceptionRenderer = new ExceptionRenderer($exception);

        $result = $ExceptionRenderer->render();
        $this->assertContains('Custom message', (string)$result->getBody());

        $exception = new MissingActionException(['controller' => 'PostsController', 'action' => 'index']);
        $ExceptionRenderer = new ExceptionRenderer($exception);

        $result = $ExceptionRenderer->render();
        $this->assertContains('Not Found', (string)$result->getBody());
    }

    /**
     * test that error400 doesn't expose XSS
     *
     * @return void
     */
    public function testError400NoInjection()
    {
        Router::reload();

        $request = new ServerRequest('pages/<span id=333>pink</span></id><script>document.body.style.background = t=document.getElementById(333).innerHTML;window.alert(t);</script>');
        Router::setRequestInfo($request);

        $exception = new NotFoundException('Custom message');
        $ExceptionRenderer = new ExceptionRenderer($exception);

        $result = (string)$ExceptionRenderer->render()->getBody();

        $this->assertNotContains('<script>document', $result);
        $this->assertNotContains('alert(t);</script>', $result);
    }

    /**
     * testError500 method
     *
     * @return void
     */
    public function testError500Message()
    {
        $exception = new InternalErrorException('An Internal Error Has Occurred.');
        $ExceptionRenderer = new ExceptionRenderer($exception);

        $response = $ExceptionRenderer->render();
        $result = (string)$response->getBody();
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertContains('<h2>An Internal Error Has Occurred.</h2>', $result);
        $this->assertContains('An Internal Error Has Occurred.</p>', $result);
    }

    /**
     * testExceptionResponseHeader method
     *
     * @return void
     */
    public function testExceptionResponseHeader()
    {
        $exception = new MethodNotAllowedException('Only allowing POST and DELETE');
        $exception->responseHeader(['Allow' => 'POST, DELETE']);
        $ExceptionRenderer = new ExceptionRenderer($exception);

        $result = $ExceptionRenderer->render();
        $this->assertTrue($result->hasHeader('Allow'));
        $this->assertEquals('POST, DELETE', $result->getHeaderLine('Allow'));
    }

    /**
     * testMissingController method
     *
     * @return void
     */
    public function testMissingController()
    {
        $exception = new MissingControllerException([
            'class' => 'Posts',
            'prefix' => '',
            'plugin' => '',
        ]);
        $ExceptionRenderer = new MyCustomExceptionRenderer($exception);

        $result = (string)$ExceptionRenderer->render()->getBody();

        $this->assertEquals(
            'missingController',
            $ExceptionRenderer->__debugInfo()['template']
        );
        $this->assertContains('Missing Controller', $result);
        $this->assertContains('<em>PostsController</em>', $result);
    }

    /**
     * test missingController method
     *
     * @return void
     */
    public function testMissingControllerLowerCase()
    {
        $exception = new MissingControllerException([
            'class' => 'posts',
            'prefix' => '',
            'plugin' => '',
        ]);
        $ExceptionRenderer = new MyCustomExceptionRenderer($exception);

        $result = (string)$ExceptionRenderer->render()->getBody();

        $this->assertEquals(
            'missingController',
            $ExceptionRenderer->__debugInfo()['template']
        );
        $this->assertContains('Missing Controller', $result);
        $this->assertContains('<em>PostsController</em>', $result);
    }

    /**
     * Returns an array of tests to run for the various Cake Exception classes.
     *
     * @return array
     */
    public static function exceptionProvider()
    {
        return [
            [
                new MissingActionException([
                    'controller' => 'postsController',
                    'action' => 'index',
                    'prefix' => '',
                    'plugin' => '',
                ]),
                [
                    '/Missing Method in PostsController/',
                    '/<em>PostsController::index\(\)<\/em>/',
                ],
                404,
            ],
            [
                new MissingActionException([
                    'controller' => 'PostsController',
                    'action' => 'index',
                    'prefix' => '',
                    'plugin' => '',
                ]),
                [
                    '/Missing Method in PostsController/',
                    '/<em>PostsController::index\(\)<\/em>/',
                ],
                404,
            ],
            [
                new MissingTemplateException(['file' => '/posts/about.ctp']),
                [
                    "/posts\/about.ctp/",
                ],
                500,
            ],
            [
                new MissingLayoutException(['file' => 'layouts/my_layout.ctp']),
                [
                    '/Missing Layout/',
                    "/layouts\/my_layout.ctp/",
                ],
                500,
            ],
            [
                new MissingHelperException(['class' => 'MyCustomHelper']),
                [
                    '/Missing Helper/',
                    '/<em>MyCustomHelper<\/em> could not be found./',
                    '/Create the class <em>MyCustomHelper<\/em> below in file:/',
                    '/(\/|\\\)MyCustomHelper.php/',
                ],
                500,
            ],
            [
                new MissingBehaviorException(['class' => 'MyCustomBehavior']),
                [
                    '/Missing Behavior/',
                    '/Create the class <em>MyCustomBehavior<\/em> below in file:/',
                    '/(\/|\\\)MyCustomBehavior.php/',
                ],
                500,
            ],
            [
                new MissingComponentException(['class' => 'SideboxComponent']),
                [
                    '/Missing Component/',
                    '/Create the class <em>SideboxComponent<\/em> below in file:/',
                    '/(\/|\\\)SideboxComponent.php/',
                ],
                500,
            ],
            [
                new MissingDatasourceConfigException(['name' => 'MyDatasourceConfig']),
                [
                    '/Missing Datasource Configuration/',
                    '/<em>MyDatasourceConfig<\/em> was not found/',
                ],
                500,
            ],
            [
                new MissingDatasourceException(['class' => 'MyDatasource', 'plugin' => 'MyPlugin']),
                [
                    '/Missing Datasource/',
                    '/<em>MyPlugin.MyDatasource<\/em> could not be found./',
                ],
                500,
            ],
            [
                new MissingMailerActionException([
                    'mailer' => 'UserMailer',
                    'action' => 'welcome',
                    'prefix' => '',
                    'plugin' => '',
                ]),
                [
                    '/Missing Method in UserMailer/',
                    '/<em>UserMailer::welcome\(\)<\/em>/',
                ],
                404,
            ],
            [
                new Exception('boom'),
                [
                    '/Internal Error/',
                ],
                500,
            ],
            [
                new RuntimeException('another boom'),
                [
                    '/Internal Error/',
                ],
                500,
            ],
            [
                new CakeException('base class'),
                ['/Internal Error/'],
                500,
            ],
            [
                new HttpException('Network Authentication Required', 511),
                ['/Network Authentication Required/'],
                511,
            ],
        ];
    }

    /**
     * Test the various Cake Exception sub classes
     *
     * @dataProvider exceptionProvider
     * @return void
     */
    public function testCakeExceptionHandling($exception, $patterns, $code)
    {
        $exceptionRenderer = new ExceptionRenderer($exception);
        $response = $exceptionRenderer->render();

        $this->assertEquals($code, $response->getStatusCode());
        $body = (string)$response->getBody();
        foreach ($patterns as $pattern) {
            $this->assertRegExp($pattern, $body);
        }
    }

    /**
     * Test that class names not ending in Exception are not mangled.
     *
     * @return void
     */
    public function testExceptionNameMangling()
    {
        $exceptionRenderer = new MyCustomExceptionRenderer(new MissingWidgetThing());

        $result = (string)$exceptionRenderer->render()->getBody();
        $this->assertContains('widget thing is missing', $result);

        // Custom method should be called even when debug is off.
        Configure::write('debug', false);
        $exceptionRenderer = new MyCustomExceptionRenderer(new MissingWidgetThing());

        $result = (string)$exceptionRenderer->render()->getBody();
        $this->assertContains('widget thing is missing', $result);
    }

    /**
     * Test exceptions being raised when helpers are missing.
     *
     * @return void
     */
    public function testMissingRenderSafe()
    {
        $exception = new MissingHelperException(['class' => 'Fail']);
        $ExceptionRenderer = new MyCustomExceptionRenderer($exception);

        $controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->setMethods(['render'])
            ->getMock();
        $controller->helpers = ['Fail', 'Boom'];
        $controller->request = new ServerRequest();
        $controller->expects($this->at(0))
            ->method('render')
            ->with('missingHelper')
            ->will($this->throwException($exception));

        $ExceptionRenderer->setController($controller);

        $response = $ExceptionRenderer->render();
        sort($controller->helpers);
        $this->assertEquals(['Form', 'Html'], $controller->helpers);
        $this->assertContains('Helper class Fail', (string)$response->getBody());
    }

    /**
     * Test that exceptions in beforeRender() are handled by outputMessageSafe
     *
     * @return void
     */
    public function testRenderExceptionInBeforeRender()
    {
        $exception = new NotFoundException('Not there, sorry');
        $ExceptionRenderer = new MyCustomExceptionRenderer($exception);

        $controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->setMethods(['beforeRender'])
            ->getMock();
        $controller->request = new ServerRequest();
        $controller->expects($this->any())
            ->method('beforeRender')
            ->will($this->throwException($exception));

        $ExceptionRenderer->setController($controller);

        $response = $ExceptionRenderer->render();
        $this->assertContains('Not there, sorry', (string)$response->getBody());
    }

    /**
     * Test that missing layoutPath don't cause other fatal errors.
     *
     * @return void
     */
    public function testMissingLayoutPathRenderSafe()
    {
        $this->called = false;
        $exception = new NotFoundException();
        $ExceptionRenderer = new MyCustomExceptionRenderer($exception);

        $controller = new Controller();
        $controller->helpers = ['Fail', 'Boom'];
        $controller->getEventManager()->on(
            'Controller.beforeRender',
            function (Event $event) {
                $this->called = true;
                $event->getSubject()->viewBuilder()->setLayoutPath('boom');
            }
        );
        $controller->setRequest(new ServerRequest());

        $ExceptionRenderer->setController($controller);

        $response = $ExceptionRenderer->render();
        $this->assertEquals('text/html', $response->getType());
        $this->assertContains('Not Found', (string)$response->getBody());
        $this->assertTrue($this->called, 'Listener added was not triggered.');
        $this->assertEquals('', $controller->viewBuilder()->getLayoutPath());
        $this->assertEquals('Error', $controller->viewBuilder()->getTemplatePath());
    }

    /**
     * Test that missing plugin disables Controller::$plugin if the two are the same plugin.
     *
     * @return void
     */
    public function testMissingPluginRenderSafe()
    {
        $exception = new NotFoundException();
        $ExceptionRenderer = new MyCustomExceptionRenderer($exception);

        $controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->setMethods(['render'])
            ->getMock();
        $controller->setPlugin('TestPlugin');
        $controller->request = $this->getMockBuilder('Cake\Http\ServerRequest')->getMock();

        $exception = new MissingPluginException(['plugin' => 'TestPlugin']);
        $controller->expects($this->once())
            ->method('render')
            ->with('error400')
            ->will($this->throwException($exception));

        $ExceptionRenderer->setController($controller);

        $response = $ExceptionRenderer->render();
        $body = (string)$response->getBody();
        $this->assertNotContains('test plugin error500', $body);
        $this->assertContains('Not Found', $body);
    }

    /**
     * Test that missing plugin doesn't disable Controller::$plugin if the two aren't the same plugin.
     *
     * @return void
     */
    public function testMissingPluginRenderSafeWithPlugin()
    {
        $this->loadPlugins(['TestPlugin']);
        $exception = new NotFoundException();
        $ExceptionRenderer = new MyCustomExceptionRenderer($exception);

        $controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->setMethods(['render'])
            ->getMock();
        $controller->setPlugin('TestPlugin');
        $controller->request = $this->getMockBuilder('Cake\Http\ServerRequest')->getMock();

        $exception = new MissingPluginException(['plugin' => 'TestPluginTwo']);
        $controller->expects($this->once())
            ->method('render')
            ->with('error400')
            ->will($this->throwException($exception));

        $ExceptionRenderer->setController($controller);

        $response = $ExceptionRenderer->render();
        $body = (string)$response->getBody();
        $this->assertContains('test plugin error500', $body);
        $this->assertContains('Not Found', $body);
    }

    /**
     * Test that exceptions can be rendered when a request hasn't been registered
     * with Router
     *
     * @return void
     */
    public function testRenderWithNoRequest()
    {
        Router::reload();
        $this->assertNull(Router::getRequest(false));

        $exception = new Exception('Terrible');
        $ExceptionRenderer = new ExceptionRenderer($exception);
        $result = $ExceptionRenderer->render();

        $this->assertContains('Internal Error', (string)$result->getBody());
        $this->assertEquals(500, $result->getStatusCode());
    }

    /**
     * Test that router request parameters are applied when the passed
     * request has no params.
     *
     * @return void
     */
    public function testRenderInheritRoutingParams()
    {
        $routerRequest = new ServerRequest([
            'params' => [
                'controller' => 'Articles',
                'action' => 'index',
                'plugin' => null,
                'pass' => [],
                '_ext' => 'json',
            ],
        ]);
        // Simulate a request having routing applied and stored in router
        Router::pushRequest($routerRequest);

        $exceptionRenderer = new ExceptionRenderer(new Exception('Terrible'), new ServerRequest());
        $exceptionRenderer->render();
        $properties = $exceptionRenderer->__debugInfo();

        foreach (['controller', 'action', '_ext'] as $key) {
            $this->assertSame(
                $routerRequest->getParam($key),
                $properties['controller']->request->getParam($key)
            );
        }
    }

    /**
     * Test that rendering exceptions triggers shutdown events.
     *
     * @return void
     */
    public function testRenderShutdownEvents()
    {
        $fired = [];
        $listener = function (Event $event) use (&$fired) {
            $fired[] = $event->getName();
        };
        $events = EventManager::instance();
        $events->on('Controller.shutdown', $listener);
        $events->on('Dispatcher.afterDispatch', $listener);

        $exception = new Exception('Terrible');
        $renderer = new ExceptionRenderer($exception);
        $renderer->render();

        $expected = ['Controller.shutdown', 'Dispatcher.afterDispatch'];
        $this->assertEquals($expected, $fired);
    }

    /**
     * Test that rendering exceptions triggers events
     * on filters attached to dispatcherfactory
     *
     * @return void
     */
    public function testRenderShutdownEventsOnDispatcherFactory()
    {
        $filter = $this->getMockBuilder('Cake\Routing\DispatcherFilter')
            ->setMethods(['afterDispatch'])
            ->getMock();

        $filter->expects($this->at(0))
            ->method('afterDispatch');

        DispatcherFactory::add($filter);

        $exception = new Exception('Terrible');
        $renderer = new ExceptionRenderer($exception);
        $renderer->render();
    }

    /**
     * test that subclass methods fire shutdown events.
     *
     * @return void
     */
    public function testSubclassTriggerShutdownEvents()
    {
        $fired = [];
        $listener = function (Event $event) use (&$fired) {
            $fired[] = $event->getName();
        };
        $events = EventManager::instance();
        $events->on('Controller.shutdown', $listener);
        $events->on('Dispatcher.afterDispatch', $listener);

        $exception = new MissingWidgetThingException('Widget not found');
        $renderer = new MyCustomExceptionRenderer($exception);
        $renderer->render();

        $expected = ['Controller.shutdown', 'Dispatcher.afterDispatch'];
        $this->assertEquals($expected, $fired);
    }

    /**
     * Tests the output of rendering a PDOException
     *
     * @return void
     */
    public function testPDOException()
    {
        $exception = new \PDOException('There was an error in the SQL query');
        $exception->queryString = 'SELECT * from poo_query < 5 and :seven';
        $exception->params = ['seven' => 7];
        $ExceptionRenderer = new ExceptionRenderer($exception);
        $response = $ExceptionRenderer->render();

        $this->assertEquals(500, $response->getStatusCode());
        $result = (string)$response->getBody();
        $this->assertContains('Database Error', $result);
        $this->assertContains('There was an error in the SQL query', $result);
        $this->assertContains(h('SELECT * from poo_query < 5 and :seven'), $result);
        $this->assertContains("'seven' => (int) 7", $result);
    }
}
