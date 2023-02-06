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
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use TestApp\Application;
use Throwable;

/**
 * Test case for runner.
 */
class RunnerTest extends TestCase
{
    /**
     * @var \Cake\Http\MiddlewareQueue
     */
    protected $queue;

    /**
     * @var \Closure
     */
    protected $ok;

    /**
     * @var \Closure
     */
    protected $pass;

    /**
     * @var \Closure
     */
    protected $fail;

    /**
     * setup
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->queue = new MiddlewareQueue();

        $this->ok = function ($request, $handler) {
            return $handler->handle($request);
        };
        $this->pass = function ($request, $handler) {
            return $handler->handle($request);
        };
        $this->fail = function ($request, $handler): void {
            throw new RuntimeException('A bad thing');
        };
    }

    /**
     * Test running a single middleware object.
     */
    public function testRunSingle(): void
    {
        $this->queue->add($this->ok);
        $req = $this->getMockBuilder('Psr\Http\Message\ServerRequestInterface')->getMock();

        $runner = new Runner();
        $result = $runner->run($this->queue, $req);
        $this->assertInstanceof(ResponseInterface::class, $result);
    }

    /**
     * Test that middleware is run in sequence
     */
    public function testRunSequencing(): void
    {
        $log = [];
        $one = function ($request, $handler) use (&$log) {
            $log[] = 'one';

            return $handler->handle($request);
        };
        $two = function ($request, $handler) use (&$log) {
            $log[] = 'two';

            return $handler->handle($request);
        };
        $three = function ($request, $handler) use (&$log) {
            $log[] = 'three';

            return $handler->handle($request);
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
    public function testRunExceptionInMiddleware(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A bad thing');
        $this->queue->add($this->ok)->add($this->fail);
        $req = $this->getMockBuilder('Psr\Http\Message\ServerRequestInterface')->getMock();

        $runner = new Runner();
        $runner->run($this->queue, $req);
    }

    public function testRunSetRouterContext(): void
    {
        $runner = new Runner();
        $request = new ServerRequest();
        $app = new Application(CONFIG);

        try {
            $runner->run($this->queue, $request, $app);
        } catch (Throwable $e) {
        }

        $this->assertSame($request, Router::getRequest());
    }
}
