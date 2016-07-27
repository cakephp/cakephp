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
namespace Cake\Test\TestCase\Http;

use Cake\Core\Configure;
use Cake\Http\MiddlewareQueue;
use Cake\TestSuite\TestCase;
use TestApp\Middleware\SampleMiddleware;

/**
 * Test case for the MiddlewareQueue
 */
class MiddlewareQueueTest extends TestCase
{
    /**
     * setUp
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->appNamespace = Configure::read('App.namespace');
        Configure::write('App.namespace', 'TestApp');
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown()
    {
        Configure::write('App.namespace', $this->appNamespace);
    }

    /**
     * Test get()
     *
     * @return void
     */
    public function testGet()
    {
        $queue = new MiddlewareQueue();
        $cb = function () {
        };
        $queue->add($cb);
        $this->assertSame($cb, $queue->get(0));
        $this->assertNull($queue->get(1));
    }

    /**
     * Test the return value of add()
     *
     * @return void
     */
    public function testAddReturn()
    {
        $queue = new MiddlewareQueue();
        $cb = function () {
        };
        $this->assertSame($queue, $queue->add($cb));
    }

    /**
     * Test the add orders correctly
     *
     * @return void
     */
    public function testAddOrdering()
    {
        $one = function () {
        };
        $two = function () {
        };

        $queue = new MiddlewareQueue();
        $this->assertCount(0, $queue);

        $queue->add($one);
        $this->assertCount(1, $queue);

        $queue->add($two);
        $this->assertCount(2, $queue);

        $this->assertSame($one, $queue->get(0));
        $this->assertSame($two, $queue->get(1));
    }

    /**
     * Test the prepend can be chained
     *
     * @return void
     */
    public function testPrependReturn()
    {
        $cb = function () {
        };
        $queue = new MiddlewareQueue();
        $this->assertSame($queue, $queue->prepend($cb));
    }

    /**
     * Test the prepend orders correctly.
     *
     * @return void
     */
    public function testPrependOrdering()
    {
        $one = function () {
        };
        $two = function () {
        };

        $queue = new MiddlewareQueue();
        $this->assertCount(0, $queue);

        $queue->add($one);
        $this->assertCount(1, $queue);

        $queue->prepend($two);
        $this->assertCount(2, $queue);

        $this->assertSame($two, $queue->get(0));
        $this->assertSame($one, $queue->get(1));
    }

    /**
     * Test updating queue using class name
     *
     * @return void
     */
    public function testAddingPrependingUsingString()
    {
        $queue = new MiddlewareQueue();
        $queue->add('Sample');
        $queue->prepend('TestApp\Middleware\SampleMiddleware');

        $this->assertInstanceOf('TestApp\Middleware\SampleMiddleware', $queue->get(0));
        $this->assertInstanceOf('TestApp\Middleware\SampleMiddleware', $queue->get(1));
    }

    /**
     * Test updating queue using array
     *
     * @return void
     */
    public function testAddingPrependingUsingArray()
    {
        $one = function () {
        };

        $queue = new MiddlewareQueue();
        $queue->add([$one]);
        $queue->prepend(['TestApp\Middleware\SampleMiddleware']);

        $this->assertInstanceOf('TestApp\Middleware\SampleMiddleware', $queue->get(0));
        $this->assertSame($one, $queue->get(1));
    }

    /**
     * Test insertAt ordering
     *
     * @return void
     */
    public function testInsertAt()
    {
        $one = function () {
        };
        $two = function () {
        };
        $three = function () {
        };

        $queue = new MiddlewareQueue();
        $queue->add($one)->add($two)->insertAt(0, $three);
        $this->assertSame($three, $queue->get(0));
        $this->assertSame($one, $queue->get(1));
        $this->assertSame($two, $queue->get(2));

        $queue = new MiddlewareQueue();
        $queue->add($one)->add($two)->insertAt(1, $three);
        $this->assertSame($one, $queue->get(0));
        $this->assertSame($three, $queue->get(1));
        $this->assertSame($two, $queue->get(2));
    }

    /**
     * Test insertAt out of the existing range
     *
     * @return void
     */
    public function testInsertAtOutOfBounds()
    {
        $one = function () {
        };
        $two = function () {
        };

        $queue = new MiddlewareQueue();
        $queue->add($one)->insertAt(99, $two);

        $this->assertCount(2, $queue);
        $this->assertSame($one, $queue->get(0));
        $this->assertSame($two, $queue->get(1));
    }

    /**
     * Test insertAt with a negative index
     *
     * @return void
     */
    public function testInsertAtNegative()
    {
        $one = function () {
        };
        $two = function () {
        };

        $queue = new MiddlewareQueue();
        $queue->add($one)->insertAt(-1, $two);

        $this->assertCount(2, $queue);
        $this->assertSame($two, $queue->get(0));
        $this->assertSame($one, $queue->get(1));
    }

    /**
     * Test insertBefore
     *
     * @return void
     */
    public function testInsertBefore()
    {
        $one = function () {
        };
        $two = new SampleMiddleware();
        $three = function () {
        };
        $queue = new MiddlewareQueue();
        $queue->add($one)->add($two)->insertBefore(SampleMiddleware::class, $three);

        $this->assertCount(3, $queue);
        $this->assertSame($one, $queue->get(0));
        $this->assertSame($three, $queue->get(1));
        $this->assertSame($two, $queue->get(2));

        $two = SampleMiddleware::class;
        $queue = new MiddlewareQueue();
        $queue
            ->add($one)
            ->add($two)
            ->insertBefore(SampleMiddleware::class, $three);

        $this->assertCount(3, $queue);
        $this->assertSame($one, $queue->get(0));
        $this->assertSame($three, $queue->get(1));
        $this->assertInstanceOf(SampleMiddleware::class, $queue->get(2));
    }

    /**
     * Test insertBefore an invalid classname
     *
     * @expectedException LogicException
     * @expectedExceptionMessage No middleware matching 'InvalidClassName' could be found.
     * @return void
     */
    public function testInsertBeforeInvalid()
    {
        $one = function () {
        };
        $two = new SampleMiddleware();
        $three = function () {
        };
        $queue = new MiddlewareQueue();
        $queue->add($one)->add($two)->insertBefore('InvalidClassName', $three);
    }

    /**
     * Test insertAfter
     *
     * @return void
     */
    public function testInsertAfter()
    {
        $one = new SampleMiddleware();
        $two = function () {
        };
        $three = function () {
        };
        $queue = new MiddlewareQueue();
        $queue->add($one)->add($two)->insertAfter(SampleMiddleware::class, $three);

        $this->assertCount(3, $queue);
        $this->assertSame($one, $queue->get(0));
        $this->assertSame($three, $queue->get(1));
        $this->assertSame($two, $queue->get(2));

        $one = 'Sample';
        $queue = new MiddlewareQueue();
        $queue
            ->add($one)
            ->add($two)
            ->insertAfter('Sample', $three);

        $this->assertCount(3, $queue);
        $this->assertInstanceOf(SampleMiddleware::class, $queue->get(0));
        $this->assertSame($three, $queue->get(1));
        $this->assertSame($two, $queue->get(2));
    }

    /**
     * Test insertAfter an invalid classname
     *
     * @return void
     */
    public function testInsertAfterInvalid()
    {
        $one = new SampleMiddleware();
        $two = function () {
        };
        $three = function () {
        };
        $queue = new MiddlewareQueue();
        $queue->add($one)->add($two)->insertAfter('InvalidClass', $three);

        $this->assertCount(3, $queue);
        $this->assertSame($one, $queue->get(0));
        $this->assertSame($two, $queue->get(1));
        $this->assertSame($three, $queue->get(2));
    }
}
