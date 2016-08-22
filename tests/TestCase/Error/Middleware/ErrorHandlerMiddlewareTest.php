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
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Error\Middleware;

use Cake\Core\Configure;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\ServerRequestFactory;
use Cake\Network\Response as CakeResponse;
use Cake\TestSuite\TestCase;
use LogicException;
use Zend\Diactoros\Request;
use Zend\Diactoros\Response;

/**
 * Test for ErrorHandlerMiddleware
 */
class ErrorHandlerMiddlewareTest extends TestCase
{
    /**
     * Test returning a response works ok.
     *
     * @return void
     */
    public function testNoErrorResponse()
    {
        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();

        $middleware = new ErrorHandlerMiddleware();
        $next = function ($req, $res) {
            return $res;
        };
        $result = $middleware($request, $response, $next);
        $this->assertSame($result, $response);
    }

    /**
     * Test an invalid rendering class.
     *
     * @expectedException Exception
     * @expectedExceptionMessage The 'TotallyInvalid' renderer class could not be found
     */
    public function testInvalidRenderer()
    {
        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();

        $middleware = new ErrorHandlerMiddleware('TotallyInvalid');
        $next = function ($req, $res) {
            throw new \Exception('Something bad');
        };
        $middleware($request, $response, $next);
    }

    /**
     * Test using a factory method to make a renderer.
     *
     * @return void
     */
    public function testRendererFactory()
    {
        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();

        $factory = function ($exception) {
            $this->assertInstanceOf('LogicException', $exception);
            $cakeResponse = new CakeResponse;
            $mock = $this->getMockBuilder('StdClass')
                ->setMethods(['render'])
                ->getMock();
            $mock->expects($this->once())
                ->method('render')
                ->will($this->returnValue($cakeResponse));

            return $mock;
        };
        $middleware = new ErrorHandlerMiddleware($factory);
        $next = function ($req, $res) {
            throw new LogicException('Something bad');
        };
        $middleware($request, $response, $next);
    }

    /**
     * Test rendering an error page
     *
     * @return void
     */
    public function testHandleException()
    {
        Configure::write('App.namespace', 'TestApp');

        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();
        $middleware = new ErrorHandlerMiddleware();
        $next = function ($req, $res) {
            throw new \Cake\Network\Exception\NotFoundException('whoops');
        };
        $result = $middleware($request, $response, $next);
        $this->assertNotSame($result, $response);
        $this->assertEquals(404, $result->getStatusCode());
        $this->assertContains("was not found", '' . $result->getBody());
    }

    /**
     * Test handling an error and having rendering fail.
     *
     * @return void
     */
    public function testHandleExceptionRenderingFails()
    {
        Configure::write('App.namespace', 'TestApp');

        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();

        $factory = function ($exception) {
            $mock = $this->getMockBuilder('StdClass')
                ->setMethods(['render'])
                ->getMock();
            $mock->expects($this->once())
                ->method('render')
                ->will($this->throwException(new LogicException('Rendering failed')));

            return $mock;
        };
        $middleware = new ErrorHandlerMiddleware($factory);
        $next = function ($req, $res) {
            throw new \Cake\Network\Exception\ServiceUnavailableException('whoops');
        };
        $response = $middleware($request, $response, $next);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('An Internal Server Error Occurred', '' . $response->getBody());
    }
}
