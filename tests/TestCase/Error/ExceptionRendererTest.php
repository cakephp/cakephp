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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Error;

use Cake\Controller\Controller;
use Cake\Controller\Exception\MissingActionException;
use Cake\Controller\Exception\MissingComponentException;
use Cake\Core\Configure;
use Cake\Core\Exception\CakeException;
use Cake\Core\Exception\MissingPluginException;
use Cake\Datasource\Exception\MissingDatasourceConfigException;
use Cake\Datasource\Exception\MissingDatasourceException;
use Cake\Error\ExceptionRenderer;
use Cake\Event\EventInterface;
use Cake\Event\EventManager;
use Cake\Http\Exception\HttpException;
use Cake\Http\Exception\InternalErrorException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\MissingControllerException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\ServerRequest;
use Cake\Mailer\Exception\MissingActionException as MissingMailerActionException;
use Cake\ORM\Exception\MissingBehaviorException;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\Exception\MissingHelperException;
use Cake\View\Exception\MissingLayoutException;
use Cake\View\Exception\MissingTemplateException;
use Exception;
use RuntimeException;
use TestApp\Controller\Admin\ErrorController;
use TestApp\Error\Exception\MissingWidgetThing;
use TestApp\Error\Exception\MissingWidgetThingException;
use TestApp\Error\MyCustomExceptionRenderer;

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
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('Config.language', 'eng');
        Router::reload();

        $request = new ServerRequest(['base' => '']);
        Router::setRequest($request);
        Configure::write('debug', true);
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
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
            ->withParam('prefix', 'Admin');

        $ExceptionRenderer = new MyCustomExceptionRenderer($exception, $request);

        $this->assertInstanceOf(
            ErrorController::class,
            $ExceptionRenderer->__debugInfo()['controller']
        );

        Configure::write('App.namespace', $namespace);
    }

    /**
     * testTemplatePath
     *
     * @return void
     */
    public function testTemplatePath()
    {
        $request = (new ServerRequest())
            ->withParam('controller', 'Foo')
            ->withParam('action', 'bar');
        $exception = new NotFoundException();
        $ExceptionRenderer = new ExceptionRenderer($exception, $request);

        $ExceptionRenderer->render();
        $controller = $ExceptionRenderer->__debugInfo()['controller'];
        $this->assertSame('error400', $controller->viewBuilder()->getTemplate());
        $this->assertSame('Error', $controller->viewBuilder()->getTemplatePath());

        $request = $request->withParam('prefix', 'Admin');
        $exception = new MissingActionException(['controller' => 'Foo', 'action' => 'bar']);

        $ExceptionRenderer = new ExceptionRenderer($exception, $request);

        $ExceptionRenderer->render();
        $controller = $ExceptionRenderer->__debugInfo()['controller'];
        $this->assertSame('missingAction', $controller->viewBuilder()->getTemplate());
        $this->assertSame('Error', $controller->viewBuilder()->getTemplatePath());

        Configure::write('debug', false);
        $ExceptionRenderer = new ExceptionRenderer($exception, $request);

        $ExceptionRenderer->render();
        $controller = $ExceptionRenderer->__debugInfo()['controller'];
        $this->assertSame('error400', $controller->viewBuilder()->getTemplate());
        $this->assertSame(
            'Admin' . DIRECTORY_SEPARATOR . 'Error',
            $controller->viewBuilder()->getTemplatePath()
        );
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

        $this->assertSame('widget thing is missing', (string)$result->getBody());
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

        $this->assertSame(
            'missingWidgetThing',
            $ExceptionRenderer->__debugInfo()['method']
        );
        $this->assertSame(
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

        $this->assertMatchesRegularExpression(
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

        $this->assertSame('error400', $ExceptionRenderer->__debugInfo()['template']);
        $this->assertStringContainsString('Not Found', $result);
        $this->assertStringNotContainsString('Secret info not to be leaked', $result);
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
        $this->assertStringContainsString('<b>peeled</b>', (string)$result->getBody());
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

        $this->assertSame(404, $response->getStatusCode());
        $this->assertFalse(method_exists($ExceptionRenderer, 'missingWidgetThing'), 'no method should exist.');
        $this->assertStringContainsString('coding fail', (string)$response->getBody(), 'Text should show up.');
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

        $this->assertSame(500, $result->getStatusCode());
        $this->assertStringContainsString('foul ball.', (string)$result->getBody(), 'Text should show up as its debug mode.');
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

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringNotContainsString('foul ball.', $result, 'Text should no show up.');
        $this->assertStringContainsString('Internal Error', $result, 'Generic message only.');
    }

    /**
     * test that unknown exception types with valid status codes are treated correctly.
     *
     * @return void
     */
    public function testUnknownExceptionTypeWithCodeHigherThan500()
    {
        $exception = new HttpException('foul ball.', 501);
        $ExceptionRenderer = new ExceptionRenderer($exception);
        $response = $ExceptionRenderer->render();
        $result = (string)$response->getBody();

        $this->assertSame(501, $response->getStatusCode());
        $this->assertStringContainsString('foul ball.', $result, 'Text should show up as its debug mode.');
    }

    /**
     * testerror400 method
     *
     * @return void
     */
    public function testError400()
    {
        Router::reload();

        $request = new ServerRequest(['url' => 'posts/view/1000']);
        Router::setRequest($request);

        $exception = new NotFoundException('Custom message');
        $ExceptionRenderer = new ExceptionRenderer($exception);

        $response = $ExceptionRenderer->render();
        $result = (string)$response->getBody();

        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringContainsString('<h2>Custom message</h2>', $result);
        $this->assertMatchesRegularExpression("/<strong>'.*?\/posts\/view\/1000'<\/strong>/", $result);
    }

    /**
     * testerror400 method when returning as JSON
     *
     * @return void
     */
    public function testError400AsJson()
    {
        Router::reload();

        $request = new ServerRequest(['url' => 'posts/view/1000?sort=title&direction=desc']);
        $request = $request->withHeader('Accept', 'application/json');
        $request = $request->withHeader('Content-Type', 'application/json');
        Router::setRequest($request);

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
        $this->assertSame(404, $response->getStatusCode());
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
        $this->assertStringContainsString('Custom message', (string)$result->getBody());

        $exception = new MissingActionException(['controller' => 'PostsController', 'action' => 'index']);
        $ExceptionRenderer = new ExceptionRenderer($exception);

        $result = $ExceptionRenderer->render();
        $this->assertStringContainsString('Not Found', (string)$result->getBody());
    }

    /**
     * test that error400 doesn't expose XSS
     *
     * @return void
     */
    public function testError400NoInjection()
    {
        Router::reload();

        $request = new ServerRequest(['url' => 'pages/<span id=333>pink</span></id><script>document.body.style.background = t=document.getElementById(333).innerHTML;window.alert(t);</script>']);
        Router::setRequest($request);

        $exception = new NotFoundException('Custom message');
        $ExceptionRenderer = new ExceptionRenderer($exception);

        $result = (string)$ExceptionRenderer->render()->getBody();

        $this->assertStringNotContainsString('<script>document', $result);
        $this->assertStringNotContainsString('alert(t);</script>', $result);
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
        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('<h2>An Internal Error Has Occurred.</h2>', $result);
        $this->assertStringContainsString('An Internal Error Has Occurred.</p>', $result);
    }

    /**
     * testExceptionResponseHeader method
     *
     * @return void
     */
    public function testExceptionResponseHeader()
    {
        $exception = new MethodNotAllowedException('Only allowing POST and DELETE');
        $exception->setHeader('Allow', ['POST', 'DELETE']);
        $ExceptionRenderer = new ExceptionRenderer($exception);

        $result = $ExceptionRenderer->render();
        $this->assertTrue($result->hasHeader('Allow'));
        $this->assertSame('POST,DELETE', $result->getHeaderLine('Allow'));

        $exception->setHeaders(['Allow' => 'GET']);
        $result = $ExceptionRenderer->render();
        $this->assertTrue($result->hasHeader('Allow'));
        $this->assertSame('GET', $result->getHeaderLine('Allow'));
    }

    /**
     * Tests setting exception response headers through core Exception
     *
     * @return void
     */
    public function testExceptionDeprecatedResponseHeader()
    {
        $this->deprecated(function () {
            $exception = new CakeException('Should Not Set Headers');
            $exception->responseHeader(['Allow' => 'POST, DELETE']);

            $ExceptionRenderer = new ExceptionRenderer($exception);

            $result = $ExceptionRenderer->render();
            $this->assertTrue($result->hasHeader('Allow'));
            $this->assertSame('POST, DELETE', $result->getHeaderLine('Allow'));
        });
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

        $this->assertSame(
            'missingController',
            $ExceptionRenderer->__debugInfo()['template']
        );
        $this->assertStringContainsString('Missing Controller', $result);
        $this->assertStringContainsString('<em>PostsController</em>', $result);
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

        $this->assertSame(
            'missingController',
            $ExceptionRenderer->__debugInfo()['template']
        );
        $this->assertStringContainsString('Missing Controller', $result);
        $this->assertStringContainsString('<em>PostsController</em>', $result);
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
                500,
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
            $this->assertMatchesRegularExpression($pattern, $body);
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
        $this->assertStringContainsString('widget thing is missing', $result);

        // Custom method should be called even when debug is off.
        Configure::write('debug', false);
        $exceptionRenderer = new MyCustomExceptionRenderer(new MissingWidgetThing());

        $result = (string)$exceptionRenderer->render()->getBody();
        $this->assertStringContainsString('widget thing is missing', $result);
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

        /** @var \Cake\Controller\Controller|\PHPUnit\Framework\MockObject\MockObject $controller */
        $controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->onlyMethods(['render'])
            ->getMock();
        $controller->viewBuilder()->setHelpers(['Fail', 'Boom']);
        $controller->setRequest(new ServerRequest());
        $controller->expects($this->once())
            ->method('render')
            ->with('missingHelper')
            ->will($this->throwException($exception));

        $ExceptionRenderer->setController($controller);

        $response = $ExceptionRenderer->render();
        $helpers = $controller->viewBuilder()->getHelpers();
        sort($helpers);
        $this->assertEquals([], $helpers);
        $this->assertStringContainsString('Helper class Fail', (string)$response->getBody());
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

        /** @var \Cake\Controller\Controller|\PHPUnit\Framework\MockObject\MockObject $controller */
        $controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->onlyMethods(['beforeRender'])
            ->getMock();
        $controller->request = new ServerRequest();
        $controller->expects($this->any())
            ->method('beforeRender')
            ->will($this->throwException($exception));

        $ExceptionRenderer->setController($controller);

        $response = $ExceptionRenderer->render();
        $this->assertStringContainsString('Not there, sorry', (string)$response->getBody());
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
        $controller->viewBuilder()->setHelpers(['Fail', 'Boom']);
        $controller->getEventManager()->on(
            'Controller.beforeRender',
            function (EventInterface $event) {
                $this->called = true;
                $event->getSubject()->viewBuilder()->setLayoutPath('boom');
            }
        );
        $controller->setRequest(new ServerRequest());
        $ExceptionRenderer->setController($controller);

        $response = $ExceptionRenderer->render();
        $this->assertSame('text/html', $response->getType());
        $this->assertStringContainsString('Not Found', (string)$response->getBody());
        $this->assertTrue($this->called, 'Listener added was not triggered.');
        $this->assertSame('', $controller->viewBuilder()->getLayoutPath());
        $this->assertSame('Error', $controller->viewBuilder()->getTemplatePath());
    }

    /**
     * Test that missing layout don't cause other fatal errors.
     *
     * @return void
     */
    public function testMissingLayoutRenderSafe()
    {
        $this->called = false;
        $exception = new NotFoundException();
        $ExceptionRenderer = new MyCustomExceptionRenderer($exception);

        $controller = new Controller();
        $controller->getEventManager()->on(
            'Controller.beforeRender',
            function (EventInterface $event) {
                $this->called = true;
                $event->getSubject()->viewBuilder()->setTemplatePath('Error');
                $event->getSubject()->viewBuilder()->setLayout('does-not-exist');
            }
        );
        $controller->setRequest(new ServerRequest());
        $ExceptionRenderer->setController($controller);

        $response = $ExceptionRenderer->render();
        $this->assertSame('text/html', $response->getType());
        $this->assertStringContainsString('Not Found', (string)$response->getBody());
        $this->assertTrue($this->called, 'Listener added was not triggered.');
        $this->assertSame('', $controller->viewBuilder()->getLayoutPath());
        $this->assertSame('Error', $controller->viewBuilder()->getTemplatePath());
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

        /** @var \Cake\Controller\Controller|\PHPUnit\Framework\MockObject\MockObject $controller */
        $controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->onlyMethods(['render'])
            ->getMock();
        $controller->setPlugin('TestPlugin');
        $controller->request = new ServerRequest();

        $exception = new MissingPluginException(['plugin' => 'TestPlugin']);
        $controller->expects($this->once())
            ->method('render')
            ->with('error400')
            ->will($this->throwException($exception));

        $ExceptionRenderer->setController($controller);

        $response = $ExceptionRenderer->render();
        $body = (string)$response->getBody();
        $this->assertStringNotContainsString('test plugin error500', $body);
        $this->assertStringContainsString('Not Found', $body);
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

        /** @var \Cake\Controller\Controller|\PHPUnit\Framework\MockObject\MockObject $controller */
        $controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->onlyMethods(['render'])
            ->getMock();
        $controller->setPlugin('TestPlugin');
        $controller->request = new ServerRequest();

        $exception = new MissingPluginException(['plugin' => 'TestPluginTwo']);
        $controller->expects($this->once())
            ->method('render')
            ->with('error400')
            ->will($this->throwException($exception));

        $ExceptionRenderer->setController($controller);

        $response = $ExceptionRenderer->render();
        $body = (string)$response->getBody();
        $this->assertStringContainsString('test plugin error500', $body);
        $this->assertStringContainsString('Not Found', $body);
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
        $this->assertNull(Router::getRequest());

        $exception = new Exception('Terrible');
        $ExceptionRenderer = new ExceptionRenderer($exception);
        $result = $ExceptionRenderer->render();

        $this->assertStringContainsString('Internal Error', (string)$result->getBody());
        $this->assertSame(500, $result->getStatusCode());
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
        Router::setRequest($routerRequest);

        $exceptionRenderer = new ExceptionRenderer(new Exception('Terrible'), new ServerRequest());
        $exceptionRenderer->render();
        $properties = $exceptionRenderer->__debugInfo();

        /** @var \Cake\Http\ServerRequest $request */
        $request = $properties['controller']->getRequest();
        foreach (['controller', 'action', '_ext'] as $key) {
            $this->assertSame($routerRequest->getParam($key), $request->getParam($key));
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
        $listener = function (EventInterface $event) use (&$fired) {
            $fired[] = $event->getName();
        };
        $events = EventManager::instance();
        $events->on('Controller.shutdown', $listener);

        $exception = new Exception('Terrible');
        $renderer = new ExceptionRenderer($exception);
        $renderer->render();

        $expected = ['Controller.shutdown'];
        $this->assertEquals($expected, $fired);
    }

    /**
     * test that subclass methods fire shutdown events.
     *
     * @return void
     */
    public function testSubclassTriggerShutdownEvents()
    {
        $fired = [];
        $listener = function (EventInterface $event) use (&$fired) {
            $fired[] = $event->getName();
        };
        $events = EventManager::instance();
        $events->on('Controller.shutdown', $listener);

        $exception = new MissingWidgetThingException('Widget not found');
        $renderer = new MyCustomExceptionRenderer($exception);
        $renderer->render();

        $expected = ['Controller.shutdown'];
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

        $this->assertSame(500, $response->getStatusCode());
        $result = (string)$response->getBody();
        $this->assertStringContainsString('Database Error', $result);
        $this->assertStringContainsString('There was an error in the SQL query', $result);
        $this->assertStringContainsString(h('SELECT * from poo_query < 5 and :seven'), $result);
        $this->assertStringContainsString("'seven' => (int) 7", $result);
    }
}
