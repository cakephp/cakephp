<?php
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

use Cake\Controller\Component;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Error\Warning;
use TestApp\Controller\Admin\PostsController;
use TestPlugin\Controller\TestPluginController;

/**
 * AppController class
 */
class ControllerTestAppController extends Controller
{
    /**
     * modelClass property
     *
     * @var string
     */
    public $modelClass = 'Posts';
}

/**
 * TestController class
 */
class TestController extends ControllerTestAppController
{
    /**
     * Theme property
     *
     * @var string
     */
    public $theme = 'Foo';

    /**
     * modelClass property
     *
     * @var string
     */
    public $modelClass = 'Comments';

    /**
     * beforeFilter handler
     *
     * @param \Cake\Event\EventInterface $event
     * @return void
     */
    public function beforeFilter(EventInterface $event)
    {
    }

    /**
     * index method
     *
     * @param mixed $testId
     * @param mixed $testTwoId
     * @return void
     */
    public function index($testId, $testTwoId)
    {
        $this->request = $this->request->withParsedBody([
            'testId' => $testId,
            'test2Id' => $testTwoId,
        ]);
    }

    /**
     * view method
     *
     * @param mixed $testId
     * @param mixed $testTwoId
     * @return void
     */
    public function view($testId, $testTwoId)
    {
        $this->request = $this->request->withParsedBody([
            'testId' => $testId,
            'test2Id' => $testTwoId,
        ]);
    }

    public function returner()
    {
        return 'I am from the controller.';
    }

    //@codingStandardsIgnoreStart
    protected function protected_m()
    {
    }

    private function private_m()
    {
    }

    public function _hidden()
    {
    }
    //@codingStandardsIgnoreEnd

    public function admin_add()
    {
    }
}

/**
 * TestComponent class
 */
class TestComponent extends Component
{
    /**
     * beforeRedirect method
     *
     * @return void
     */
    public function beforeRedirect()
    {
    }

    /**
     * initialize method
     *
     * @param array $config
     * @return void
     */
    public function initialize(array $config)
    {
    }

    /**
     * startup method
     *
     * @param \Cake\Event\EventInterface $event
     * @return void
     */
    public function startup(EventInterface $event)
    {
    }

    /**
     * shutdown method
     *
     * @param \Cake\Event\EventInterface $event
     * @return void
     */
    public function shutdown(EventInterface $event)
    {
    }

    /**
     * beforeRender callback
     *
     * @param \Cake\Event\EventInterface $event
     * @return void
     */
    public function beforeRender(EventInterface $event)
    {
        $controller = $event->getSubject();
        if ($this->viewclass) {
            $controller->viewClass = $this->viewclass;
        }
    }
}

/**
 * AnotherTestController class
 */
class AnotherTestController extends ControllerTestAppController
{
}

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
    public $fixtures = [
        'core.comments',
        'core.posts',
    ];

    /**
     * reset environment.
     *
     * @return void
     */
    public function setUp()
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
    public function tearDown()
    {
        parent::tearDown();
        Plugin::unload();
    }

    /**
     * test autoload modelClass
     *
     * @return void
     */
    public function testTableAutoload()
    {
        $request = new ServerRequest(['url' => 'controller/posts/index']);
        $response = $this->getMockBuilder('Cake\Http\Response')->getMock();
        $Controller = new Controller($request, $response);
        $Controller->modelClass = 'SiteArticles';

        $this->assertFalse($Controller->Articles);
        $this->assertInstanceOf(
            'Cake\ORM\Table',
            $Controller->SiteArticles
        );
        unset($Controller->SiteArticles);

        $Controller->modelClass = 'Articles';

        $this->assertFalse($Controller->SiteArticles);
        $this->assertInstanceOf(
            'TestApp\Model\Table\ArticlesTable',
            $Controller->Articles
        );
    }

    /**
     * testLoadModel method
     *
     * @return void
     */
    public function testLoadModel()
    {
        $request = new ServerRequest(['url' => 'controller/posts/index']);
        $response = $this->getMockBuilder('Cake\Http\Response')->getMock();
        $Controller = new Controller($request, $response);

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
     * testLoadModel method from a plugin controller
     *
     * @return void
     */
    public function testLoadModelInPlugins()
    {
        Plugin::load('TestPlugin');

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
    public function testConstructSetModelClass()
    {
        Plugin::load('TestPlugin');

        $request = new ServerRequest();
        $response = new Response();
        $controller = new \TestApp\Controller\PostsController($request, $response);
        $this->assertEquals('Posts', $controller->modelClass);
        $this->assertInstanceOf('Cake\ORM\Table', $controller->Posts);

        $controller = new \TestApp\Controller\Admin\PostsController($request, $response);
        $this->assertEquals('Posts', $controller->modelClass);
        $this->assertInstanceOf('Cake\ORM\Table', $controller->Posts);

        $request = $request->withParam('plugin', 'TestPlugin');
        $controller = new \TestPlugin\Controller\Admin\CommentsController($request, $response);
        $this->assertEquals('TestPlugin.Comments', $controller->modelClass);
        $this->assertInstanceOf('TestPlugin\Model\Table\CommentsTable', $controller->Comments);
    }

    /**
     * testConstructClassesWithComponents method
     *
     * @return void
     */
    public function testConstructClassesWithComponents()
    {
        Plugin::load('TestPlugin');

        $Controller = new TestPluginController(new ServerRequest(), new Response());
        $Controller->loadComponent('TestPlugin.Other');

        $this->assertInstanceOf('TestPlugin\Controller\Component\OtherComponent', $Controller->Other);
    }

    /**
     * testRender method
     *
     * @return void
     */
    public function testRender()
    {
        Plugin::load('TestPlugin');

        $request = new ServerRequest([
            'url' => 'controller_posts/index',
            'params' => [
                'action' => 'header',
            ],
        ]);

        $Controller = new Controller($request, new Response());
        $Controller->viewBuilder()->setTemplatePath('Posts');

        $result = $Controller->render('index');
        $this->assertRegExp('/posts index/', (string)$result);

        $Controller->viewBuilder()->setTemplate('index');
        $result = $Controller->render();
        $this->assertRegExp('/posts index/', (string)$result);

        $result = $Controller->render('/Element/test_element');
        $this->assertRegExp('/this is the test element/', (string)$result);
    }

    /**
     * test view rendering changing response
     *
     * @return void
     */
    public function testRenderViewChangesResponse()
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
        $this->assertContains('header template', (string)$result);
        $this->assertTrue($controller->getResponse()->hasHeader('X-view-template'));
        $this->assertSame('yes', $controller->getResponse()->getHeaderLine('X-view-template'));
    }

    /**
     * test that a component beforeRender can change the controller view class.
     *
     * @return void
     */
    public function testBeforeRenderCallbackChangingViewClass()
    {
        $Controller = new Controller(new ServerRequest, new Response());

        $Controller->getEventManager()->on('Controller.beforeRender', function (EventInterface $event) {
            $controller = $event->getSubject();
            $controller->viewBuilder()->setClassName('Json');
        });

        $Controller->set([
            'test' => 'value',
            '_serialize' => ['test'],
        ]);
        $debug = Configure::read('debug');
        Configure::write('debug', false);
        $result = $Controller->render('index');
        $this->assertEquals('{"test":"value"}', (string)$result->getBody());
        Configure::write('debug', $debug);
    }

    /**
     * test that a component beforeRender can change the controller view class.
     *
     * @return void
     */
    public function testBeforeRenderEventCancelsRender()
    {
        $Controller = new Controller(new ServerRequest, new Response());

        $Controller->getEventManager()->on('Controller.beforeRender', function (EventInterface $event) {
            return false;
        });

        $result = $Controller->render('index');
        $this->assertInstanceOf('Cake\Http\Response', $result);
    }

    /**
     * Generates status codes for redirect test.
     *
     * @return void
     */
    public static function statusCodeProvider()
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
    public function testRedirectByCode($code, $msg)
    {
        $Controller = new Controller(null, new Response());

        $response = $Controller->redirect('http://cakephp.org', (int)$code);
        $this->assertSame($response, $Controller->getResponse());
        $this->assertEquals($code, $response->getStatusCode());
        $this->assertEquals('http://cakephp.org', $response->getHeaderLine('Location'));
        $this->assertFalse($Controller->isAutoRenderEnabled());
    }

    /**
     * test that beforeRedirect callbacks can set the URL that is being redirected to.
     *
     * @return void
     */
    public function testRedirectBeforeRedirectModifyingUrl()
    {
        $Controller = new Controller(null, new Response());

        $Controller->getEventManager()->on('Controller.beforeRedirect', function (EventInterface $event, $url, Response $response) {
            $controller = $event->getSubject();
            $controller->setResponse($response->withLocation('https://book.cakephp.org'));
        });

        $response = $Controller->redirect('http://cakephp.org', 301);
        $this->assertEquals('https://book.cakephp.org', $response->getHeaderLine('Location'));
        $this->assertEquals(301, $response->getStatusCode());
    }

    /**
     * test that beforeRedirect callback returning null doesn't affect things.
     *
     * @return void
     */
    public function testRedirectBeforeRedirectModifyingStatusCode()
    {
        $response = new Response();
        $Controller = new Controller(null, $response);

        $Controller->getEventManager()->on('Controller.beforeRedirect', function (EventInterface $event, $url, Response $response) {
            $controller = $event->getSubject();
            $controller->setResponse($response->withStatus(302));
        });

        $response = $Controller->redirect('http://cakephp.org', 301);

        $this->assertEquals('http://cakephp.org', $response->getHeaderLine('Location'));
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testRedirectBeforeRedirectListenerReturnResponse()
    {
        $Response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods(['stop', 'header', 'statusCode'])
            ->getMock();
        $Controller = new Controller(null, $Response);

        $newResponse = new Response;
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
    public function testReferer()
    {
        $request = $this->getMockBuilder('Cake\Http\ServerRequest')
            ->setMethods(['referer'])
            ->getMock();
        $request->expects($this->any())->method('referer')
            ->with(true)
            ->will($this->returnValue('/posts/index'));

        $Controller = new Controller($request);
        $result = $Controller->referer(null, true);
        $this->assertEquals('/posts/index', $result);

        $request = $this->getMockBuilder('Cake\Http\ServerRequest')
            ->setMethods(['referer'])
            ->getMock();
        $request->expects($this->any())->method('referer')
            ->with(true)
            ->will($this->returnValue('/posts/index'));
        $Controller = new Controller($request);
        $result = $Controller->referer(['controller' => 'posts', 'action' => 'index'], true);
        $this->assertEquals('/posts/index', $result);

        $request = $this->getMockBuilder('Cake\Http\ServerRequest')
            ->setMethods(['referer'])
            ->getMock();

        $request->expects($this->any())->method('referer')
            ->with(false)
            ->will($this->returnValue('http://localhost/posts/index'));

        $Controller = new Controller($request);
        $result = $Controller->referer();
        $this->assertEquals('http://localhost/posts/index', $result);

        $Controller = new Controller(null);
        $result = $Controller->referer();
        $this->assertEquals('/', $result);
    }

    /**
     * Test that the referer is not absolute if it is '/'.
     *
     * This avoids the base path being applied twice on string urls.
     *
     * @return void
     */
    public function testRefererSlash()
    {
        $request = $this->getMockBuilder('Cake\Http\ServerRequest')
            ->setMethods(['referer'])
            ->getMock();
        $request = $request->withAttribute('base', '/base');
        Router::pushRequest($request);

        $request->expects($this->any())->method('referer')
            ->will($this->returnValue('/'));

        $controller = new Controller($request);
        $result = $controller->referer('/', true);
        $this->assertEquals('/', $result);

        $controller = new Controller($request);
        $result = $controller->referer('/some/path', true);
        $this->assertEquals('/some/path', $result);
    }

    /**
     * testSetAction method
     *
     * @return void
     */
    public function testSetAction()
    {
        $request = new ServerRequest(['url' => 'controller/posts/index']);

        $TestController = new TestController($request);
        $TestController->setAction('view', 1, 2);
        $expected = ['testId' => 1, 'test2Id' => 2];
        $this->assertSame($expected, $TestController->getRequest()->getData());
        $this->assertSame('view', $TestController->getRequest()->getParam('action'));
    }

    /**
     * Tests that the startup process calls the correct functions
     *
     * @return void
     */
    public function testStartupProcess()
    {
        $eventManager = $this->getMockBuilder('Cake\Event\EventManager')->getMock();
        $controller = new Controller(null, null, null, $eventManager);

        $eventManager->expects($this->at(0))->method('dispatch')
            ->with(
                $this->logicalAnd(
                    $this->isInstanceOf('Cake\Event\Event'),
                    $this->attributeEqualTo('_name', 'Controller.initialize'),
                    $this->attributeEqualTo('_subject', $controller)
                )
            )
            ->will($this->returnValue($this->getMockBuilder('Cake\Event\Event')->disableOriginalConstructor()->getMock()));

        $eventManager->expects($this->at(1))->method('dispatch')
            ->with(
                $this->logicalAnd(
                    $this->isInstanceOf('Cake\Event\Event'),
                    $this->attributeEqualTo('_name', 'Controller.startup'),
                    $this->attributeEqualTo('_subject', $controller)
                )
            )
            ->will($this->returnValue($this->getMockBuilder('Cake\Event\Event')->disableOriginalConstructor()->getMock()));

        $controller->startupProcess();
    }

    /**
     * Tests that the shutdown process calls the correct functions
     *
     * @return void
     */
    public function testShutdownProcess()
    {
        $eventManager = $this->getMockBuilder('Cake\Event\EventManager')->getMock();
        $controller = new Controller(null, null, null, $eventManager);

        $eventManager->expects($this->once())->method('dispatch')
            ->with(
                $this->logicalAnd(
                    $this->isInstanceOf('Cake\Event\Event'),
                    $this->attributeEqualTo('_name', 'Controller.shutdown'),
                    $this->attributeEqualTo('_subject', $controller)
                )
            )
            ->will($this->returnValue($this->getMockBuilder('Cake\Event\Event')->disableOriginalConstructor()->getMock()));

        $controller->shutdownProcess();
    }

    /**
     * test using Controller::paginate()
     *
     * @return void
     */
    public function testPaginate()
    {
        $request = new ServerRequest(['url' => 'controller_posts/index']);
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods(['httpCodes'])
            ->getMock();

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

        $paging = $Controller->getRequest()->getParam('paging');
        $this->assertSame($paging['Posts']['page'], 1);
        $this->assertSame($paging['Posts']['pageCount'], 1);
        $this->assertFalse($paging['Posts']['prevPage']);
        $this->assertFalse($paging['Posts']['nextPage']);
        $this->assertNull($paging['Posts']['scope']);

        $results = $Controller->paginate($this->getTableLocator()->get('Posts'), ['scope' => 'posts']);
        $this->assertInstanceOf('Cake\Datasource\ResultSetInterface', $results);
        $this->assertCount(1, $results);

        $paging = $Controller->getRequest()->getParam('paging');
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
    public function testPaginateUsesModelClass()
    {
        $request = new ServerRequest([
            'url' => 'controller_posts/index',
        ]);
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods(['httpCodes'])
            ->getMock();

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
    public function testInvokeActionMissingAction()
    {
        $this->expectException(\Cake\Controller\Exception\MissingActionException::class);
        $this->expectExceptionMessage('Action TestController::missing() could not be found, or is not accessible.');
        $url = new ServerRequest([
            'url' => 'test/missing',
            'params' => ['controller' => 'Test', 'action' => 'missing'],
        ]);
        $response = $this->getMockBuilder('Cake\Http\Response')->getMock();

        $Controller = new TestController($url, $response);
        $Controller->invokeAction();
    }

    /**
     * test invoking private methods.
     *
     * @return void
     */
    public function testInvokeActionPrivate()
    {
        $this->expectException(\Cake\Controller\Exception\MissingActionException::class);
        $this->expectExceptionMessage('Action TestController::private_m() could not be found, or is not accessible.');
        $url = new ServerRequest([
            'url' => 'test/private_m/',
            'params' => ['controller' => 'Test', 'action' => 'private_m'],
        ]);
        $response = $this->getMockBuilder('Cake\Http\Response')->getMock();

        $Controller = new TestController($url, $response);
        $Controller->invokeAction();
    }

    /**
     * test invoking protected methods.
     *
     * @return void
     */
    public function testInvokeActionProtected()
    {
        $this->expectException(\Cake\Controller\Exception\MissingActionException::class);
        $this->expectExceptionMessage('Action TestController::protected_m() could not be found, or is not accessible.');
        $url = new ServerRequest([
            'url' => 'test/protected_m/',
            'params' => ['controller' => 'Test', 'action' => 'protected_m'],
        ]);
        $response = $this->getMockBuilder('Cake\Http\Response')->getMock();

        $Controller = new TestController($url, $response);
        $Controller->invokeAction();
    }

    /**
     * test invoking controller methods.
     *
     * @return void
     */
    public function testInvokeActionBaseMethods()
    {
        $this->expectException(\Cake\Controller\Exception\MissingActionException::class);
        $this->expectExceptionMessage('Action TestController::redirect() could not be found, or is not accessible.');
        $url = new ServerRequest([
            'url' => 'test/redirect/',
            'params' => ['controller' => 'Test', 'action' => 'redirect'],
        ]);
        $response = $this->getMockBuilder('Cake\Http\Response')->getMock();

        $Controller = new TestController($url, $response);
        $Controller->invokeAction();
    }

    /**
     * test invoking controller methods.
     *
     * @return void
     */
    public function testInvokeActionReturnValue()
    {
        $url = new ServerRequest([
            'url' => 'test/returner/',
            'params' => [
                'controller' => 'Test',
                'action' => 'returner',
                'pass' => [],
            ],
        ]);
        $response = $this->getMockBuilder('Cake\Http\Response')->getMock();

        $Controller = new TestController($url, $response);
        $result = $Controller->invokeAction();
        $this->assertEquals('I am from the controller.', $result);
    }

    /**
     * test invoking controller methods with passed params
     *
     * @return void
     */
    public function testInvokeActionWithPassedParams()
    {
        $url = new ServerRequest([
            'url' => 'test/index/1/2',
            'params' => [
                'controller' => 'Test',
                'action' => 'index',
                'pass' => ['param1' => '1', 'param2' => '2'],
            ],
        ]);
        $response = $this->getMockBuilder('Cake\Http\Response')->getMock();

        $Controller = new TestController($url, $response);
        $result = $Controller->invokeAction();
        $this->assertEquals(
            ['testId' => '1', 'test2Id' => '2'],
            $Controller->getRequest()->getData()
        );
    }

    /**
     * test that a classes namespace is used in the viewPath.
     *
     * @return void
     */
    public function testViewPathConventions()
    {
        $request = new ServerRequest([
            'url' => 'admin/posts',
            'params' => ['prefix' => 'admin'],
        ]);
        $response = $this->getMockBuilder('Cake\Http\Response')->getMock();
        $Controller = new \TestApp\Controller\Admin\PostsController($request, $response);
        $Controller->getEventManager()->on('Controller.beforeRender', function (EventInterface $e) {
            return $e->getSubject()->response;
        });
        $Controller->render();
        $this->assertEquals('Admin' . DS . 'Posts', $Controller->viewBuilder()->getTemplatePath());

        $request = $request->withParam('prefix', 'admin/super');
        $response = $this->getMockBuilder('Cake\Http\Response')->getMock();
        $Controller = new \TestApp\Controller\Admin\PostsController($request, $response);
        $Controller->getEventManager()->on('Controller.beforeRender', function (EventInterface $e) {
            return $e->getSubject()->response;
        });
        $Controller->render();
        $this->assertEquals('Admin' . DS . 'Super' . DS . 'Posts', $Controller->viewBuilder()->getTemplatePath());

        $request = new ServerRequest([
            'url' => 'pages/home',
            'params' => [
                'prefix' => false,
            ],
        ]);
        $Controller = new \TestApp\Controller\PagesController($request, $response);
        $Controller->getEventManager()->on('Controller.beforeRender', function (EventInterface $e) {
            return $e->getSubject()->response;
        });
        $Controller->render();
        $this->assertEquals('Pages', $Controller->viewBuilder()->getTemplatePath());
    }

    /**
     * Test the components() method.
     *
     * @return void
     */
    public function testComponents()
    {
        $request = new ServerRequest(['url' => '/']);
        $response = $this->getMockBuilder('Cake\Http\Response')->getMock();

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
    public function testComponentsPropertyError()
    {
        $this->expectException(Warning::class);
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
    public function testHelpersPropertyError()
    {
        $this->expectException(Warning::class);
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
    public function testComponentsWithCustomRegistry()
    {
        $request = new ServerRequest(['url' => '/']);
        $response = $this->getMockBuilder('Cake\Http\Response')->getMock();
        $componentRegistry = $this->getMockBuilder('Cake\Controller\ComponentRegistry')
            ->setMethods(['offsetGet'])
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
    public function testLoadComponent()
    {
        $request = new ServerRequest(['url' => '/']);
        $response = $this->getMockBuilder('Cake\Http\Response')->getMock();

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
    public function testLoadComponentDuplicate()
    {
        $request = new ServerRequest(['url' => '/']);
        $response = $this->getMockBuilder('Cake\Http\Response')->getMock();

        $controller = new TestController($request, $response);
        $this->assertNotEmpty($controller->loadComponent('Paginator'));
        $this->assertNotEmpty($controller->loadComponent('Paginator'));
        try {
            $controller->loadComponent('Paginator', ['bad' => 'settings']);
            $this->fail('No exception');
        } catch (\RuntimeException $e) {
            $this->assertContains('The "Paginator" alias has already been loaded', $e->getMessage());
        }
    }

    /**
     * Test the isAction method.
     *
     * @return void
     */
    public function testIsAction()
    {
        $request = new ServerRequest(['url' => '/']);
        $response = $this->getMockBuilder('Cake\Http\Response')->getMock();
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
    public function testBeforeRenderViewVariables()
    {
        $controller = new PostsController();

        $controller->getEventManager()->on('Controller.beforeRender', function (EventInterface $event) {
            /* @var Controller $controller */
            $controller = $event->getSubject();

            $controller->set('testVariable', 'test');
        });

        $controller->dispatchEvent('Controller.beforeRender');
        $view = $controller->createView();

        $this->assertArrayHasKey('testVariable', $view->viewVars);
    }

    /**
     * Test name getter and setter.
     *
     * @return void
     */
    public function testName()
    {
        $controller = new PostsController();
        $this->assertEquals('Posts', $controller->getName());

        $this->assertSame($controller, $controller->setName('Articles'));
        $this->assertEquals('Articles', $controller->getName());
    }

    /**
     * Test plugin getter and setter.
     *
     * @return void
     */
    public function testPlugin()
    {
        $controller = new PostsController();
        $this->assertEquals('', $controller->getPlugin());

        $this->assertSame($controller, $controller->setPlugin('Articles'));
        $this->assertEquals('Articles', $controller->getPlugin());
    }

    /**
     * Test request getter and setter.
     *
     * @return void
     */
    public function testRequest()
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

        $this->assertEquals('Posts', $controller->getRequest()->getParam('plugin'));
        $this->assertEquals(['foo', 'bar'], $controller->getRequest()->getParam('pass'));
    }

    /**
     * Test response getter and setter.
     *
     * @return void
     */
    public function testResponse()
    {
        $controller = new PostsController();
        $this->assertInstanceOf(Response::class, $controller->getResponse());

        $response = new Response;
        $this->assertSame($controller, $controller->setResponse($response));
        $this->assertSame($response, $controller->getResponse());
    }

    /**
     * Test autoRender getter and setter.
     *
     * @return void
     */
    public function testAutoRender()
    {
        $controller = new PostsController();
        $this->assertTrue($controller->isAutoRenderEnabled());

        $this->assertSame($controller, $controller->disableAutoRender());
        $this->assertFalse($controller->isAutoRenderEnabled());

        $this->assertSame($controller, $controller->enableAutoRender());
        $this->assertTrue($controller->isAutoRenderEnabled());
    }
}
