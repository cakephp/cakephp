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
 * DispatcherTest class
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

        $this->dispatcher = new Dispatcher();
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
     * @expectedExceptionMessage Controller class Interface could not be found.
     * @return void
     */
    public function testMissingControllerInterface()
    {
        $request = new Request([
            'url' => 'interface/index',
            'params' => [
                'controller' => 'Interface',
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
            ]
        ]);
        $response = $this->getMockBuilder('Cake\Network\Response')->getMock();
        $response->expects($this->once())
            ->method('send');

        $result = $this->dispatcher->dispatch($url, $response);
        $this->assertNull($result);
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
        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['_sendHeader'])
            ->getMock();

        ob_start();
        $this->dispatcher->dispatch($request, $response);
        $result = ob_get_clean();

        $this->assertEquals('new response', $result);
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
        $filter = $this->getMockBuilder('Cake\Routing\DispatcherFilter')
            ->setMethods(['beforeDispatch', 'afterDispatch'])
            ->getMock();

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
        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['send'])
            ->getMock();
        $this->dispatcher->dispatch($request, $response);
    }

    /**
     * Test dispatcher filters being called and changing the response.
     *
     * @return void
     */
    public function testBeforeDispatchAbortDispatch()
    {
        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['send'])
            ->getMock();
        $response->expects($this->once())
            ->method('send');

        $filter = $this->getMockBuilder('Cake\Routing\DispatcherFilter')
            ->setMethods(['beforeDispatch', 'afterDispatch'])
            ->getMock();
        $filter->expects($this->once())
            ->method('beforeDispatch')
            ->will($this->returnValue($response));

        $filter->expects($this->never())
            ->method('afterDispatch');

        $request = new Request([
            'url' => '/',
            'params' => [
                'controller' => 'Pages',
                'action' => 'display',
                'home',
                'pass' => []
            ]
        ]);
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
        $response = $this->getMockBuilder('Cake\Network\Response')
            ->setMethods(['_sendHeader', 'send'])
            ->getMock();
        $response->expects($this->once())
            ->method('send');

        $filter = $this->getMockBuilder('Cake\Routing\DispatcherFilter')
            ->setMethods(['beforeDispatch', 'afterDispatch'])
            ->getMock();

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
