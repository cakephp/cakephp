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
namespace Cake\Test\TestCase;

use Cake\Http\MiddlewareQueue;
use Cake\Http\Runner;
use Cake\TestSuite\TestCase;
use RuntimeException;

/**
 * Test case for runner.
 */
class RunnerTest extends TestCase
{
    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->stack = new MiddlewareQueue();

        $this->ok = function ($req, $res, $next) {
            return $next($req, $res);
        };
        $this->pass = function ($req, $res, $next) {
            return $next($req, $res);
        };
        $this->noNext = function ($req, $res, $next) {
        };
        $this->fail = function ($req, $res, $next) {
            throw new RuntimeException('A bad thing');
        };
    }

    /**
     * Test running a single middleware object.
     *
     * @return void
     */
    public function testRunSingle()
    {
        $this->stack->add($this->ok);
        $req = $this->getMockBuilder('Psr\Http\Message\ServerRequestInterface')->getMock();
        $res = $this->getMockBuilder('Psr\Http\Message\ResponseInterface')->getMock();

        $runner = new Runner();
        $result = $runner->run($this->stack, $req, $res);
        $this->assertSame($res, $result);
    }

    /**
     * Test replacing a response in a middleware.
     *
     * @return void
     */
    public function testRunResponseReplace()
    {
        $one = function ($req, $res, $next) {
            $res = $this->getMockBuilder('Psr\Http\Message\ResponseInterface')->getMock();

            return $next($req, $res);
        };
        $this->stack->add($one);
        $runner = new Runner();

        $req = $this->getMockBuilder('Psr\Http\Message\ServerRequestInterface')->getMock();
        $res = $this->getMockBuilder('Psr\Http\Message\ResponseInterface')->getMock();
        $result = $runner->run($this->stack, $req, $res);

        $this->assertNotSame($res, $result, 'Response was not replaced');
        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $result);
    }

    /**
     * Test that middleware is run in sequence
     *
     * @return void
     */
    public function testRunSequencing()
    {
        $log = [];
        $one = function ($req, $res, $next) use (&$log) {
            $log[] = 'one';

            return $next($req, $res);
        };
        $two = function ($req, $res, $next) use (&$log) {
            $log[] = 'two';

            return $next($req, $res);
        };
        $three = function ($req, $res, $next) use (&$log) {
            $log[] = 'three';

            return $next($req, $res);
        };
        $this->stack->add($one)->add($two)->add($three);
        $runner = new Runner();

        $req = $this->getMockBuilder('Psr\Http\Message\ServerRequestInterface')->getMock();
        $res = $this->getMockBuilder('Psr\Http\Message\ResponseInterface')->getMock();
        $result = $runner->run($this->stack, $req, $res);

        $this->assertSame($res, $result, 'Response is not correct');

        $expected = ['one', 'two', 'three'];
        $this->assertEquals($expected, $log);
    }

    /**
     * Test that exceptions bubble up.
     *
     */
    public function testRunExceptionInMiddleware()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A bad thing');
        $this->stack->add($this->ok)->add($this->fail);
        $req = $this->getMockBuilder('Psr\Http\Message\ServerRequestInterface')->getMock();
        $res = $this->getMockBuilder('Psr\Http\Message\ResponseInterface')->getMock();

        $runner = new Runner();
        $runner->run($this->stack, $req, $res);
    }

    /**
     * Test that 'bad' middleware returns null.
     *
     * @return void
     */
    public function testRunNextNotCalled()
    {
        $this->stack->add($this->noNext);
        $req = $this->getMockBuilder('Psr\Http\Message\ServerRequestInterface')->getMock();
        $res = $this->getMockBuilder('Psr\Http\Message\ResponseInterface')->getMock();

        $runner = new Runner();
        $result = $runner->run($this->stack, $req, $res);
        $this->assertNull($result);
    }
}
