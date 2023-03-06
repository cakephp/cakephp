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
use Cake\Controller\Exception\MissingActionException;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use Cake\View\XmlView;
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
use TestPlugin\Controller\TestPluginController;
use UnexpectedValueException;

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
    protected $fixtures = [
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
     * test autoload modelClass
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
     */
    public function testUndefinedPropertyError(): void
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
     */
    public function testLoadModel(): void
    {
        $request = new ServerRequest(['url' => 'controller/posts/index']);
        $Controller = new Controller($request, new Response());

        $this->assertFalse(isset($Controller->Articles));

        $this->deprecated(function () use ($Controller) {
            $result = $Controller->loadModel('Articles');
            $this->assertInstanceOf(
                'TestApp\Model\Table\ArticlesTable',
                $result
            );
            $this->assertInstanceOf(
                'TestApp\Model\Table\ArticlesTable',
                $Controller->Articles
            );
        });
    }

    public function testAutoLoadModelUsingDefaultTable()
    {
        Configure::write('App.namespace', 'TestApp');
        $Controller = new WithDefaultTableController(new ServerRequest(), new Response());

        $this->assertInstanceOf(PostsTable::class, $Controller->Posts);

        Configure::write('App.namespace', 'App');
    }

    /**
     * @link https://github.com/cakephp/cakephp/issues/14804
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
     */
    public function testLoadModelInPlugins(): void
    {
        $this->loadPlugins(['TestPlugin']);

        $Controller = new TestPluginController();
        $Controller->setPlugin('TestPlugin');

        $this->assertFalse(isset($Controller->TestPluginComments));

        $this->deprecated(function () use ($Controller) {
            $result = $Controller->loadModel('TestPlugin.TestPluginComments');
            $this->assertInstanceOf(
                'TestPlugin\Model\Table\TestPluginCommentsTable',
                $result
            );
            $this->assertInstanceOf(
                'TestPlugin\Model\Table\TestPluginCommentsTable',
                $Controller->TestPluginComments
            );
        });
    }

    /**
     * Test that the constructor sets modelClass properly.
     */
    public function testConstructSetModelClass(): void
    {
        $this->loadPlugins(['TestPlugin']);

        $request = new ServerRequest();
        $response = new Response();
        $this->deprecated(function () use ($request, $response) {
            $controller = new PostsController($request, $response);
            $this->assertInstanceOf('Cake\ORM\Table', $controller->fetchModel());
            $this->assertInstanceOf('Cake\ORM\Table', $controller->loadModel());
            $this->assertInstanceOf('Cake\ORM\Table', $controller->Posts);

            $controller = new AdminPostsController($request, $response);
            $this->assertInstanceOf('Cake\ORM\Table', $controller->fetchModel());
            $this->assertInstanceOf('Cake\ORM\Table', $controller->loadModel());
            $this->assertInstanceOf('Cake\ORM\Table', $controller->Posts);

            $request = $request->withParam('plugin', 'TestPlugin');
            $controller = new CommentsController($request, $response);
            $this->assertInstanceOf('TestPlugin\Model\Table\CommentsTable', $controller->fetchModel());
            $this->assertInstanceOf('TestPlugin\Model\Table\CommentsTable', $controller->loadModel());
            $this->assertInstanceOf('TestPlugin\Model\Table\CommentsTable', $controller->Comments);
        });
    }

    public function testConstructSetDefaultTable()
    {
        Configure::write('App.namespace', 'TestApp');

        $controller = new PostsController();
        $this->assertInstanceOf(PostsTable::class, $controller->fetchTable());

        Configure::write('App.namespace', 'App');
    }

    /**
     * testConstructClassesWithComponents method
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

    public function testAddViewClasses()
    {
        $controller = new ContentTypesController();
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
        $controller = new ContentTypesController($request, new Response());
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
        $controller = new ContentTypesController($request, new Response());
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
        $controller = new ContentTypesController($request, new Response());
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
        $controller = new ContentTypesController($request, new Response());
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
        $controller = new ContentTypesController($request, new Response());
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
        $controller = new ContentTypesController($request, new Response());
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
        $controller = new ContentTypesController($request, new Response());
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
        $controller = new ContentTypesController($request, new Response());
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
        $controller = new ContentTypesController($request, new Response());
        $controller->plain();

        $this->expectException(NotFoundException::class);
        $response = $controller->render();
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

        $controller = new Controller($request, new Response());
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

    public function testControllerRedirect(): void
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
        ];
    }

    /**
     * testRedirect method
     *
     * @dataProvider statusCodeProvider
     */
    public function testRedirectByCode(int $code, string $msg): void
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

    public function testRedirectWithInvalidStatusCode(): void
    {
        $Controller = new Controller();
        $uri = new Uri('/foo/bar');
        $this->expectException(\InvalidArgumentException::class);
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
     * @group deprecated
     */
    public function testSetAction(): void
    {
        $this->deprecated(function (): void {
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

        $this->deprecated(function () use ($Controller) {
            $results = $Controller->paginate('Posts');
            $this->assertInstanceOf('Cake\Datasource\ResultSetInterface', $results);
            $this->assertCount(3, $results);
        });

        $results = $Controller->paginate($this->getTableLocator()->get('Posts'));
        $this->assertInstanceOf('Cake\Datasource\ResultSetInterface', $results);
        $this->assertCount(3, $results);

        $paging = $Controller->getRequest()->getAttribute('paging');
        $this->assertSame($paging['Posts']['page'], 1);
        $this->assertSame($paging['Posts']['pageCount'], 1);
        $this->assertFalse($paging['Posts']['prevPage']);
        $this->assertFalse($paging['Posts']['nextPage']);
        $this->assertNull($paging['Posts']['scope']);

        $Controller->paginate = ['className' => 'Numeric'];
        $results = $Controller->paginate($this->getTableLocator()->get('Posts'), ['scope' => 'posts']);
        $this->assertInstanceOf('Cake\Datasource\ResultSetInterface', $results);
        $this->assertCount(1, $results);

        $paging = $Controller->getRequest()->getAttribute('paging');
        $this->assertSame($paging['Posts']['page'], 2);
        $this->assertSame($paging['Posts']['pageCount'], 2);
        $this->assertTrue($paging['Posts']['prevPage']);
        $this->assertFalse($paging['Posts']['nextPage']);
        $this->assertSame($paging['Posts']['scope'], 'posts');

        $results = $Controller->paginate(
            $this->getTableLocator()->get('Posts'),
            ['className' => 'Simple']
        );
        $this->assertInstanceOf('Cake\Datasource\ResultSetInterface', $results);

        $paging = $Controller->getRequest()->getAttribute('paging');
        $this->assertSame($paging['Posts']['pageCount'], 0, 'SimplePaginator doesn\'t have a page count');
    }

    /**
     * test that paginate uses modelClass property.
     */
    public function testPaginateUsesModelClass(): void
    {
        $request = new ServerRequest([
            'url' => 'controller_posts/index',
        ]);
        $response = new Response();

        $Controller = new Controller($request, $response);
        $Controller->modelClass = 'Posts';
        $this->deprecated(function () use ($Controller) {
            $results = $Controller->paginate();

            $this->assertInstanceOf('Cake\Datasource\ResultSetInterface', $results);
        });
    }

    /**
     * testMissingAction method
     */
    public function testGetActionMissingAction(): void
    {
        $this->expectException(MissingActionException::class);
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
     */
    public function testGetActionPrivate(): void
    {
        $this->expectException(MissingActionException::class);
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
     */
    public function testGetActionProtected(): void
    {
        $this->expectException(MissingActionException::class);
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
     */
    public function testGetActionBaseMethods(): void
    {
        $this->expectException(MissingActionException::class);
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
     */
    public function testGetActionMethodCasing(): void
    {
        $this->expectException(MissingActionException::class);
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
     */
    public function testInvokeActionException(): void
    {
        $this->expectException(UnexpectedValueException::class);
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
     */
    public function testViewPathConventions(): void
    {
        $request = new ServerRequest([
            'url' => 'admin/posts',
            'params' => ['prefix' => 'Admin'],
        ]);
        $response = new Response();
        $Controller = new AdminPostsController($request, $response);
        $Controller->getEventManager()->on('Controller.beforeRender', function (EventInterface $e) {
            return $e->getSubject()->getResponse();
        });
        $Controller->render();
        $this->assertSame('Admin' . DS . 'Posts', $Controller->viewBuilder()->getTemplatePath());

        $request = $request->withParam('prefix', 'admin/super');
        $response = new Response();
        $Controller = new AdminPostsController($request, $response);
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
        $Controller = new PagesController($request, $response);
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
        $response = new Response();

        $controller = new TestController($request, $response);
        $this->assertInstanceOf('Cake\Controller\ComponentRegistry', $controller->components());

        $result = $controller->components();
        $this->assertSame($result, $controller->components());
    }

    /**
     * Test the components property errors
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
     */
    public function testLoadComponent(): void
    {
        $request = new ServerRequest(['url' => '/']);
        $response = new Response();

        $controller = new TestController($request, $response);
        $result = $controller->loadComponent('FormProtection');
        $this->assertInstanceOf('Cake\Controller\Component\FormProtectionComponent', $result);
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
        $response = new Response();

        $controller = new TestController($request, $response);
        $this->assertNotEmpty($controller->loadComponent('FormProtection'));
        $this->assertNotEmpty($controller->loadComponent('FormProtection'));
        try {
            $controller->loadComponent('FormProtection', ['bad' => 'settings']);
            $this->fail('No exception');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('The "FormProtection" alias has already been loaded', $e->getMessage());
        }
    }

    /**
     * Test the isAction method.
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
     */
    public function testBeforeRenderViewVariables(): void
    {
        $controller = new AdminPostsController();

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
        $Controller = new Controller(new ServerRequest(), new Response());
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
        $controller = new AdminPostsController();
        $this->assertSame('Posts', $controller->getName());

        $this->assertSame($controller, $controller->setName('Articles'));
        $this->assertSame('Articles', $controller->getName());
    }

    /**
     * Test plugin getter and setter.
     */
    public function testPlugin(): void
    {
        $controller = new AdminPostsController();
        $this->assertNull($controller->getPlugin());

        $this->assertSame($controller, $controller->setPlugin('Articles'));
        $this->assertSame('Articles', $controller->getPlugin());
    }

    /**
     * Test request getter and setter.
     */
    public function testRequest(): void
    {
        $controller = new AdminPostsController();
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
        $controller = new AdminPostsController();
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
        $controller = new AdminPostsController();
        $this->assertTrue($controller->isAutoRenderEnabled());

        $this->assertSame($controller, $controller->disableAutoRender());
        $this->assertFalse($controller->isAutoRenderEnabled());

        $this->assertSame($controller, $controller->enableAutoRender());
        $this->assertTrue($controller->isAutoRenderEnabled());
    }
}
