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

use AssertionError;
use Cake\Controller\Component\FormProtectionComponent;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Controller\Exception\MissingActionException;
use Cake\Core\Configure;
use Cake\Datasource\Paging\PaginatedInterface;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\Event\EventManagerInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use Cake\View\XmlView;
use InvalidArgumentException;
use Laminas\Diactoros\Uri;
use ReflectionFunction;
use RuntimeException;
use TestApp\Controller\Admin\PostsController as AdminPostsController;
use TestApp\Controller\ArticlesController;
use TestApp\Controller\ContentTypesController;
use TestApp\Controller\PagesController;
use TestApp\Controller\PostsController;
use TestApp\Controller\TestController;
use TestApp\Controller\WithDefaultTableController;
use TestApp\Model\Table\ArticlesTable;
use TestApp\Model\Table\PostsTable;
use TestApp\View\PlainTextView;
use TestPlugin\Controller\Admin\CommentsController;
use TestPlugin\Controller\Component\OtherComponent;
use TestPlugin\Controller\TestPluginController;
use TestPlugin\Model\Table\CommentsTable;
use TestPlugin\Model\Table\TestPluginCommentsTable;

/**
 * ControllerTest class
 */
class ControllerTest extends TestCase
{
    /**
     * fixtures property
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'core.Comments',
        'core.Posts',
    ];

    /**
     * reset environment.
     */
    public function setUp(): void
    {
        parent::setUp();

        static::setAppNamespace();
        Router::reload();
    }

    /**
     * tearDown
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->clearPlugins();
    }

    /**
     * test autoload default table
     */
    public function testTableAutoload(): void
    {
        $request = new ServerRequest(['url' => 'controller/posts/index']);
        $Controller = new Controller($request, 'Articles');

        $this->assertInstanceOf(
            ArticlesTable::class,
            $Controller->Articles
        );
    }

    /**
     * testUndefinedPropertyError
     */
    public function testUndefinedPropertyError(): void
    {
        $this->expectNoticeMessageMatches('/Undefined property `Controller::\$Foo` in `.*` on line \d+/', function () {
            $controller = new Controller(new ServerRequest());
            $controller->Foo->baz();
        });
    }

    /**
     * testGetTable method
     */
    public function testGetTable(): void
    {
        $request = new ServerRequest(['url' => 'controller/posts/index']);
        $Controller = new Controller($request);

        $this->assertFalse(isset($Controller->Articles));

        $result = $Controller->fetchTable('Articles');
        $this->assertInstanceOf(
            ArticlesTable::class,
            $result
        );
    }

    public function testAutoLoadModelUsingDefaultTable()
    {
        Configure::write('App.namespace', 'TestApp');
        $Controller = new WithDefaultTableController(new ServerRequest());

        $this->assertInstanceOf(PostsTable::class, $Controller->Posts);

        Configure::write('App.namespace', 'App');
    }

    /**
     * @link https://github.com/cakephp/cakephp/issues/14804
     */
    public function testAutoLoadTableUsingFqcn(): void
    {
        Configure::write('App.namespace', 'TestApp');
        $Controller = new ArticlesController(new ServerRequest());

        $this->assertInstanceOf(ArticlesTable::class, $Controller->fetchTable());

        Configure::write('App.namespace', 'App');
    }

    public function testGetTableInPlugins(): void
    {
        $this->loadPlugins(['TestPlugin']);

        $Controller = new TestPluginController(new ServerRequest());
        $Controller->setPlugin('TestPlugin');

        $this->assertFalse(isset($Controller->TestPluginComments));

        $result = $Controller->fetchTable('TestPlugin.TestPluginComments');
        $this->assertInstanceOf(
            TestPluginCommentsTable::class,
            $result
        );
    }

    /**
     * Test that the constructor sets defaultTable properly.
     */
    public function testConstructSetDefaultTable(): void
    {
        $this->loadPlugins(['TestPlugin']);

        $request = new ServerRequest();
        $controller = new PostsController($request);
        $this->assertInstanceOf(PostsTable::class, $controller->fetchTable());

        $controller = new AdminPostsController($request);
        $this->assertInstanceOf(PostsTable::class, $controller->fetchTable());

        $request = $request->withParam('plugin', 'TestPlugin');
        $controller = new CommentsController($request);
        $this->assertInstanceOf(CommentsTable::class, $controller->fetchTable());
    }

    /**
     * testConstructClassesWithComponents method
     */
    public function testConstructClassesWithComponents(): void
    {
        $this->loadPlugins(['TestPlugin']);

        $Controller = new TestPluginController(new ServerRequest());
        $Controller->loadComponent('TestPlugin.Other');

        $this->assertInstanceOf(OtherComponent::class, $Controller->Other);
    }

    /**
     * testRender method
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

        $Controller = new Controller($request);
        $Controller->viewBuilder()->setTemplatePath('Posts');

        $result = $Controller->render('index');
        $this->assertMatchesRegularExpression('/posts index/', (string)$result);

        $Controller->viewBuilder()->setTemplate('index');
        $result = $Controller->render();
        $this->assertMatchesRegularExpression('/posts index/', (string)$result);

        $result = $Controller->render('/element/test_element');
        $this->assertMatchesRegularExpression('/this is the test element/', (string)$result);
    }

    public function testAddViewClasses()
    {
        $request = new ServerRequest([
            'url' => 'controller_posts/index',
        ]);
        $controller = new ContentTypesController($request);
        $this->assertSame([], $controller->viewClasses());

        $controller->addViewClasses([PlainTextView::class]);
        $this->assertSame([PlainTextView::class], $controller->viewClasses());

        $controller->addViewClasses([XmlView::class]);
        $this->assertSame([PlainTextView::class, XmlView::class], $controller->viewClasses());
    }

    /**
     * Test that render() will do content negotiation when supported
     * by the controller.
     */
    public function testRenderViewClassesContentNegotiationMatch()
    {
        $request = new ServerRequest([
            'url' => '/',
            'environment' => ['HTTP_ACCEPT' => 'application/json'],
        ]);
        $controller = new ContentTypesController($request);
        $controller->all();
        $response = $controller->render();
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'), 'Has correct header');
        $this->assertNotEmpty(json_decode($response->getBody() . ''), 'Body should be json');
    }

    /**
     * Test that render() will do content negotiation when supported
     * by the controller.
     */
    public function testRenderViewClassContentNegotiationMatchLast()
    {
        $request = new ServerRequest([
            'url' => '/',
            'environment' => ['HTTP_ACCEPT' => 'application/xml'],
        ]);
        $controller = new ContentTypesController($request);
        $controller->all();
        $response = $controller->render();
        $this->assertSame(
            'application/xml; charset=UTF-8',
            $response->getHeaderLine('Content-Type'),
            'Has correct header'
        );
        $this->assertStringContainsString('<?xml', $response->getBody() . '');
    }

    public function testRenderViewClassesContentNegotiationNoMatch()
    {
        $request = new ServerRequest([
            'url' => '/',
            'environment' => ['HTTP_ACCEPT' => 'text/plain'],
            'params' => ['plugin' => null, 'controller' => 'ContentTypes', 'action' => 'all'],
        ]);
        $controller = new ContentTypesController($request);
        $controller->all();
        $response = $controller->render();
        $this->assertSame('text/html; charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('hello world', $response->getBody() . '');
    }

    /**
     * Test that render() will skip content-negotiation when a view class is set.
     */
    public function testRenderViewClassContentNegotiationSkipWithViewClass()
    {
        $request = new ServerRequest([
            'url' => '/',
            'environment' => ['HTTP_ACCEPT' => 'application/xml'],
            'params' => ['plugin' => null, 'controller' => 'ContentTypes', 'action' => 'all'],
        ]);
        $controller = new ContentTypesController($request);
        $controller->all();
        $controller->viewBuilder()->setClassName(View::class);
        $response = $controller->render();
        $this->assertSame(
            'text/html; charset=UTF-8',
            $response->getHeaderLine('Content-Type'),
            'Should not be XML response.'
        );
        $this->assertStringContainsString('hello world', $response->getBody() . '');
    }

    /**
     * Test that render() will do content negotiation when supported
     * by the controller.
     */
    public function testRenderViewClassesContentNegotiationMatchAllType()
    {
        $request = new ServerRequest([
            'url' => '/',
            'environment' => ['HTTP_ACCEPT' => 'text/html'],
        ]);
        $controller = new ContentTypesController($request);
        $controller->matchAll();
        $response = $controller->render();
        $this->assertSame('text/html; charset=UTF-8', $response->getHeaderLine('Content-Type'), 'Default response type');
        $this->assertEmpty($response->getBody() . '', 'Body should be empty');
        $this->assertSame(406, $response->getStatusCode(), 'status code is wrong');
    }

    public function testRenderViewClassesSetContentTypeHeader()
    {
        $request = new ServerRequest([
            'url' => '/',
            'environment' => ['HTTP_ACCEPT' => 'text/plain'],
            'params' => ['plugin' => null, 'controller' => 'ContentTypes', 'action' => 'plain'],
        ]);
        $controller = new ContentTypesController($request);
        $controller->plain();
        $response = $controller->render();
        $this->assertSame('text/plain; charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('hello world', $response->getBody() . '');
    }

    public function testRenderViewClassesUsesSingleMimeExt()
    {
        $request = new ServerRequest([
            'url' => '/',
            'environment' => [],
            'params' => ['plugin' => null, 'controller' => 'ContentTypes', 'action' => 'all', '_ext' => 'json'],
        ]);
        $controller = new ContentTypesController($request);
        $controller->all();
        $response = $controller->render();
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertNotEmpty(json_decode($response->getBody() . ''), 'Body should be json');
    }

    public function testRenderViewClassesUsesMultiMimeExt()
    {
        $request = new ServerRequest([
            'url' => '/',
            'environment' => [],
            'params' => ['plugin' => null, 'controller' => 'ContentTypes', 'action' => 'all', '_ext' => 'xml'],
        ]);
        $controller = new ContentTypesController($request);
        $controller->all();
        $response = $controller->render();
        $this->assertSame('application/xml; charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertTextStartsWith('<?xml', $response->getBody() . '', 'Body should be xml');
    }

    public function testRenderViewClassesMineExtMissingView()
    {
        $request = new ServerRequest([
            'url' => '/',
            'environment' => [],
            'params' => ['plugin' => null, 'controller' => 'ContentTypes', 'action' => 'all', '_ext' => 'json'],
        ]);
        $controller = new ContentTypesController($request);
        $controller->plain();

        $this->expectException(NotFoundException::class);
        $controller->render();
    }

    /**
     * test view rendering changing response
     */
    public function testRenderViewChangesResponse(): void
    {
        $request = new ServerRequest([
            'url' => 'controller_posts/index',
            'params' => [
                'action' => 'header',
            ],
        ]);

        $controller = new Controller($request);
        $controller->viewBuilder()->setTemplatePath('Posts');

        $result = $controller->render('header');
        $this->assertStringContainsString('header template', (string)$result);
        $this->assertTrue($controller->getResponse()->hasHeader('X-view-template'));
        $this->assertSame('yes', $controller->getResponse()->getHeaderLine('X-view-template'));
    }

    /**
     * test that a component beforeRender can change the controller view class.
     */
    public function testBeforeRenderCallbackChangingViewClass(): void
    {
        $Controller = new Controller(new ServerRequest());

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
     */
    public function testBeforeRenderEventCancelsRender(): void
    {
        $Controller = new Controller(new ServerRequest());

        $Controller->getEventManager()->on('Controller.beforeRender', function (EventInterface $event) {
            return false;
        });

        $result = $Controller->render('index');
        $this->assertInstanceOf(Response::class, $result);
    }

    public function testControllerRedirect(): void
    {
        $Controller = new Controller(new ServerRequest());
        $uri = new Uri('/foo/bar');
        $response = $Controller->redirect($uri);
        $this->assertSame('http://localhost/foo/bar', $response->getHeaderLine('Location'));

        $Controller = new Controller(new ServerRequest());
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
        ];
    }

    /**
     * testRedirect method
     *
     * @dataProvider statusCodeProvider
     */
    public function testRedirectByCode(int $code, string $msg): void
    {
        $Controller = new Controller(new ServerRequest());

        $response = $Controller->redirect('http://cakephp.org', (int)$code);
        $this->assertSame($response, $Controller->getResponse());
        $this->assertEquals($code, $response->getStatusCode());
        $this->assertSame('http://cakephp.org', $response->getHeaderLine('Location'));
        $this->assertFalse($Controller->isAutoRenderEnabled());
    }

    /**
     * test that beforeRedirect callbacks can set the URL that is being redirected to.
     */
    public function testRedirectBeforeRedirectModifyingUrl(): void
    {
        $Controller = new Controller(new ServerRequest());

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
     */
    public function testRedirectBeforeRedirectModifyingStatusCode(): void
    {
        $Controller = new Controller(new ServerRequest());

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
        $Controller = new Controller(new ServerRequest());

        $newResponse = new Response();
        $Controller->getEventManager()->on('Controller.beforeRedirect', function (EventInterface $event, $url, Response $response) use ($newResponse) {
            return $newResponse;
        });

        $result = $Controller->redirect('http://cakephp.org');
        $this->assertSame($newResponse, $result);
        $this->assertSame($newResponse, $Controller->getResponse());
    }

    public function testRedirectWithInvalidStatusCode(): void
    {
        $Controller = new Controller(new ServerRequest());
        $uri = new Uri('/foo/bar');
        $this->expectException(InvalidArgumentException::class);
        $Controller->redirect($uri, 200);
    }

    /**
     * testReferer method
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

        $request = $this->getMockBuilder(ServerRequest::class)
            ->onlyMethods(['referer'])
            ->getMock();

        $request = new ServerRequest([
            'environment' => ['HTTP_REFERER' => 'http://localhost/posts/index'],
        ]);
        $Controller = new Controller($request);
        $result = $Controller->referer(null, false);
        $this->assertSame('http://localhost/posts/index', $result);

        $Controller = new Controller(new ServerRequest());
        $result = $Controller->referer('/', false);
        $this->assertSame('http://localhost/', $result);
    }

    /**
     * Test that the referer is not absolute if it is '/'.
     *
     * This avoids the base path being applied twice on string urls.
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
     * Tests that the startup process calls the correct functions
     */
    public function testStartupProcess(): void
    {
        $eventManager = $this->getMockBuilder(EventManagerInterface::class)->getMock();
        $controller = new Controller(new ServerRequest(), null, $eventManager);

        $eventManager
            ->expects($this->exactly(2))
            ->method('dispatch')
            ->with(
                ...self::withConsecutive(
                    [$this->callback(function (EventInterface $event) {
                        return $event->getName() === 'Controller.initialize';
                    })],
                    [$this->callback(function (EventInterface $event) {
                        return $event->getName() === 'Controller.startup';
                    })]
                )
            )
            ->willReturn(new Event('stub'));

        $controller->startupProcess();
    }

    /**
     * Tests that the shutdown process calls the correct functions
     */
    public function testShutdownProcess(): void
    {
        $eventManager = $this->getMockBuilder(EventManagerInterface::class)->getMock();
        $controller = new Controller(new ServerRequest(), null, $eventManager);

        $eventManager->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (EventInterface $event) {
                return $event->getName() === 'Controller.shutdown';
            }))
            ->willReturn(new Event('stub'));

        $controller->shutdownProcess();
    }

    /**
     * test using Controller::paginate()
     */
    public function testPaginate(): void
    {
        $request = new ServerRequest(['url' => 'controller_posts/index']);

        $Controller = new Controller($request);
        $Controller->setRequest($Controller->getRequest()->withQueryParams([
            'posts' => [
                'page' => 2,
                'limit' => 2,
            ],
        ]));

        $this->assertNotContains('Paginator', $Controller->viewBuilder()->getHelpers());
        $this->assertArrayNotHasKey('Paginator', $Controller->viewBuilder()->getHelpers());

        $results = $Controller->paginate('Posts');
        $this->assertInstanceOf(PaginatedInterface::class, $results);
        $this->assertCount(3, $results);

        $results = $Controller->paginate($this->getTableLocator()->get('Posts'));
        $this->assertInstanceOf(PaginatedInterface::class, $results);
        $this->assertCount(3, $results);

        $this->assertSame($results->currentPage(), 1);
        $this->assertSame($results->pageCount(), 1);
        $this->assertFalse($results->hasPrevPage());
        $this->assertFalse($results->hasPrevPage());
        $this->assertNull($results->pagingParam('scope'));

        $results = $Controller->paginate(
            $this->getTableLocator()->get('Posts'),
            ['scope' => 'posts', 'className' => 'Numeric']
        );
        $this->assertInstanceOf(PaginatedInterface::class, $results);
        $this->assertCount(1, $results);

        $this->assertSame($results->currentPage(), 2);
        $this->assertSame($results->pageCount(), 2);
        $this->assertTrue($results->hasPrevPage());
        $this->assertFalse($results->hasNextPage());
        $this->assertSame($results->pagingParam('scope'), 'posts');

        $results = $Controller->paginate(
            $this->getTableLocator()->get('Posts'),
            ['className' => 'Simple']
        );
        $this->assertInstanceOf(PaginatedInterface::class, $results);

        $this->assertNull($results->pageCount(), 'SimplePaginator doesn\'t have a page count');
    }

    /**
     * test that paginate uses modelClass property.
     */
    public function testPaginateUsesModelClass(): void
    {
        $request = new ServerRequest([
            'url' => 'controller_posts/index',
        ]);

        $Controller = new Controller($request, 'Posts');
        $results = $Controller->paginate();

        $this->assertInstanceOf(PaginatedInterface::class, $results);
    }

    public function testPaginateException()
    {
        $this->expectException(NotFoundException::class);

        $request = new ServerRequest(['url' => 'controller_posts/index?page=2&limit=100']);

        $Controller = new Controller($request, 'Posts');

        $Controller->paginate();
    }

    /**
     * testMissingAction method
     */
    public function testGetActionMissingAction(): void
    {
        $this->expectException(MissingActionException::class);
        $this->expectExceptionMessage('Action `TestController::missing()` could not be found, or is not accessible.');
        $url = new ServerRequest([
            'url' => 'test/missing',
            'params' => ['controller' => 'Test', 'action' => 'missing'],
        ]);

        $Controller = new TestController($url);
        $Controller->getAction();
    }

    /**
     * test invoking private methods.
     */
    public function testGetActionPrivate(): void
    {
        $this->expectException(MissingActionException::class);
        $this->expectExceptionMessage('Action `TestController::private_m()` could not be found, or is not accessible.');
        $url = new ServerRequest([
            'url' => 'test/private_m/',
            'params' => ['controller' => 'Test', 'action' => 'private_m'],
        ]);

        $Controller = new TestController($url);
        $Controller->getAction();
    }

    /**
     * test invoking protected methods.
     */
    public function testGetActionProtected(): void
    {
        $this->expectException(MissingActionException::class);
        $this->expectExceptionMessage('Action `TestController::protected_m()` could not be found, or is not accessible.');
        $url = new ServerRequest([
            'url' => 'test/protected_m/',
            'params' => ['controller' => 'Test', 'action' => 'protected_m'],
        ]);

        $Controller = new TestController($url);
        $Controller->getAction();
    }

    /**
     * test invoking controller methods.
     */
    public function testGetActionBaseMethods(): void
    {
        $this->expectException(MissingActionException::class);
        $this->expectExceptionMessage('Action `TestController::redirect()` could not be found, or is not accessible.');
        $url = new ServerRequest([
            'url' => 'test/redirect/',
            'params' => ['controller' => 'Test', 'action' => 'redirect'],
        ]);

        $Controller = new TestController($url);
        $Controller->getAction();
    }

    /**
     * test invoking action method with mismatched casing.
     */
    public function testGetActionMethodCasing(): void
    {
        $this->expectException(MissingActionException::class);
        $this->expectExceptionMessage('Action `TestController::RETURNER()` could not be found, or is not accessible.');
        $url = new ServerRequest([
            'url' => 'test/RETURNER/',
            'params' => ['controller' => 'Test', 'action' => 'RETURNER'],
        ]);

        $Controller = new TestController($url);
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
        $controller = new TestController($request);

        $closure = $controller->getAction();
        $args = (new ReflectionFunction($closure))->getParameters();

        $this->assertSame('Parameter #0 [ <required> $passed ]', (string)$args[0]);
        $this->assertSame('Parameter #1 [ <required> Cake\ORM\Table $table ]', (string)$args[1]);
    }

    /**
     * test invoking controller methods.
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

        $Controller = new TestController($url);
        $Controller->invokeAction($Controller->getAction(), $Controller->getRequest()->getParam('pass'));

        $this->assertSame('I am from the controller.', (string)$Controller->getResponse());
    }

    /**
     * test invoking controller methods with passed params
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
        $controller = new TestController($request);
        $controller->disableAutoRender();
        $controller->invokeAction($controller->getAction(), array_values($controller->getRequest()->getParam('pass')));

        $this->assertEquals(
            ['testId' => '1', 'test2Id' => '2'],
            $controller->getRequest()->getData()
        );
    }

    /**
     * test invalid return value from action method.
     */
    public function testInvokeActionException(): void
    {
        $this->expectException(AssertionError::class);
        $this->expectExceptionMessage(
            'Controller actions can only return Response instance or null. '
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

        $Controller = new TestController($url);
        $Controller->invokeAction($Controller->getAction(), $Controller->getRequest()->getParam('pass'));
    }

    /**
     * test that a classes namespace is used in the viewPath.
     */
    public function testViewPathConventions(): void
    {
        $request = new ServerRequest([
            'url' => 'admin/posts',
            'params' => ['prefix' => 'Admin'],
        ]);
        $Controller = new AdminPostsController($request);
        $Controller->getEventManager()->on('Controller.beforeRender', function (EventInterface $e) {
            return $e->getSubject()->getResponse();
        });
        $Controller->render();
        $this->assertSame('Admin' . DS . 'Posts', $Controller->viewBuilder()->getTemplatePath());

        $request = $request->withParam('prefix', 'admin/super');
        $Controller = new AdminPostsController($request);
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
        $Controller = new PagesController($request);
        $Controller->getEventManager()->on('Controller.beforeRender', function (EventInterface $e) {
            return $e->getSubject()->getResponse();
        });
        $Controller->render();
        $this->assertSame('Pages', $Controller->viewBuilder()->getTemplatePath());
    }

    /**
     * Test the components() method.
     */
    public function testComponents(): void
    {
        $request = new ServerRequest(['url' => '/']);

        $controller = new TestController($request);
        $this->assertInstanceOf(ComponentRegistry::class, $controller->components());

        $result = $controller->components();
        $this->assertSame($result, $controller->components());
    }

    /**
     * Test adding a component
     */
    public function testLoadComponent(): void
    {
        $request = new ServerRequest(['url' => '/']);

        $controller = new TestController($request);
        $result = $controller->loadComponent('FormProtection');
        $this->assertInstanceOf(FormProtectionComponent::class, $result);
        $this->assertSame($result, $controller->FormProtection);

        $registry = $controller->components();
        $this->assertTrue(isset($registry->FormProtection));
    }

    /**
     * Test adding a component that is a duplicate.
     */
    public function testLoadComponentDuplicate(): void
    {
        $request = new ServerRequest(['url' => '/']);

        $controller = new TestController($request);
        $this->assertNotEmpty($controller->loadComponent('FormProtection'));
        $this->assertNotEmpty($controller->loadComponent('FormProtection'));
        try {
            $controller->loadComponent('FormProtection', ['bad' => 'settings']);
            $this->fail('No exception');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('The `FormProtection` alias has already been loaded', $e->getMessage());
        }
    }

    /**
     * Test the isAction method.
     */
    public function testIsAction(): void
    {
        $request = new ServerRequest(['url' => '/']);
        $controller = new TestController($request);

        $this->assertFalse($controller->isAction('redirect'));
        $this->assertFalse($controller->isAction('beforeFilter'));
        $this->assertTrue($controller->isAction('index'));
    }

    /**
     * Test that view variables are being set after the beforeRender event gets dispatched
     */
    public function testBeforeRenderViewVariables(): void
    {
        $controller = new AdminPostsController(new ServerRequest());

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
     */
    public function testBeforeRenderTemplateAndLayout(): void
    {
        $Controller = new Controller(new ServerRequest());
        $Controller->getEventManager()->on('Controller.beforeRender', function ($event): void {
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
     */
    public function testName(): void
    {
        $controller = new AdminPostsController(new ServerRequest());
        $this->assertSame('Posts', $controller->getName());

        $this->assertSame($controller, $controller->setName('Articles'));
        $this->assertSame('Articles', $controller->getName());
    }

    /**
     * Test plugin getter and setter.
     */
    public function testPlugin(): void
    {
        $controller = new AdminPostsController(new ServerRequest());
        $this->assertNull($controller->getPlugin());

        $this->assertSame($controller, $controller->setPlugin('Articles'));
        $this->assertSame('Articles', $controller->getPlugin());
    }

    /**
     * Test request getter and setter.
     */
    public function testRequest(): void
    {
        $controller = new AdminPostsController(new ServerRequest());
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
     */
    public function testResponse(): void
    {
        $controller = new AdminPostsController(new ServerRequest());
        $this->assertInstanceOf(Response::class, $controller->getResponse());

        $response = new Response();
        $this->assertSame($controller, $controller->setResponse($response));
        $this->assertSame($response, $controller->getResponse());
    }

    /**
     * Test autoRender getter and setter.
     */
    public function testAutoRender(): void
    {
        $controller = new AdminPostsController(new ServerRequest());
        $this->assertTrue($controller->isAutoRenderEnabled());

        $this->assertSame($controller, $controller->disableAutoRender());
        $this->assertFalse($controller->isAutoRenderEnabled());

        $this->assertSame($controller, $controller->enableAutoRender());
        $this->assertTrue($controller->isAutoRenderEnabled());
    }
}
