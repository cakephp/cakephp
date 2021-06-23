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
 * @link          https://cakephp.org CakePHP Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\Uri;
use ReflectionFunction;
use TestApp\Controller\Admin\PostsController;
use TestApp\Controller\ArticlesController;
use TestApp\Controller\TestController;
use TestApp\Model\Table\ArticlesTable;
use TestPlugin\Controller\TestPluginController;

/**
 * ControllerTest class
 */
class ControllerTest extends TestCase
{
    /**
     * fixtures property
     *
     * @var array
     */
    protected $fixtures = [
        'core.Comments',
        'core.Posts',
    ];

    /**
     * reset environment.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        static::setAppNamespace();
        Router::reload();
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
    }

    /**
     * test autoload modelClass
     *
     * @return void
     */
    public function testTableAutoload(): void
    {
        $request = new ServerRequest(['url' => 'controller/posts/index']);
        $Controller = new Controller($request, new Response());
        $Controller->modelClass = 'SiteArticles';

        $this->assertFalse(isset($Controller->Articles));
        $this->assertInstanceOf(
            'Cake\ORM\Table',
            $Controller->SiteArticles
        );
        unset($Controller->SiteArticles);

        $Controller->modelClass = 'Articles';

        $this->assertFalse(isset($Controller->SiteArticles));
        $this->assertInstanceOf(
            'TestApp\Model\Table\ArticlesTable',
            $Controller->Articles
        );
    }

    /**
     * testUndefinedPropertyError
     *
     * @return void
     */
    public function testUndefinedPropertyError()
    {
        $controller = new Controller();

        $controller->Bar = true;
        $this->assertTrue($controller->Bar);

        $this->expectNotice();
        $this->expectNoticeMessage(sprintf(
            'Undefined property: Controller::$Foo in %s on line %s',
            __FILE__,
            __LINE__ + 2
        ));
        $controller->Foo->baz();
    }

    /**
     * testLoadModel method
     *
     * @return void
     */
    public function testLoadModel(): void
    {
        $request = new ServerRequest(['url' => 'controller/posts/index']);
        $Controller = new Controller($request, new Response());

        $this->assertFalse(isset($Controller->Articles));

        $result = $Controller->loadModel('Articles');
        $this->assertInstanceOf(
            'TestApp\Model\Table\ArticlesTable',
            $result
        );
        $this->assertInstanceOf(
            'TestApp\Model\Table\ArticlesTable',
            $Controller->Articles
        );
    }

    /**
     * @link https://github.com/cakephp/cakephp/issues/14804
     * @return void
     */
    public function testAutoLoadModelUsingFqcn(): void
    {
        Configure::write('App.namespace', 'TestApp');
        $Controller = new ArticlesController(new ServerRequest(), new Response());

        $this->assertInstanceOf(ArticlesTable::class, $Controller->Articles);

        Configure::write('App.namespace', 'App');
    }

    /**
     * testLoadModel method from a plugin controller
     *
     * @return void
     */
    public function testLoadModelInPlugins(): void
    {
        $this->loadPlugins(['TestPlugin']);

        $Controller = new TestPluginController();
        $Controller->setPlugin('TestPlugin');

        $this->assertFalse(isset($Controller->TestPluginComments));

        $result = $Controller->loadModel('TestPlugin.TestPluginComments');
        $this->assertInstanceOf(
            'TestPlugin\Model\Table\TestPluginCommentsTable',
            $result
        );
        $this->assertInstanceOf(
            'TestPlugin\Model\Table\TestPluginCommentsTable',
            $Controller->TestPluginComments
        );
    }

    /**
     * Test that the constructor sets modelClass properly.
     *
     * @return void
     */
    public function testConstructSetModelClass(): void
    {
        $this->loadPlugins(['TestPlugin']);

        $request = new ServerRequest();
        $response = new Response();
        $controller = new \TestApp\Controller\PostsController($request, $response);
        $this->assertInstanceOf('Cake\ORM\Table', $controller->loadModel());
        $this->assertInstanceOf('Cake\ORM\Table', $controller->Posts);

        $controller = new \TestApp\Controller\Admin\PostsController($request, $response);
        $this->assertInstanceOf('Cake\ORM\Table', $controller->loadModel());
        $this->assertInstanceOf('Cake\ORM\Table', $controller->Posts);

        $request = $request->withParam('plugin', 'TestPlugin');
        $controller = new \TestPlugin\Controller\Admin\CommentsController($request, $response);
        $this->assertInstanceOf('TestPlugin\Model\Table\CommentsTable', $controller->loadModel());
        $this->assertInstanceOf('TestPlugin\Model\Table\CommentsTable', $controller->Comments);
    }

    /**
     * testConstructClassesWithComponents method
     *
     * @return void
     */
    public function testConstructClassesWithComponents(): void
    {
        $this->loadPlugins(['TestPlugin']);

        $Controller = new TestPluginController(new ServerRequest(), new Response());
        $Controller->loadComponent('TestPlugin.Other');

        $this->assertInstanceOf('TestPlugin\Controller\Component\OtherComponent', $Controller->Other);
    }

    /**
     * testRender method
     *
     * @return void
     */
    public function testRender(): void
    {
        $this->loadPlugins(['TestPlugin']);

        $request = new ServerRequest([
            'url' => 'controller_posts/index',
            'params' => [
                'action' => 'header',
            ],
        ]);

        $Controller = new Controller($request, new Response());
        $Controller->viewBuilder()->setTemplatePath('Posts');

        $result = $Controller->render('index');
        $this->assertMatchesRegularExpression('/posts index/', (string)$result);

        $Controller->viewBuilder()->setTemplate('index');
        $result = $Controller->render();
        $this->assertMatchesRegularExpression('/posts index/', (string)$result);

        $result = $Controller->render('/element/test_element');
        $this->assertMatchesRegularExpression('/this is the test element/', (string)$result);
    }

    /**
     * test view rendering changing response
     *
     * @return void
     */
    public function testRenderViewChangesResponse(): void
    {
        $request = new ServerRequest([
            'url' => 'controller_posts/index',
            'params' => [
                'action' => 'header',
            ],
        ]);

        $controller = new Controller($request, new Response());
        $controller->viewBuilder()->setTemplatePath('Posts');

        $result = $controller->render('header');
        $this->assertStringContainsString('header template', (string)$result);
        $this->assertTrue($controller->getResponse()->hasHeader('X-view-template'));
        $this->assertSame('yes', $controller->getResponse()->getHeaderLine('X-view-template'));
    }

    /**
     * test that a component beforeRender can change the controller view class.
     *
     * @return void
     */
    public function testBeforeRenderCallbackChangingViewClass(): void
    {
        $Controller = new Controller(new ServerRequest(), new Response());

        $Controller->getEventManager()->on('Controller.beforeRender', function (EventInterface $event): void {
            $controller = $event->getSubject();
            $controller->viewBuilder()->setClassName('Json');
        });

        $Controller->set([
            'test' => 'value',
        ]);
        $Controller->viewBuilder()->setOption('serialize', ['test']);
        $debug = Configure::read('debug');
        Configure::write('debug', false);
        $result = $Controller->render('index');
        $this->assertSame('{"test":"value"}', (string)$result->getBody());
        Configure::write('debug', $debug);
    }

    /**
     * test that a component beforeRender can change the controller view class.
     *
     * @return void
     */
    public function testBeforeRenderEventCancelsRender(): void
    {
        $Controller = new Controller(new ServerRequest(), new Response());

        $Controller->getEventManager()->on('Controller.beforeRender', function (EventInterface $event) {
            return false;
        });

        $result = $Controller->render('index');
        $this->assertInstanceOf('Cake\Http\Response', $result);
    }

    public function testControllerRedirect()
    {
        $Controller = new Controller();
        $uri = new Uri('/foo/bar');
        $response = $Controller->redirect($uri);
        $this->assertSame('http://localhost/foo/bar', $response->getHeaderLine('Location'));

        $Controller = new Controller();
        $uri = new Uri('http://cakephp.org/foo/bar');
        $response = $Controller->redirect($uri);
        $this->assertSame('http://cakephp.org/foo/bar', $response->getHeaderLine('Location'));
    }

    /**
     * Generates status codes for redirect test.
     *
     * @return array
     */
    public static function statusCodeProvider(): array
    {
        return [
            [300, 'Multiple Choices'],
            [301, 'Moved Permanently'],
            [302, 'Found'],
            [303, 'See Other'],
            [304, 'Not Modified'],
            [305, 'Use Proxy'],
            [307, 'Temporary Redirect'],
            [403, 'Forbidden'],
        ];
    }

    /**
     * testRedirect method
     *
     * @dataProvider statusCodeProvider
     * @return void
     */
    public function testRedirectByCode($code, $msg): void
    {
        $Controller = new Controller(null, new Response());

        $response = $Controller->redirect('http://cakephp.org', (int)$code);
        $this->assertSame($response, $Controller->getResponse());
        $this->assertEquals($code, $response->getStatusCode());
        $this->assertSame('http://cakephp.org', $response->getHeaderLine('Location'));
        $this->assertFalse($Controller->isAutoRenderEnabled());
    }

    /**
     * test that beforeRedirect callbacks can set the URL that is being redirected to.
     *
     * @return void
     */
    public function testRedirectBeforeRedirectModifyingUrl(): void
    {
        $Controller = new Controller(null, new Response());

        $Controller->getEventManager()->on('Controller.beforeRedirect', function (EventInterface $event, $url, Response $response): void {
            $controller = $event->getSubject();
            $controller->setResponse($response->withLocation('https://book.cakephp.org'));
        });

        $response = $Controller->redirect('http://cakephp.org', 301);
        $this->assertSame('https://book.cakephp.org', $response->getHeaderLine('Location'));
        $this->assertSame(301, $response->getStatusCode());
    }

    /**
     * test that beforeRedirect callback returning null doesn't affect things.
     *
     * @return void
     */
    public function testRedirectBeforeRedirectModifyingStatusCode(): void
    {
        $response = new Response();
        $Controller = new Controller(null, $response);

        $Controller->getEventManager()->on('Controller.beforeRedirect', function (EventInterface $event, $url, Response $response): void {
            $controller = $event->getSubject();
            $controller->setResponse($response->withStatus(302));
        });

        $response = $Controller->redirect('http://cakephp.org', 301);

        $this->assertSame('http://cakephp.org', $response->getHeaderLine('Location'));
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testRedirectBeforeRedirectListenerReturnResponse(): void
    {
        $Controller = new Controller(null, new Response());

        $newResponse = new Response();
        $Controller->getEventManager()->on('Controller.beforeRedirect', function (EventInterface $event, $url, Response $response) use ($newResponse) {
            return $newResponse;
        });

        $result = $Controller->redirect('http://cakephp.org');
        $this->assertSame($newResponse, $result);
        $this->assertSame($newResponse, $Controller->getResponse());
    }

    /**
     * testReferer method
     *
     * @return void
     */
    public function testReferer(): void
    {
        $request = new ServerRequest([
            'environment' => ['HTTP_REFERER' => 'http://localhost/posts/index'],
        ]);
        $Controller = new Controller($request);
        $result = $Controller->referer();
        $this->assertSame('/posts/index', $result);

        $request = new ServerRequest([
            'environment' => ['HTTP_REFERER' => 'http://localhost/posts/index'],
        ]);
        $Controller = new Controller($request);
        $result = $Controller->referer(['controller' => 'Posts', 'action' => 'index'], true);
        $this->assertSame('/posts/index', $result);

        $request = $this->getMockBuilder('Cake\Http\ServerRequest')
            ->onlyMethods(['referer'])
            ->getMock();

        $request = new ServerRequest([
            'environment' => ['HTTP_REFERER' => 'http://localhost/posts/index'],
        ]);
        $Controller = new Controller($request);
        $result = $Controller->referer(null, false);
        $this->assertSame('http://localhost/posts/index', $result);

        $Controller = new Controller(null);
        $result = $Controller->referer('/', false);
        $this->assertSame('http://localhost/', $result);
    }

    /**
     * Test that the referer is not absolute if it is '/'.
     *
     * This avoids the base path being applied twice on string urls.
     *
     * @return void
     */
    public function testRefererSlash(): void
    {
        $request = new ServerRequest();
        $request = $request->withAttribute('base', '/base');
        Router::setRequest($request);

        $controller = new Controller($request);
        $result = $controller->referer('/', true);
        $this->assertSame('/', $result);

        $controller = new Controller($request);
        $result = $controller->referer('/some/path', true);
        $this->assertSame('/some/path', $result);
    }

    /**
     * testSetAction method
     *
     * @return void
     * @group deprecated
     */
    public function testSetAction(): void
    {
        $this->deprecated(function () {
            $request = new ServerRequest(['url' => 'controller/posts/index']);

            $TestController = new TestController($request);
            $TestController->setAction('view', 1, 2);
            $expected = ['testId' => 1, 'test2Id' => 2];
            $this->assertSame($expected, $TestController->getRequest()->getData());
            $this->assertSame('view', $TestController->getRequest()->getParam('action'));
        });
    }

    /**
     * Tests that the startup process calls the correct functions
     *
     * @return void
     */
    public function testStartupProcess(): void
    {
        $eventManager = $this->getMockBuilder('Cake\Event\EventManagerInterface')->getMock();
        $controller = new Controller(null, null, null, $eventManager);

        $eventManager
            ->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$this->callback(function (EventInterface $event) {
                    return $event->getName() === 'Controller.initialize';
                })],
                [$this->callback(function (EventInterface $event) {
                    return $event->getName() === 'Controller.startup';
                })]
            )
            ->will($this->returnValue(new Event('stub')));

        $controller->startupProcess();
    }

    /**
     * Tests that the shutdown process calls the correct functions
     *
     * @return void
     */
    public function testShutdownProcess(): void
    {
        $eventManager = $this->getMockBuilder('Cake\Event\EventManagerInterface')->getMock();
        $controller = new Controller(null, null, null, $eventManager);

        $eventManager->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (EventInterface $event) {
                return $event->getName() === 'Controller.shutdown';
            }))
            ->will($this->returnValue(new Event('stub')));

        $controller->shutdownProcess();
    }

    /**
     * test using Controller::paginate()
     *
     * @return void
     */
    public function testPaginate(): void
    {
        $request = new ServerRequest(['url' => 'controller_posts/index']);
        $response = new Response();

        $Controller = new Controller($request, $response);
        $Controller->setRequest($Controller->getRequest()->withQueryParams([
            'posts' => [
                'page' => 2,
                'limit' => 2,
            ],
        ]));

        $this->assertEquals([], $Controller->paginate);

        $this->assertNotContains('Paginator', $Controller->viewBuilder()->getHelpers());
        $this->assertArrayNotHasKey('Paginator', $Controller->viewBuilder()->getHelpers());

        $results = $Controller->paginate('Posts');
        $this->assertInstanceOf('Cake\Datasource\ResultSetInterface', $results);
        $this->assertCount(3, $results);

        $results = $Controller->paginate($this->getTableLocator()->get('Posts'));
        $this->assertInstanceOf('Cake\Datasource\ResultSetInterface', $results);
        $this->assertCount(3, $results);

        $paging = $Controller->getRequest()->getAttribute('paging');
        $this->assertSame($paging['Posts']['page'], 1);
        $this->assertSame($paging['Posts']['pageCount'], 1);
        $this->assertFalse($paging['Posts']['prevPage']);
        $this->assertFalse($paging['Posts']['nextPage']);
        $this->assertNull($paging['Posts']['scope']);

        $results = $Controller->paginate($this->getTableLocator()->get('Posts'), ['scope' => 'posts']);
        $this->assertInstanceOf('Cake\Datasource\ResultSetInterface', $results);
        $this->assertCount(1, $results);

        $paging = $Controller->getRequest()->getAttribute('paging');
        $this->assertSame($paging['Posts']['page'], 2);
        $this->assertSame($paging['Posts']['pageCount'], 2);
        $this->assertTrue($paging['Posts']['prevPage']);
        $this->assertFalse($paging['Posts']['nextPage']);
        $this->assertSame($paging['Posts']['scope'], 'posts');
    }

    /**
     * test that paginate uses modelClass property.
     *
     * @return void
     */
    public function testPaginateUsesModelClass(): void
    {
        $request = new ServerRequest([
            'url' => 'controller_posts/index',
        ]);
        $response = new Response();

        $Controller = new Controller($request, $response);
        $Controller->modelClass = 'Posts';
        $results = $Controller->paginate();

        $this->assertInstanceOf('Cake\Datasource\ResultSetInterface', $results);
    }

    /**
     * testMissingAction method
     *
     * @return void
     */
    public function testGetActionMissingAction(): void
    {
        $this->expectException(\Cake\Controller\Exception\MissingActionException::class);
        $this->expectExceptionMessage('Action TestController::missing() could not be found, or is not accessible.');
        $url = new ServerRequest([
            'url' => 'test/missing',
            'params' => ['controller' => 'Test', 'action' => 'missing'],
        ]);
        $response = new Response();

        $Controller = new TestController($url, $response);
        $Controller->getAction();
    }

    /**
     * test invoking private methods.
     *
     * @return void
     */
    public function testGetActionPrivate(): void
    {
        $this->expectException(\Cake\Controller\Exception\MissingActionException::class);
        $this->expectExceptionMessage('Action TestController::private_m() could not be found, or is not accessible.');
        $url = new ServerRequest([
            'url' => 'test/private_m/',
            'params' => ['controller' => 'Test', 'action' => 'private_m'],
        ]);
        $response = new Response();

        $Controller = new TestController($url, $response);
        $Controller->getAction();
    }

    /**
     * test invoking protected methods.
     *
     * @return void
     */
    public function testGetActionProtected(): void
    {
        $this->expectException(\Cake\Controller\Exception\MissingActionException::class);
        $this->expectExceptionMessage('Action TestController::protected_m() could not be found, or is not accessible.');
        $url = new ServerRequest([
            'url' => 'test/protected_m/',
            'params' => ['controller' => 'Test', 'action' => 'protected_m'],
        ]);
        $response = new Response();

        $Controller = new TestController($url, $response);
        $Controller->getAction();
    }

    /**
     * test invoking controller methods.
     *
     * @return void
     */
    public function testGetActionBaseMethods(): void
    {
        $this->expectException(\Cake\Controller\Exception\MissingActionException::class);
        $this->expectExceptionMessage('Action TestController::redirect() could not be found, or is not accessible.');
        $url = new ServerRequest([
            'url' => 'test/redirect/',
            'params' => ['controller' => 'Test', 'action' => 'redirect'],
        ]);
        $response = new Response();

        $Controller = new TestController($url, $response);
        $Controller->getAction();
    }

    /**
     * test invoking action method with mismatched casing.
     *
     * @return void
     */
    public function testGetActionMethodCasing(): void
    {
        $this->expectException(\Cake\Controller\Exception\MissingActionException::class);
        $this->expectExceptionMessage('Action TestController::RETURNER() could not be found, or is not accessible.');
        $url = new ServerRequest([
            'url' => 'test/RETURNER/',
            'params' => ['controller' => 'Test', 'action' => 'RETURNER'],
        ]);
        $response = new Response();

        $Controller = new TestController($url, $response);
        $Controller->getAction();
    }

    public function testGetActionArgsReflection(): void
    {
        $request = new ServerRequest([
            'url' => 'test/reflection/1',
            'params' => [
                'controller' => 'Test',
                'action' => 'reflection',
                'pass' => ['1'],
            ],
        ]);
        $controller = new TestController($request, new Response());

        $closure = $controller->getAction();
        $args = (new ReflectionFunction($closure))->getParameters();

        $this->assertSame('Parameter #0 [ <required> $passed ]', (string)$args[0]);
        $this->assertSame('Parameter #1 [ <required> Cake\ORM\Table $table ]', (string)$args[1]);
    }

    /**
     * test invoking controller methods.
     *
     * @return void
     */
    public function testInvokeActionReturnValue(): void
    {
        $url = new ServerRequest([
            'url' => 'test/returner/',
            'params' => [
                'controller' => 'Test',
                'action' => 'returner',
                'pass' => [],
            ],
        ]);
        $response = new Response();

        $Controller = new TestController($url, $response);
        $Controller->invokeAction($Controller->getAction(), $Controller->getRequest()->getParam('pass'));

        $this->assertSame('I am from the controller.', (string)$Controller->getResponse());
    }

    /**
     * test invoking controller methods with passed params
     *
     * @return void
     */
    public function testInvokeActionWithPassedParams(): void
    {
        $request = new ServerRequest([
            'url' => 'test/index/1/2',
            'params' => [
                'controller' => 'Test',
                'action' => 'index',
                'pass' => ['param1' => '1', 'param2' => '2'],
            ],
        ]);
        $controller = new TestController($request, new Response());
        $controller->disableAutoRender();
        $controller->invokeAction($controller->getAction(), array_values($controller->getRequest()->getParam('pass')));

        $this->assertEquals(
            ['testId' => '1', 'test2Id' => '2'],
            $controller->getRequest()->getData()
        );
    }

    /**
     * test invalid return value from action method.
     *
     * @return void
     */
    public function testInvokeActionException()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage(
            'Controller actions can only return ResponseInterface instance or null. '
                . 'Got string instead.'
        );

        $url = new ServerRequest([
            'url' => 'test/willCauseException',
            'params' => [
                'controller' => 'Test',
                'action' => 'willCauseException',
                'pass' => [],
            ],
        ]);
        $response = new Response();

        $Controller = new TestController($url, $response);
        $Controller->invokeAction($Controller->getAction(), $Controller->getRequest()->getParam('pass'));
    }

    /**
     * test that a classes namespace is used in the viewPath.
     *
     * @return void
     */
    public function testViewPathConventions(): void
    {
        $request = new ServerRequest([
            'url' => 'admin/posts',
            'params' => ['prefix' => 'Admin'],
        ]);
        $response = new Response();
        $Controller = new \TestApp\Controller\Admin\PostsController($request, $response);
        $Controller->getEventManager()->on('Controller.beforeRender', function (EventInterface $e) {
            return $e->getSubject()->getResponse();
        });
        $Controller->render();
        $this->assertSame('Admin' . DS . 'Posts', $Controller->viewBuilder()->getTemplatePath());

        $request = $request->withParam('prefix', 'admin/super');
        $response = new Response();
        $Controller = new \TestApp\Controller\Admin\PostsController($request, $response);
        $Controller->getEventManager()->on('Controller.beforeRender', function (EventInterface $e) {
            return $e->getSubject()->getResponse();
        });
        $Controller->render();
        $this->assertSame('Admin' . DS . 'Super' . DS . 'Posts', $Controller->viewBuilder()->getTemplatePath());

        $request = new ServerRequest([
            'url' => 'pages/home',
            'params' => [
                'prefix' => false,
            ],
        ]);
        $Controller = new \TestApp\Controller\PagesController($request, $response);
        $Controller->getEventManager()->on('Controller.beforeRender', function (EventInterface $e) {
            return $e->getSubject()->getResponse();
        });
        $Controller->render();
        $this->assertSame('Pages', $Controller->viewBuilder()->getTemplatePath());
    }

    /**
     * Test the components() method.
     *
     * @return void
     */
    public function testComponents(): void
    {
        $request = new ServerRequest(['url' => '/']);
        $response = new Response();

        $controller = new TestController($request, $response);
        $this->assertInstanceOf('Cake\Controller\ComponentRegistry', $controller->components());

        $result = $controller->components();
        $this->assertSame($result, $controller->components());
    }

    /**
     * Test the components property errors
     *
     * @return void
     */
    public function testComponentsPropertyError(): void
    {
        $this->expectWarning();
        $request = new ServerRequest(['url' => '/']);
        $response = new Response();

        $controller = new TestController($request, $response);
        $controller->components = ['Flash'];
    }

    /**
     * Test the helpers property errors
     *
     * @return void
     */
    public function testHelpersPropertyError(): void
    {
        $this->expectWarning();
        $request = new ServerRequest(['url' => '/']);
        $response = new Response();

        $controller = new TestController($request, $response);
        $controller->helpers = ['Flash'];
    }

    /**
     * Test the components() method with the custom ObjectRegistry.
     *
     * @return void
     */
    public function testComponentsWithCustomRegistry(): void
    {
        $request = new ServerRequest(['url' => '/']);
        $response = new Response();
        $componentRegistry = $this->getMockBuilder('Cake\Controller\ComponentRegistry')
            ->addMethods(['offsetGet'])
            ->getMock();

        $controller = new TestController($request, $response, null, null, $componentRegistry);
        $this->assertInstanceOf(get_class($componentRegistry), $controller->components());

        $result = $controller->components();
        $this->assertSame($result, $controller->components());
    }

    /**
     * Test adding a component
     *
     * @return void
     */
    public function testLoadComponent(): void
    {
        $request = new ServerRequest(['url' => '/']);
        $response = new Response();

        $controller = new TestController($request, $response);
        $result = $controller->loadComponent('Paginator');
        $this->assertInstanceOf('Cake\Controller\Component\PaginatorComponent', $result);
        $this->assertSame($result, $controller->Paginator);

        $registry = $controller->components();
        $this->assertTrue(isset($registry->Paginator));
    }

    /**
     * Test adding a component that is a duplicate.
     *
     * @return void
     */
    public function testLoadComponentDuplicate(): void
    {
        $request = new ServerRequest(['url' => '/']);
        $response = new Response();

        $controller = new TestController($request, $response);
        $this->assertNotEmpty($controller->loadComponent('Paginator'));
        $this->assertNotEmpty($controller->loadComponent('Paginator'));
        try {
            $controller->loadComponent('Paginator', ['bad' => 'settings']);
            $this->fail('No exception');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('The "Paginator" alias has already been loaded', $e->getMessage());
        }
    }

    /**
     * Test the isAction method.
     *
     * @return void
     */
    public function testIsAction(): void
    {
        $request = new ServerRequest(['url' => '/']);
        $response = new Response();
        $controller = new TestController($request, $response);

        $this->assertFalse($controller->isAction('redirect'));
        $this->assertFalse($controller->isAction('beforeFilter'));
        $this->assertTrue($controller->isAction('index'));
    }

    /**
     * Test that view variables are being set after the beforeRender event gets dispatched
     *
     * @return void
     */
    public function testBeforeRenderViewVariables(): void
    {
        $controller = new PostsController();

        $controller->getEventManager()->on('Controller.beforeRender', function (EventInterface $event): void {
            /** @var \Cake\Controller\Controller $controller */
            $controller = $event->getSubject();

            $controller->set('testVariable', 'test');
        });

        $controller->dispatchEvent('Controller.beforeRender');
        $view = $controller->createView();

        $this->assertNotEmpty('testVariable', $view->get('testVariable'));
    }

    /**
     * Test that render()'s arguments are available in beforeRender() through view builder.
     *
     * @return void
     */
    public function testBeforeRenderTemplateAndLayout()
    {
        $Controller = new Controller(new ServerRequest(), new Response());
        $Controller->getEventManager()->on('Controller.beforeRender', function ($event) {
            $this->assertSame(
                '/Element/test_element',
                $event->getSubject()->viewBuilder()->getTemplate()
            );
            $this->assertSame(
                'default',
                $event->getSubject()->viewBuilder()->getLayout()
            );

            $event->getSubject()->viewBuilder()
                ->setTemplatePath('Posts')
                ->setTemplate('index');
        });

        $result = $Controller->render('/Element/test_element', 'default');
        $this->assertMatchesRegularExpression('/posts index/', (string)$result);
    }

    /**
     * Test name getter and setter.
     *
     * @return void
     */
    public function testName(): void
    {
        $controller = new PostsController();
        $this->assertSame('Posts', $controller->getName());

        $this->assertSame($controller, $controller->setName('Articles'));
        $this->assertSame('Articles', $controller->getName());
    }

    /**
     * Test plugin getter and setter.
     *
     * @return void
     */
    public function testPlugin(): void
    {
        $controller = new PostsController();
        $this->assertNull($controller->getPlugin());

        $this->assertSame($controller, $controller->setPlugin('Articles'));
        $this->assertSame('Articles', $controller->getPlugin());
    }

    /**
     * Test request getter and setter.
     *
     * @return void
     */
    public function testRequest(): void
    {
        $controller = new PostsController();
        $this->assertInstanceOf(ServerRequest::class, $controller->getRequest());

        $request = new ServerRequest([
            'params' => [
                'plugin' => 'Posts',
                'pass' => [
                    'foo',
                    'bar',
                ],
            ],
        ]);
        $this->assertSame($controller, $controller->setRequest($request));
        $this->assertSame($request, $controller->getRequest());

        $this->assertSame('Posts', $controller->getRequest()->getParam('plugin'));
        $this->assertEquals(['foo', 'bar'], $controller->getRequest()->getParam('pass'));
    }

    /**
     * Test response getter and setter.
     *
     * @return void
     */
    public function testResponse(): void
    {
        $controller = new PostsController();
        $this->assertInstanceOf(Response::class, $controller->getResponse());

        $response = new Response();
        $this->assertSame($controller, $controller->setResponse($response));
        $this->assertSame($response, $controller->getResponse());
    }

    /**
     * Test autoRender getter and setter.
     *
     * @return void
     */
    public function testAutoRender(): void
    {
        $controller = new PostsController();
        $this->assertTrue($controller->isAutoRenderEnabled());

        $this->assertSame($controller, $controller->disableAutoRender());
        $this->assertFalse($controller->isAutoRenderEnabled());

        $this->assertSame($controller, $controller->enableAutoRender());
        $this->assertTrue($controller->isAutoRenderEnabled());
    }
}
