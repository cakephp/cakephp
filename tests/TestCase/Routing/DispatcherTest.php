<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Routing;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Http\Session;
use Cake\Routing\Dispatcher;
use Cake\Routing\Filter\ControllerFactoryFilter;
use Cake\TestSuite\TestCase;

/**
 * DispatcherTest class
 *
 * @group deprecated
 */
class DispatcherTest extends TestCase
{
    protected $errorLevel;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $_GET = [];

        $this->errorLevel = error_reporting(E_ALL ^ E_USER_DEPRECATED);
        Configure::write('App.base', false);
        Configure::write('App.baseUrl', false);
        Configure::write('App.dir', 'app');
        Configure::write('App.webroot', 'webroot');
        static::setAppNamespace();

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
        error_reporting($this->errorLevel);
        parent::tearDown();
        Plugin::unload();
    }

    /**
     * testMissingController method
     *
     * @return void
     */
    public function testMissingController()
    {
        $this->expectException(\Cake\Routing\Exception\MissingControllerException::class);
        $this->expectExceptionMessage('Controller class SomeController could not be found.');
        $request = new ServerRequest([
            'url' => 'some_controller/home',
            'params' => [
                'controller' => 'SomeController',
                'action' => 'home',
            ]
        ]);
        $response = $this->getMockBuilder('Cake\Http\Response')->getMock();
        $this->dispatcher->dispatch($request, $response, ['return' => 1]);
    }

    /**
     * testMissingControllerInterface method
     *
     * @return void
     */
    public function testMissingControllerInterface()
    {
        $this->expectException(\Cake\Routing\Exception\MissingControllerException::class);
        $this->expectExceptionMessage('Controller class Interface could not be found.');
        $request = new ServerRequest([
            'url' => 'interface/index',
            'params' => [
                'controller' => 'Interface',
                'action' => 'index',
            ]
        ]);
        $url = new ServerRequest('dispatcher_test_interface/index');
        $response = $this->getMockBuilder('Cake\Http\Response')->getMock();
        $this->dispatcher->dispatch($request, $response, ['return' => 1]);
    }

    /**
     * testMissingControllerInterface method
     *
     * @return void
     */
    public function testMissingControllerAbstract()
    {
        $this->expectException(\Cake\Routing\Exception\MissingControllerException::class);
        $this->expectExceptionMessage('Controller class Abstract could not be found.');
        $request = new ServerRequest([
            'url' => 'abstract/index',
            'params' => [
                'controller' => 'Abstract',
                'action' => 'index',
            ]
        ]);
        $response = $this->getMockBuilder('Cake\Http\Response')->getMock();
        $this->dispatcher->dispatch($request, $response, ['return' => 1]);
    }

    /**
     * Test that lowercase controller names result in missing controller errors.
     *
     * In case-insensitive file systems, lowercase controller names will kind of work.
     * This causes annoying deployment issues for lots of folks.
     *
     * @return void
     */
    public function testMissingControllerLowercase()
    {
        $this->expectException(\Cake\Routing\Exception\MissingControllerException::class);
        $this->expectExceptionMessage('Controller class somepages could not be found.');
        $request = new ServerRequest([
            'url' => 'pages/home',
            'params' => [
                'controller' => 'somepages',
                'action' => 'display',
                'pass' => ['home'],
            ]
        ]);
        $response = $this->getMockBuilder('Cake\Http\Response')->getMock();
        $this->dispatcher->dispatch($request, $response, ['return' => 1]);
    }

    /**
     * testDispatch method
     *
     * @return void
     */
    public function testDispatchBasic()
    {
        $url = new ServerRequest([
            'url' => 'pages/home',
            'params' => [
                'controller' => 'Pages',
                'action' => 'display',
                'pass' => ['extract'],
            ]
        ]);
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods(['send'])
            ->getMock();
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
        $request = new ServerRequest([
            'url' => 'some_pages/responseGenerator',
            'params' => [
                'controller' => 'SomePages',
                'action' => 'responseGenerator',
                'pass' => []
            ]
        ]);
        $response = $this->getMockBuilder('Cake\Http\Response')
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
     * @return void
     */
    public function testDispatchBadPluginName()
    {
        $this->expectException(\Cake\Routing\Exception\MissingControllerException::class);
        $this->expectExceptionMessage('Controller class TestPlugin.Tests could not be found.');
        Plugin::load('TestPlugin');

        $request = new ServerRequest([
            'url' => 'TestPlugin.Tests/index',
            'params' => [
                'plugin' => '',
                'controller' => 'TestPlugin.Tests',
                'action' => 'index',
                'pass' => [],
                'return' => 1
            ]
        ]);
        $response = $this->getMockBuilder('Cake\Http\Response')->getMock();
        $this->dispatcher->dispatch($request, $response);
    }

    /**
     * test forbidden controller names.
     *
     * @return void
     */
    public function testDispatchBadName()
    {
        $this->expectException(\Cake\Routing\Exception\MissingControllerException::class);
        $this->expectExceptionMessage('Controller class TestApp\Controller\PostsController could not be found.');
        $request = new ServerRequest([
            'url' => 'TestApp%5CController%5CPostsController/index',
            'params' => [
                'plugin' => '',
                'controller' => 'TestApp\Controller\PostsController',
                'action' => 'index',
                'pass' => [],
                'return' => 1
            ]
        ]);
        $response = $this->getMockBuilder('Cake\Http\Response')->getMock();
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

        $request = new ServerRequest([
            'url' => '/',
            'params' => [
                'controller' => 'Pages',
                'action' => 'display',
                'home',
                'pass' => []
            ]
        ]);
        $response = $this->getMockBuilder('Cake\Http\Response')
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
        $response = $this->getMockBuilder('Cake\Http\Response')
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

        $request = new ServerRequest([
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
        $response = $this->getMockBuilder('Cake\Http\Response')
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

        $request = new ServerRequest([
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
