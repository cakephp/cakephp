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
 * @link          http://cakephp.org CakePHP Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller;

use Cake\Controller\Component;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use TestApp\Controller\Admin\PostsController;
use TestPlugin\Controller\TestPluginController;

/**
 * AppController class
 *
 */
class ControllerTestAppController extends Controller
{

    /**
     * helpers property
     *
     * @var array
     */
    public $helpers = ['Html'];

    /**
     * modelClass property
     *
     * @var string
     */
    public $modelClass = 'Posts';

    /**
     * components property
     *
     * @var array
     */
    public $components = ['Cookie'];
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
     * helpers property
     *
     * @var array
     */
    public $helpers = ['Html'];

    /**
     * components property
     *
     * @var array
     */
    public $components = ['Security'];

    /**
     * modelClass property
     *
     * @var string
     */
    public $modelClass = 'Comments';

    /**
     * beforeFilter handler
     *
     * @param \Cake\Event\Event $event
     * @retun void
     */
    public function beforeFilter(Event $event)
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
        $this->request->data = [
            'testId' => $testId,
            'test2Id' => $testTwoId
        ];
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
        $this->request->data = [
            'testId' => $testId,
            'test2Id' => $testTwoId
        ];
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
     * @param Event $event
     * @return void
     */
    public function startup(Event $event)
    {
    }

    /**
     * shutdown method
     *
     * @param Event $event
     * @return void
     */
    public function shutdown(Event $event)
    {
    }

    /**
     * beforeRender callback
     *
     * @param Event $event
     * @return void
     */
    public function beforeRender(Event $event)
    {
        $controller = $event->subject();
        if ($this->viewclass) {
            $controller->viewClass = $this->viewclass;
        }
    }
}

/**
 * AnotherTestController class
 *
 */
class AnotherTestController extends ControllerTestAppController
{
}

/**
 * ControllerTest class
 *
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
        'core.posts'
    ];

    /**
     * reset environment.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        Configure::write('App.namespace', 'TestApp');
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
        $request = new Request('controller_posts/index');
        $response = $this->getMockBuilder('Cake\Network\Response')->getMock();
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
        $request = new Request('controller_posts/index');
        $response = $this->getMockBuilder('Cake\Network\Response')->getMock();
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
        $Controller->plugin = 'TestPlugin';

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

        $request = new Request();
        $response = new Response();
        $controller = new \TestApp\Controller\PostsController($request, $response);
        $this->assertEquals('Posts', $controller->modelClass);
        $this->assertInstanceOf('Cake\ORM\Table', $controller->Posts);

        $controller = new \TestApp\Controller\Admin\PostsController($request, $response);
        $this->assertEquals('Posts', $controller->modelClass);
        $this->assertInstanceOf('Cake\ORM\Table', $controller->Posts);

        $request->params['plugin'] = 'TestPlugin';
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

        $Controller = new TestPluginController(new Request(), new Response());
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

        $request = new Request('controller_posts/index');
        $request->params['action'] = 'index';

        $Controller = new Controller($request, new Response());
        $Controller->viewBuilder()->templatePath('Posts');

        $result = $Controller->render('index');
        $this->assertRegExp('/posts index/', (string)$result);

        $Controller->viewBuilder()->template('index');
        $result = $Controller->render();
        $this->assertRegExp('/posts index/', (string)$result);

        $result = $Controller->render('/Element/test_element');
        $this->assertRegExp('/this is the test element/', (string)$result);
    }

    /**
     * test that a component beforeRender can change the controller view class.
     *
     * @return void
     */
    public function testBeforeRenderCallbackChangingViewClass()
    {
        $Controller = new Controller(new Request, new Response());

        $Controller->eventManager()->on('Controller.beforeRender', function ($event) {
            $controller = $event->subject();
            $controller->viewClass = 'Json';
        });

        $Controller->set([
            'test' => 'value',
            '_serialize' => ['test']
        ]);
        $debug = Configure::read('debug');
        Configure::write('debug', false);
        $result = $Controller->render('index');
        $this->assertEquals('{"test":"value"}', $result->body());
        Configure::write('debug', $debug);
    }

    /**
     * test that a component beforeRender can change the controller view class.
     *
     * @return void
     */
    public function testBeforeRenderEventCancelsRender()
    {
        $Controller = new Controller(new Request, new Response());

        $Controller->eventManager()->attach(function ($event) {
            return false;
        }, 'Controller.beforeRender');

        $result = $Controller->render('index');
        $this->assertInstanceOf('Cake\Network\Response', $result);
    }

    /**
     * Generates status codes for redirect test.
     *
     * @return void
     */
    public static function statusCodeProvider()
    {
        return [
            [300, "Multiple Choices"],
            [301, "Moved Permanently"],
            [302, "Found"],
            [303, "See Other"],
            [304, "Not Modified"],
            [305, "Use Proxy"],
            [307, "Temporary Redirect"],
            [403, "Forbidden"],
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
        $this->assertEquals($code, $response->statusCode());
        $this->assertEquals('http://cakephp.org', $response->header()['Location']);
        $this->assertFalse($Controller->autoRender);
    }

    /**
     * test that beforeRedirect callbacks can set the URL that is being redirected to.
     *
     * @return void
     */
    public function testRedirectBeforeRedirectModifyingUrl()
    {
        $Controller = new Controller(null, new Response());

        $Controller->eventManager()->attach(function ($event, $url, $response) {
            $response->location('http://book.cakephp.org');
        }, 'Controller.beforeRedirect');

        $response = $Controller->redirect('http://cakephp.org', 301);
        $this->assertEquals('http://book.cakephp.org', $response->header()['Location']);
        $this->assertEquals(301, $response->statusCode());
    }

    /**
     * test that beforeRedirect callback returning null doesn't affect things.
     *
     * @return void
     */
    public function testRedirectBeforeRedirectModifyingStatusCode()
    {
        $Response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['stop'])
            ->getMock();
        $Controller = new Controller(null, $Response);

        $Controller->eventManager()->attach(function ($event, $url, $response) {
            $response->statusCode(302);
        }, 'Controller.beforeRedirect');

        $response = $Controller->redirect('http://cakephp.org', 301);

        $this->assertEquals('http://cakephp.org', $response->header()['Location']);
        $this->assertEquals(302, $response->statusCode());
    }

    public function testRedirectBeforeRedirectListenerReturnResponse()
    {
        $Response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['stop', 'header', 'statusCode'])
            ->getMock();
        $Controller = new Controller(null, $Response);

        $newResponse = new Response;
        $Controller->eventManager()->on('Controller.beforeRedirect', function ($event, $url, $response) use ($newResponse) {
            return $newResponse;
        });

        $result = $Controller->redirect('http://cakephp.org');
        $this->assertSame($newResponse, $result);
    }

    /**
     * testMergeVars method
     *
     * @return void
     */
    public function testMergeVars()
    {
        $request = new Request();
        $TestController = new TestController($request);

        $expected = [
            'Html' => null,
        ];
        $this->assertEquals($expected, $TestController->helpers);

        $expected = [
            'Security' => null,
            'Cookie' => null,
        ];
        $this->assertEquals($expected, $TestController->components);

        $TestController = new AnotherTestController($request);
        $this->assertEquals(
            'Posts',
            $TestController->modelClass,
            'modelClass should not be overwritten when defined.'
        );
    }

    /**
     * testReferer method
     *
     * @return void
     */
    public function testReferer()
    {
        $request = $this->getMockBuilder('Cake\Network\Request')
            ->setMethods(['referer'])
            ->getMock();
        $request->expects($this->any())->method('referer')
            ->with(true)
            ->will($this->returnValue('/posts/index'));

        $Controller = new Controller($request);
        $result = $Controller->referer(null, true);
        $this->assertEquals('/posts/index', $result);

        $request = $this->getMockBuilder('Cake\Network\Request')
            ->setMethods(['referer'])
            ->getMock();
        $request->expects($this->any())->method('referer')
            ->with(true)
            ->will($this->returnValue('/posts/index'));
        $Controller = new Controller($request);
        $result = $Controller->referer(['controller' => 'posts', 'action' => 'index'], true);
        $this->assertEquals('/posts/index', $result);

        $request = $this->getMockBuilder('Cake\Network\Request')
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
        $request = $this->getMockBuilder('Cake\Network\Request')
            ->setMethods(['referer'])
            ->getMock();
        $request->base = '/base';
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
        $request = new Request('controller_posts/index');

        $TestController = new TestController($request);
        $TestController->setAction('view', 1, 2);
        $expected = ['testId' => 1, 'test2Id' => 2];
        $this->assertSame($expected, $TestController->request->data);
        $this->assertSame('view', $TestController->request->params['action']);
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
        $request = new Request('controller_posts/index');
        $request->params['pass'] = [];
        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['httpCodes'])
            ->getMock();

        $Controller = new Controller($request, $response);
        $Controller->request->query = [
            'url' => [],
            'prefix' => [
                'page' => 2,
                'limit' => 2,
            ]
        ];

        $this->assertEquals([], $Controller->paginate);

        $this->assertNotContains('Paginator', $Controller->helpers);
        $this->assertArrayNotHasKey('Paginator', $Controller->helpers);

        $results = $Controller->paginate('Posts');
        $this->assertInstanceOf('Cake\Datasource\ResultSetInterface', $results);
        $this->assertCount(3, $results);

        $results = $Controller->paginate(TableRegistry::get('Posts'));
        $this->assertInstanceOf('Cake\Datasource\ResultSetInterface', $results);
        $this->assertCount(3, $results);

        $this->assertSame($Controller->request->params['paging']['Posts']['page'], 1);
        $this->assertSame($Controller->request->params['paging']['Posts']['pageCount'], 1);
        $this->assertSame($Controller->request->params['paging']['Posts']['prevPage'], false);
        $this->assertSame($Controller->request->params['paging']['Posts']['nextPage'], false);

        $results = $Controller->paginate(TableRegistry::get('Posts'), ['prefix' => 'prefix']);
        $this->assertInstanceOf('Cake\Datasource\ResultSetInterface', $results);
        $this->assertCount(1, $results);

        $this->assertSame($Controller->request->params['paging']['Posts']['page'], 2);
        $this->assertSame($Controller->request->params['paging']['Posts']['pageCount'], 2);
        $this->assertSame($Controller->request->params['paging']['Posts']['prevPage'], true);
        $this->assertSame($Controller->request->params['paging']['Posts']['nextPage'], false);

    }

    /**
     * test that paginate uses modelClass property.
     *
     * @return void
     */
    public function testPaginateUsesModelClass()
    {
        $request = new Request('controller_posts/index');
        $request->params['pass'] = [];
        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['httpCodes'])
            ->getMock();

        $Controller = new Controller($request, $response);
        $Controller->request->query['url'] = [];
        $Controller->modelClass = 'Posts';
        $results = $Controller->paginate();

        $this->assertInstanceOf('Cake\Datasource\ResultSetInterface', $results);
    }

    /**
     * testMissingAction method
     *
     * @expectedException \Cake\Controller\Exception\MissingActionException
     * @expectedExceptionMessage Action TestController::missing() could not be found, or is not accessible.
     * @return void
     */
    public function testInvokeActionMissingAction()
    {
        $url = new Request('test/missing');
        $url->addParams(['controller' => 'Test', 'action' => 'missing']);
        $response = $this->getMockBuilder('Cake\Network\Response')->getMock();

        $Controller = new TestController($url, $response);
        $Controller->invokeAction();
    }

    /**
     * test invoking private methods.
     *
     * @expectedException \Cake\Controller\Exception\MissingActionException
     * @expectedExceptionMessage Action TestController::private_m() could not be found, or is not accessible.
     * @return void
     */
    public function testInvokeActionPrivate()
    {
        $url = new Request('test/private_m/');
        $url->addParams(['controller' => 'Test', 'action' => 'private_m']);
        $response = $this->getMockBuilder('Cake\Network\Response')->getMock();

        $Controller = new TestController($url, $response);
        $Controller->invokeAction();
    }

    /**
     * test invoking protected methods.
     *
     * @expectedException \Cake\Controller\Exception\MissingActionException
     * @expectedExceptionMessage Action TestController::protected_m() could not be found, or is not accessible.
     * @return void
     */
    public function testInvokeActionProtected()
    {
        $url = new Request('test/protected_m/');
        $url->addParams(['controller' => 'Test', 'action' => 'protected_m']);
        $response = $this->getMockBuilder('Cake\Network\Response')->getMock();

        $Controller = new TestController($url, $response);
        $Controller->invokeAction();
    }

    /**
     * test invoking controller methods.
     *
     * @expectedException \Cake\Controller\Exception\MissingActionException
     * @expectedExceptionMessage Action TestController::redirect() could not be found, or is not accessible.
     * @return void
     */
    public function testInvokeActionBaseMethods()
    {
        $url = new Request('test/redirect/');
        $url->addParams(['controller' => 'Test', 'action' => 'redirect']);
        $response = $this->getMockBuilder('Cake\Network\Response')->getMock();

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
        $url = new Request('test/returner/');
        $url->addParams([
            'controller' => 'Test',
            'action' => 'returner',
            'pass' => []
        ]);
        $response = $this->getMockBuilder('Cake\Network\Response')->getMock();

        $Controller = new TestController($url, $response);
        $result = $Controller->invokeAction();
        $this->assertEquals('I am from the controller.', $result);
    }

    /**
     * test that a classes namespace is used in the viewPath.
     *
     * @return void
     */
    public function testViewPathConventions()
    {
        $request = new Request('admin/posts');
        $request->addParams([
            'prefix' => 'admin'
        ]);
        $response = $this->getMockBuilder('Cake\Network\Response')->getMock();
        $Controller = new \TestApp\Controller\Admin\PostsController($request, $response);
        $Controller->eventManager()->on('Controller.beforeRender', function (Event $e) {
            return $e->subject()->response;
        });
        $Controller->render();
        $this->assertEquals('Admin' . DS . 'Posts', $Controller->viewBuilder()->templatePath());

        $request->addParams([
            'prefix' => 'admin/super'
        ]);
        $response = $this->getMockBuilder('Cake\Network\Response')->getMock();
        $Controller = new \TestApp\Controller\Admin\PostsController($request, $response);
        $Controller->eventManager()->on('Controller.beforeRender', function (Event $e) {
            return $e->subject()->response;
        });
        $Controller->render();
        $this->assertEquals('Admin' . DS . 'Super' . DS . 'Posts', $Controller->viewBuilder()->templatePath());

        $request = new Request('pages/home');
        $request->addParams([
            'prefix' => false
        ]);
        $Controller = new \TestApp\Controller\PagesController($request, $response);
        $Controller->eventManager()->on('Controller.beforeRender', function (Event $e) {
            return $e->subject()->response;
        });
        $Controller->render();
        $this->assertEquals('Pages', $Controller->viewBuilder()->templatePath());
    }

    /**
     * Test the components() method.
     *
     * @return void
     */
    public function testComponents()
    {
        $request = new Request('/');
        $response = $this->getMockBuilder('Cake\Network\Response')->getMock();

        $controller = new TestController($request, $response);
        $this->assertInstanceOf('Cake\Controller\ComponentRegistry', $controller->components());

        $result = $controller->components();
        $this->assertSame($result, $controller->components());
    }

    /**
     * Test the components() method with the custom ObjectRegistry.
     *
     * @return void
     */
    public function testComponentsWithCustomRegistry()
    {
        $request = new Request('/');
        $response = $this->getMockBuilder('Cake\Network\Response')->getMock();
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
        $request = new Request('/');
        $response = $this->getMockBuilder('Cake\Network\Response')->getMock();

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
        $request = new Request('/');
        $response = $this->getMockBuilder('Cake\Network\Response')->getMock();

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
        $request = new Request('/');
        $response = $this->getMockBuilder('Cake\Network\Response')->getMock();
        $controller = new TestController($request, $response);

        $this->assertFalse($controller->isAction('redirect'));
        $this->assertFalse($controller->isAction('beforeFilter'));
        $this->assertTrue($controller->isAction('index'));
    }

    /**
     * Test declared deprecated properties like $theme are properly passed to view.
     *
     * @return void
     */
    public function testDeclaredDeprecatedProperty()
    {
        $controller = new TestController(new Request(), new Response());
        $theme = $controller->theme;

        // @codingStandardsIgnoreStart
        $this->assertEquals($theme, @$controller->createView()->theme);
        // @codingStandardsIgnoreEnd
    }

    /**
     * Test that view variables are being set after the beforeRender event gets dispatched
     *
     * @return void
     */
    public function testBeforeRenderViewVariables()
    {
        $controller = new PostsController();

        $controller->eventManager()->on('Controller.beforeRender', function (Event $event) {
            /* @var Controller $controller */
            $controller = $event->subject();

            $controller->set('testVariable', 'test');
        });

        $controller->render('index');

        $this->assertArrayHasKey('testVariable', $controller->View->viewVars);
    }
}
