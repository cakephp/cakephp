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

use Cake\Http\MiddlewareQueue;
use Cake\Http\Response;
use Cake\Http\Runner;
use Cake\TestSuite\TestCase;
use Psr\Http\Message\ResponseInterface;
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
    public function setUp(): void
    {
        parent::setUp();

        $this->queue = new MiddlewareQueue();

        $this->ok = function ($req, $res, $next) {
            return $next($req, $res);
        };
        $this->pass = function ($req, $res, $next) {
            return $next($req, $res);
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
        $this->queue->add($this->ok);
        $req = $this->getMockBuilder('Psr\Http\Message\ServerRequestInterface')->getMock();

        $runner = new Runner();
        $result = $runner->run($this->queue, $req);
        $this->assertInstanceof(ResponseInterface::class, $result);
    }

    /**
     * Test that middleware is run in sequence
     *
     * @return void
     */
    public function testRunSequencing()
    {
        $log = [];
        $one = function ($req, $handler) use (&$log) {
            $log[] = 'one';

            return $handler->handle($req);
        };
        $two = function ($req, $res, $next) use (&$log) {
            $log[] = 'two';

            return $next($req, $res);
        };
        $three = function ($req, $res, $next) use (&$log) {
            $log[] = 'three';

            return $next($req, $res);
        };
        $this->queue->add($one)->add($two)->add($three);
        $runner = new Runner();

        $req = $this->getMockBuilder('Psr\Http\Message\ServerRequestInterface')->getMock();
        $result = $runner->run($this->queue, $req);
        $this->assertInstanceof(Response::class, $result);

        $expected = ['one', 'two', 'three'];
        $this->assertEquals($expected, $log);
    }

    /**
     * Test that exceptions bubble up.
     */
    public function testRunExceptionInMiddleware()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A bad thing');
        $this->queue->add($this->ok)->add($this->fail);
        $req = $this->getMockBuilder('Psr\Http\Message\ServerRequestInterface')->getMock();

        $runner = new Runner();
        $runner->run($this->queue, $req);
    }
}
