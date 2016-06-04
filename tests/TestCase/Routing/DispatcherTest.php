<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Routing;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Network\Session;
use Cake\Routing\Dispatcher;
use Cake\Routing\Filter\ControllerFactoryFilter;
use Cake\TestSuite\TestCase;

/**
 * A testing stub that doesn't send headers.
 */
class DispatcherMockResponse extends Response
{

    protected function _sendHeader($name, $value = null)
    {
        return $name . ' ' . $value;
    }
}

/**
 * TestDispatcher class
 */
class TestDispatcher extends Dispatcher
{

    /**
     * Controller instance, made publicly available for testing
     *
     * @var Controller
     */
    public $controller;

    /**
     * invoke method
     *
     * @param \Cake\Controller\Controller $controller
     * @return \Cake\Network\Response $response
     */
    protected function _invoke(Controller $controller)
    {
        $this->controller = $controller;
        return parent::_invoke($controller);
    }
}

/**
 * MyPluginAppController class
 *
 */
class MyPluginAppController extends Controller
{
}

interface DispatcherTestInterfaceController
{

    public function index();
}

/**
 * MyPluginController class
 *
 */
class MyPluginController extends MyPluginAppController
{

    /**
     * name property
     *
     * @var string
     */
    public $name = 'MyPlugin';

    /**
     * index method
     *
     * @return void
     */
    public function index()
    {
        return true;
    }

    /**
     * add method
     *
     * @return void
     */
    public function add()
    {
        return true;
    }

    /**
     * admin_add method
     *
     * @param mixed $id
     * @return void
     */
    public function admin_add($id = null)
    {
        return $id;
    }
}

/**
 * OtherPagesController class
 *
 */
class OtherPagesController extends MyPluginAppController
{

    /**
     * name property
     *
     * @var string
     */
    public $name = 'OtherPages';

    /**
     * display method
     *
     * @param string $page
     * @return void
     */
    public function display($page = null)
    {
        return $page;
    }

    /**
     * index method
     *
     * @return void
     */
    public function index()
    {
        return true;
    }
}

/**
 * ArticlesTestAppController class
 *
 */
class ArticlesTestAppController extends Controller
{
}

/**
 * ArticlesTestController class
 *
 */
class ArticlesTestController extends ArticlesTestAppController
{

    /**
     * name property
     *
     * @var string
     */
    public $name = 'ArticlesTest';

    /**
     * admin_index method
     *
     * @return void
     */
    public function admin_index()
    {
        return true;
    }

    /**
     * fake index method.
     *
     * @return void
     */
    public function index()
    {
        return true;
    }
}

/**
 * DispatcherTest class
 *
 */
class DispatcherTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $_GET = [];

        Configure::write('App.base', false);
        Configure::write('App.baseUrl', false);
        Configure::write('App.dir', 'app');
        Configure::write('App.webroot', 'webroot');
        Configure::write('App.namespace', 'TestApp');

        $this->dispatcher = new TestDispatcher();
        $this->dispatcher->addFilter(new ControllerFactoryFilter());
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Plugin::unload();
    }

    /**
     * testMissingController method
     *
     * @expectedException \Cake\Routing\Exception\MissingControllerException
     * @expectedExceptionMessage Controller class SomeController could not be found.
     * @return void
     */
    public function testMissingController()
    {
        $request = new Request([
            'url' => 'some_controller/home',
            'params' => [
                'controller' => 'SomeController',
                'action' => 'home',
            ]
        ]);
        $response = $this->getMockBuilder('Cake\Network\Response')->getMock();
        $this->dispatcher->dispatch($request, $response, ['return' => 1]);
    }

    /**
     * testMissingControllerInterface method
     *
     * @expectedException \Cake\Routing\Exception\MissingControllerException
     * @expectedExceptionMessage Controller class DispatcherTestInterface could not be found.
     * @return void
     */
    public function testMissingControllerInterface()
    {
        $request = new Request([
            'url' => 'dispatcher_test_interface/index',
            'params' => [
                'controller' => 'DispatcherTestInterface',
                'action' => 'index',
            ]
        ]);
        $url = new Request('dispatcher_test_interface/index');
        $response = $this->getMockBuilder('Cake\Network\Response')->getMock();
        $this->dispatcher->dispatch($request, $response, ['return' => 1]);
    }

    /**
     * testMissingControllerInterface method
     *
     * @expectedException \Cake\Routing\Exception\MissingControllerException
     * @expectedExceptionMessage Controller class Abstract could not be found.
     * @return void
     */
    public function testMissingControllerAbstract()
    {
        $request = new Request([
            'url' => 'abstract/index',
            'params' => [
                'controller' => 'Abstract',
                'action' => 'index',
            ]
        ]);
        $response = $this->getMockBuilder('Cake\Network\Response')->getMock();
        $this->dispatcher->dispatch($request, $response, ['return' => 1]);
    }

    /**
     * Test that lowercase controller names result in missing controller errors.
     *
     * In case-insensitive file systems, lowercase controller names will kind of work.
     * This causes annoying deployment issues for lots of folks.
     *
     * @expectedException \Cake\Routing\Exception\MissingControllerException
     * @expectedExceptionMessage Controller class somepages could not be found.
     * @return void
     */
    public function testMissingControllerLowercase()
    {
        $request = new Request([
            'url' => 'pages/home',
            'params' => [
                'controller' => 'somepages',
                'action' => 'display',
                'pass' => ['home'],
            ]
        ]);
        $response = $this->getMockBuilder('Cake\Network\Response')->getMock();
        $this->dispatcher->dispatch($request, $response, ['return' => 1]);
    }

    /**
     * testDispatch method
     *
     * @return void
     */
    public function testDispatchBasic()
    {
        $url = new Request([
            'url' => 'pages/home',
            'params' => [
                'controller' => 'Pages',
                'action' => 'display',
                'pass' => ['extract'],
                'return' => 1
            ]
        ]);
        $response = $this->getMockBuilder('Cake\Network\Response')->getMock();

        $this->dispatcher->dispatch($url, $response);
        $expected = 'Pages';
        $this->assertEquals($expected, $this->dispatcher->controller->name);
    }

    /**
     * Test that Dispatcher handles actions that return response objects.
     *
     * @return void
     */
    public function testDispatchActionReturnsResponse()
    {
        $request = new Request([
            'url' => 'some_pages/responseGenerator',
            'params' => [
                'controller' => 'SomePages',
                'action' => 'responseGenerator',
                'pass' => []
            ]
        ]);
        $response = $this->getMock('Cake\Network\Response', ['_sendHeader']);

        ob_start();
        $this->dispatcher->dispatch($request, $response);
        $result = ob_get_clean();

        $this->assertEquals('new response', $result);
    }

    /**
     * testPrefixDispatch method
     *
     * @return void
     */
    public function testPrefixDispatch()
    {
        $request = new Request([
            'url' => 'admin/posts/index',
            'params' => [
                'prefix' => 'Admin',
                'controller' => 'Posts',
                'action' => 'index',
                'pass' => [],
                'return' => 1
            ]
        ]);
        $response = $this->getMockBuilder('Cake\Network\Response')->getMock();

        $this->dispatcher->dispatch($request, $response);

        $this->assertInstanceOf(
            'TestApp\Controller\Admin\PostsController',
            $this->dispatcher->controller
        );
        $expected = '/admin/posts/index';
        $this->assertSame($expected, $request->here);
    }

    /**
     * test prefix dispatching in a plugin.
     *
     * @return void
     */
    public function testPrefixDispatchPlugin()
    {
        Plugin::load('TestPlugin');

        $request = new Request([
            'url' => 'admin/test_plugin/comments/index',
            'params' => [
                'plugin' => 'TestPlugin',
                'prefix' => 'Admin',
                'controller' => 'Comments',
                'action' => 'index',
                'pass' => [],
                'return' => 1
            ]
        ]);
        $response = $this->getMockBuilder('Cake\Network\Response')->getMock();

        $this->dispatcher->dispatch($request, $response);

        $this->assertInstanceOf(
            'TestPlugin\Controller\Admin\CommentsController',
            $this->dispatcher->controller
        );
    }

    /**
     * test forbidden controller names.
     *
     * @expectedException \Cake\Routing\Exception\MissingControllerException
     * @expectedExceptionMessage Controller class TestPlugin.Tests could not be found.
     * @return void
     */
    public function testDispatchBadPluginName()
    {
        Plugin::load('TestPlugin');

        $request = new Request([
            'url' => 'TestPlugin.Tests/index',
            'params' => [
                'plugin' => '',
                'controller' => 'TestPlugin.Tests',
                'action' => 'index',
                'pass' => [],
                'return' => 1
            ]
        ]);
        $response = $this->getMockBuilder('Cake\Network\Response')->getMock();
        $this->dispatcher->dispatch($request, $response);
    }

    /**
     * test forbidden controller names.
     *
     * @expectedException \Cake\Routing\Exception\MissingControllerException
     * @expectedExceptionMessage Controller class TestApp\Controller\PostsController could not be found.
     * @return void
     */
    public function testDispatchBadName()
    {
        $request = new Request([
            'url' => 'TestApp%5CController%5CPostsController/index',
            'params' => [
                'plugin' => '',
                'controller' => 'TestApp\Controller\PostsController',
                'action' => 'index',
                'pass' => [],
                'return' => 1
            ]
        ]);
        $response = $this->getMockBuilder('Cake\Network\Response')->getMock();
        $this->dispatcher->dispatch($request, $response);
    }

    /**
     * Test dispatcher filters being called.
     *
     * @return void
     */
    public function testDispatcherFilter()
    {
        $filter = $this->getMock(
            'Cake\Routing\DispatcherFilter',
            ['beforeDispatch', 'afterDispatch']
        );

        $filter->expects($this->at(0))
            ->method('beforeDispatch');
        $filter->expects($this->at(1))
            ->method('afterDispatch');
        $this->dispatcher->addFilter($filter);

        $request = new Request([
            'url' => '/',
            'params' => [
                'controller' => 'Pages',
                'action' => 'display',
                'home',
                'pass' => []
            ]
        ]);
        $response = $this->getMock('Cake\Network\Response', ['send']);
        $this->dispatcher->dispatch($request, $response);
    }

    /**
     * Test dispatcher filters being called and changing the response.
     *
     * @return void
     */
    public function testBeforeDispatchAbortDispatch()
    {
        $response = $this->getMock('Cake\Network\Response', ['send']);
        $response->expects($this->once())
            ->method('send');

        $filter = $this->getMock(
            'Cake\Routing\DispatcherFilter',
            ['beforeDispatch', 'afterDispatch']
        );
        $filter->expects($this->once())
            ->method('beforeDispatch')
            ->will($this->returnValue($response));

        $filter->expects($this->never())
            ->method('afterDispatch');

        $request = new Request();
        $res = new Response();
        $this->dispatcher->addFilter($filter);
        $this->dispatcher->dispatch($request, $res);
    }

    /**
     * Test dispatcher filters being called and changing the response.
     *
     * @return void
     */
    public function testAfterDispatchReplaceResponse()
    {
        $response = $this->getMock('Cake\Network\Response', ['_sendHeader', 'send']);
        $response->expects($this->once())
            ->method('send');

        $filter = $this->getMock(
            'Cake\Routing\DispatcherFilter',
            ['beforeDispatch', 'afterDispatch']
        );

        $filter->expects($this->once())
            ->method('afterDispatch')
            ->will($this->returnValue($response));

        $request = new Request([
            'url' => '/posts',
            'params' => [
                'plugin' => null,
                'controller' => 'Posts',
                'action' => 'index',
                'pass' => [],
            ],
            'session' => new Session
        ]);
        $this->dispatcher->addFilter($filter);
        $this->dispatcher->dispatch($request, $response);
    }
}
