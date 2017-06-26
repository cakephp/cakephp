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
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http;

use Cake\Http\ControllerFactory;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

/**
 * Test case for ControllerFactory.
 */
class ControllerFactoryTest extends TestCase
{
    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        static::setAppNamespace();
        $this->factory = new ControllerFactory();
        $this->response = $this->getMockBuilder('Cake\Http\Response')->getMock();
    }

    /**
     * Test building an application controller
     *
     * @return void
     */
    public function testApplicationController()
    {
        $request = new ServerRequest([
            'url' => 'cakes/index',
            'params' => [
                'controller' => 'Cakes',
                'action' => 'index',
            ]
        ]);
        $result = $this->factory->create($request, $this->response);
        $this->assertInstanceOf('TestApp\Controller\CakesController', $result);
        $this->assertSame($request, $result->request);
        $this->assertSame($this->response, $result->response);
    }

    /**
     * Test building a prefixed app controller.
     *
     * @return void
     */
    public function testPrefixedAppController()
    {
        $request = new ServerRequest([
            'url' => 'admin/posts/index',
            'params' => [
                'prefix' => 'admin',
                'controller' => 'Posts',
                'action' => 'index',
            ]
        ]);
        $result = $this->factory->create($request, $this->response);
        $this->assertInstanceOf(
            'TestApp\Controller\Admin\PostsController',
            $result
        );
        $this->assertSame($request, $result->request);
        $this->assertSame($this->response, $result->response);
    }

    /**
     * Test building a nested prefix app controller
     *
     * @return void
     */
    public function testNestedPrefixedAppController()
    {
        $request = new ServerRequest([
            'url' => 'admin/sub/posts/index',
            'params' => [
                'prefix' => 'admin/sub',
                'controller' => 'Posts',
                'action' => 'index',
            ]
        ]);
        $result = $this->factory->create($request, $this->response);
        $this->assertInstanceOf(
            'TestApp\Controller\Admin\Sub\PostsController',
            $result
        );
        $this->assertSame($request, $result->request);
        $this->assertSame($this->response, $result->response);
    }

    /**
     * Test building a plugin controller
     *
     * @return void
     */
    public function testPluginController()
    {
        $request = new ServerRequest([
            'url' => 'test_plugin/test_plugin/index',
            'params' => [
                'plugin' => 'TestPlugin',
                'controller' => 'TestPlugin',
                'action' => 'index',
            ]
        ]);
        $result = $this->factory->create($request, $this->response);
        $this->assertInstanceOf(
            'TestPlugin\Controller\TestPluginController',
            $result
        );
        $this->assertSame($request, $result->request);
        $this->assertSame($this->response, $result->response);
    }

    /**
     * Test building a vendored plugin controller.
     *
     * @return void
     */
    public function testVendorPluginController()
    {
        $request = new ServerRequest([
            'url' => 'test_plugin_three/ovens/index',
            'params' => [
                'plugin' => 'Company/TestPluginThree',
                'controller' => 'Ovens',
                'action' => 'index',
            ]
        ]);
        $result = $this->factory->create($request, $this->response);
        $this->assertInstanceOf(
            'Company\TestPluginThree\Controller\OvensController',
            $result
        );
        $this->assertSame($request, $result->request);
        $this->assertSame($this->response, $result->response);
    }

    /**
     * Test building a prefixed plugin controller
     *
     * @return void
     */
    public function testPrefixedPluginController()
    {
        $request = new ServerRequest([
            'url' => 'test_plugin/admin/comments',
            'params' => [
                'prefix' => 'admin',
                'plugin' => 'TestPlugin',
                'controller' => 'Comments',
                'action' => 'index',
            ]
        ]);
        $result = $this->factory->create($request, $this->response);
        $this->assertInstanceOf(
            'TestPlugin\Controller\Admin\CommentsController',
            $result
        );
        $this->assertSame($request, $result->request);
        $this->assertSame($this->response, $result->response);
    }

    /**
     * @expectedException \Cake\Routing\Exception\MissingControllerException
     * @expectedExceptionMessage Controller class Abstract could not be found.
     * @return void
     */
    public function testAbstractClassFailure()
    {
        $request = new ServerRequest([
            'url' => 'abstract/index',
            'params' => [
                'controller' => 'Abstract',
                'action' => 'index',
            ]
        ]);
        $this->factory->create($request, $this->response);
    }

    /**
     * @expectedException \Cake\Routing\Exception\MissingControllerException
     * @expectedExceptionMessage Controller class Interface could not be found.
     * @return void
     */
    public function testInterfaceFailure()
    {
        $request = new ServerRequest([
            'url' => 'interface/index',
            'params' => [
                'controller' => 'Interface',
                'action' => 'index',
            ]
        ]);
        $this->factory->create($request, $this->response);
    }

    /**
     * @expectedException \Cake\Routing\Exception\MissingControllerException
     * @expectedExceptionMessage Controller class Invisible could not be found.
     * @return void
     */
    public function testMissingClassFailure()
    {
        $request = new ServerRequest([
            'url' => 'interface/index',
            'params' => [
                'controller' => 'Invisible',
                'action' => 'index',
            ]
        ]);
        $this->factory->create($request, $this->response);
    }

    /**
     * @expectedException \Cake\Routing\Exception\MissingControllerException
     * @expectedExceptionMessage Controller class Admin/Posts could not be found.
     * @return void
     */
    public function testSlashedControllerFailure()
    {
        $request = new ServerRequest([
            'url' => 'admin/posts/index',
            'params' => [
                'controller' => 'Admin/Posts',
                'action' => 'index',
            ]
        ]);
        $this->factory->create($request, $this->response);
    }

    /**
     * @expectedException \Cake\Routing\Exception\MissingControllerException
     * @expectedExceptionMessage Controller class TestApp\Controller\CakesController could not be found.
     * @return void
     */
    public function testAbsoluteReferenceFailure()
    {
        $request = new ServerRequest([
            'url' => 'interface/index',
            'params' => [
                'controller' => 'TestApp\Controller\CakesController',
                'action' => 'index',
            ]
        ]);
        $this->factory->create($request, $this->response);
    }
}
