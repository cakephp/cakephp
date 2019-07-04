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
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http;

use Cake\Http\ActionDispatcher;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use ReflectionProperty;

/**
 * Test case for the ActionDispatcher.
 */
class ActionDispatcherTest extends TestCase
{
    /**
     * Setup
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Router::reload();
        static::setAppNamespace();
        $this->dispatcher = new ActionDispatcher();
    }

    /**
     * Ensure the constructor args end up on the right protected properties.
     *
     * @return void
     */
    public function testConstructorArgs()
    {
        $factory = $this->getMockBuilder('Cake\Http\ControllerFactory')->getMock();
        $dispatcher = new ActionDispatcher($factory);

        $reflect = new ReflectionProperty($dispatcher, 'factory');
        $reflect->setAccessible(true);
        $this->assertSame($factory, $reflect->getValue($dispatcher));
    }

    /**
     * Test that dispatching sets the Router request state.
     *
     * @return void
     */
    public function testDispatchSetsRequestContext()
    {
        $this->assertNull(Router::getRequest());
        $req = new ServerRequest([
            'url' => '/cakes',
            'params' => [
                'plugin' => null,
                'controller' => 'Cakes',
                'action' => 'index',
                'pass' => [],
                'return' => true,
            ],
        ]);
        $res = new Response();
        $this->dispatcher->dispatch($req, $res);
        $this->assertSame($req, Router::getRequest(true));
    }

    /**
     * Test dispatch with autorender
     *
     * @return void
     */
    public function testDispatchAutoRender()
    {
        $request = new ServerRequest([
            'url' => 'posts',
            'params' => [
                'controller' => 'Posts',
                'action' => 'index',
                'pass' => [],
            ],
        ]);
        $response = new Response();
        $result = $this->dispatcher->dispatch($request, $response);
        $this->assertInstanceOf('Cake\Http\Response', $result);
        $this->assertStringContainsString('posts index', (string)$result->getBody());
    }

    /**
     * Test dispatch with autorender=false
     *
     * @return void
     */
    public function testDispatchAutoRenderFalse()
    {
        $request = new ServerRequest([
            'url' => 'posts',
            'params' => [
                'controller' => 'Cakes',
                'action' => 'noRender',
                'pass' => [],
            ],
        ]);
        $response = new Response();
        $result = $this->dispatcher->dispatch($request, $response);
        $this->assertInstanceOf('Cake\Http\Response', $result);
        $this->assertStringContainsString('autoRender false body', (string)$result->getBody());
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
            ],
        ]);
        $response = $this->getMockBuilder('Cake\Http\Response')->getMock();
        $this->dispatcher->dispatch($request, $response);
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
            ],
        ]);
        $response = $this->getMockBuilder('Cake\Http\Response')->getMock();
        $this->dispatcher->dispatch($request, $response);
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
            ],
        ]);
        $response = $this->getMockBuilder('Cake\Http\Response')->getMock();
        $this->dispatcher->dispatch($request, $response);
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
                'plugin' => null,
                'controller' => 'somepages',
                'action' => 'display',
                'pass' => ['home'],
            ],
        ]);
        $response = $this->getMockBuilder('Cake\Http\Response')->getMock();
        $this->dispatcher->dispatch($request, $response);
    }

    /**
     * Ensure that a controller's startup event can stop the request.
     *
     * @return void
     */
    public function testStartupProcessAbort()
    {
        $request = new ServerRequest([
            'url' => 'cakes/index',
            'params' => [
                'plugin' => null,
                'controller' => 'Cakes',
                'action' => 'index',
                'stop' => 'startup',
                'pass' => [],
            ],
        ]);
        $response = new Response();
        $result = $this->dispatcher->dispatch($request, $response);
        $this->assertSame('startup stop', (string)$result->getBody());
    }

    /**
     * Ensure that a controllers startup process can emit a response
     *
     * @return void
     */
    public function testShutdownProcessResponse()
    {
        $request = new ServerRequest([
            'url' => 'cakes/index',
            'params' => [
                'plugin' => null,
                'controller' => 'Cakes',
                'action' => 'index',
                'stop' => 'shutdown',
                'pass' => [],
            ],
        ]);
        $response = new Response();
        $result = $this->dispatcher->dispatch($request, $response);
        $this->assertSame('shutdown stop', (string)$result->getBody());
    }
}
