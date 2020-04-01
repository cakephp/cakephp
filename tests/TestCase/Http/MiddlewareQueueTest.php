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
use Cake\TestSuite\TestCase;
use TestApp\Middleware\DumbMiddleware;
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
    public function setUp(): void
    {
        parent::setUp();

        $this->previousNamespace = static::setAppNamespace('TestApp');
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        static::setAppNamespace($this->previousNamespace);
    }

    public function testConstructorAddingMiddleware()
    {
        $cb = function () {
        };
        $queue = new MiddlewareQueue([$cb]);
        $this->assertCount(1, $queue);
        $this->assertSame($cb, $queue->current()->getCallable());
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
        $this->assertSame($cb, $queue->current()->getCallable());
    }

    /**
     * Test that current() throws exception for invalid current position.
     *
     * @return void
     */
    public function testGetException()
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Invalid current position (0)');

        $queue = new MiddlewareQueue();
        $queue->current();
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

        $this->assertSame($one, $queue->current()->getCallable());
        $queue->next();
        $this->assertSame($two, $queue->current()->getCallable());
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

        $this->assertSame($two, $queue->current()->getCallable());
        $queue->next();
        $this->assertSame($one, $queue->current()->getCallable());
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

        $this->assertInstanceOf('TestApp\Middleware\SampleMiddleware', $queue->current()->getCallable());
        $this->assertInstanceOf('TestApp\Middleware\SampleMiddleware', $queue->current()->getCallable());
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

        $this->assertInstanceOf('TestApp\Middleware\SampleMiddleware', $queue->current()->getCallable());
        $queue->next();
        $this->assertSame($one, $queue->current()->getCallable());
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
        $four = new SampleMiddleware();

        $queue = new MiddlewareQueue();
        $queue->add($one)->add($two)->insertAt(0, $three)->insertAt(2, $four);
        $this->assertSame($three, $queue->current()->getCallable());
        $queue->next();
        $this->assertSame($one, $queue->current()->getCallable());
        $queue->next();
        $this->assertSame($four, $queue->current()->getCallable());
        $queue->next();
        $this->assertSame($two, $queue->current()->getCallable());

        $queue = new MiddlewareQueue();
        $queue->add($one)->add($two)->insertAt(1, $three);
        $this->assertSame($one, $queue->current()->getCallable());
        $queue->next();
        $this->assertSame($three, $queue->current()->getCallable());
        $queue->next();
        $this->assertSame($two, $queue->current()->getCallable());
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
        $this->assertSame($one, $queue->current()->getCallable());
        $queue->next();
        $this->assertSame($two, $queue->current()->getCallable());
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
        $three = new SampleMiddleware();

        $queue = new MiddlewareQueue();
        $queue->add($one)->insertAt(-1, $two)->insertAt(-1, $three);

        $this->assertCount(3, $queue);
        $this->assertSame($two, $queue->current()->getCallable());
        $queue->next();
        $this->assertSame($three, $queue->current()->getCallable());
        $queue->next();
        $this->assertSame($one, $queue->current()->getCallable());
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
        $four = new DumbMiddleware();

        $queue = new MiddlewareQueue();
        $queue->add($one)->add($two)->insertBefore(SampleMiddleware::class, $three)->insertBefore(SampleMiddleware::class, $four);

        $this->assertCount(4, $queue);
        $this->assertSame($one, $queue->current()->getCallable());
        $queue->next();
        $this->assertSame($three, $queue->current()->getCallable());
        $queue->next();
        $this->assertSame($four, $queue->current()->getCallable());
        $queue->next();
        $this->assertSame($two, $queue->current()->getCallable());

        $two = SampleMiddleware::class;
        $queue = new MiddlewareQueue();
        $queue
            ->add($one)
            ->add($two)
            ->insertBefore(SampleMiddleware::class, $three);

        $this->assertCount(3, $queue);
        $this->assertSame($one, $queue->current()->getCallable());
        $queue->next();
        $this->assertSame($three, $queue->current()->getCallable());
        $queue->next();
        $this->assertInstanceOf(SampleMiddleware::class, $queue->current()->getCallable());
    }

    /**
     * Test insertBefore an invalid classname
     *
     * @return void
     */
    public function testInsertBeforeInvalid()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No middleware matching \'InvalidClassName\' could be found.');
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
        $four = new DumbMiddleware();
        $queue = new MiddlewareQueue();
        $queue
            ->add($one)
            ->add($two)
            ->insertAfter(SampleMiddleware::class, $three)
            ->insertAfter(SampleMiddleware::class, $four);

        $this->assertCount(4, $queue);
        $this->assertSame($one, $queue->current()->getCallable());
        $queue->next();
        $this->assertSame($four, $queue->current()->getCallable());
        $queue->next();
        $this->assertSame($three, $queue->current()->getCallable());
        $queue->next();
        $this->assertSame($two, $queue->current()->getCallable());

        $one = 'Sample';
        $queue = new MiddlewareQueue();
        $queue
            ->add($one)
            ->add($two)
            ->insertAfter('Sample', $three);

        $this->assertCount(3, $queue);
        $this->assertInstanceOf(SampleMiddleware::class, $queue->current()->getCallable());
        $queue->next();
        $this->assertSame($three, $queue->current()->getCallable());
        $queue->next();
        $this->assertSame($two, $queue->current()->getCallable());
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
        $this->assertSame($one, $queue->current()->getCallable());
        $queue->next();
        $this->assertSame($two, $queue->current()->getCallable());
        $queue->next();
        $this->assertSame($three, $queue->current()->getCallable());
    }
}
