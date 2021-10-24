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
use LogicException;
use OutOfBoundsException;
use TestApp\Middleware\DumbMiddleware;
use TestApp\Middleware\SampleMiddleware;

/**
 * Test case for the MiddlewareQueue
 */
class MiddlewareQueueTest extends TestCase
{
    /**
     * @var string
     */
    protected $previousNamespace;

    /**
     * setUp
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->previousNamespace = static::setAppNamespace('TestApp');
    }

    /**
     * tearDown
     */
    public function tearDown(): void
    {
        parent::tearDown();
        static::setAppNamespace($this->previousNamespace);
    }

    public function testConstructorAddingMiddleware(): void
    {
        $cb = function (): void {
        };
        $queue = new MiddlewareQueue([$cb]);
        $this->assertCount(1, $queue);
        $this->assertSame($cb, $queue->current()->getCallable());
    }

    /**
     * Test get()
     */
    public function testGet(): void
    {
        $queue = new MiddlewareQueue();
        $cb = function (): void {
        };
        $queue->add($cb);
        $this->assertSame($cb, $queue->current()->getCallable());
    }

    /**
     * Test that current() throws exception for invalid current position.
     */
    public function testGetException(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('Invalid current position (0)');

        $queue = new MiddlewareQueue();
        $queue->current();
    }

    /**
     * Test the return value of add()
     */
    public function testAddReturn(): void
    {
        $queue = new MiddlewareQueue();
        $cb = function (): void {
        };
        $this->assertSame($queue, $queue->add($cb));
    }

    /**
     * Test the add orders correctly
     */
    public function testAddOrdering(): void
    {
        $one = function (): void {
        };
        $two = function (): void {
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
     */
    public function testPrependReturn(): void
    {
        $cb = function (): void {
        };
        $queue = new MiddlewareQueue();
        $this->assertSame($queue, $queue->prepend($cb));
    }

    /**
     * Test the prepend orders correctly.
     */
    public function testPrependOrdering(): void
    {
        $one = function (): void {
        };
        $two = function (): void {
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
     */
    public function testAddingPrependingUsingString(): void
    {
        $queue = new MiddlewareQueue();
        $queue->add('Sample');
        $queue->prepend('TestApp\Middleware\SampleMiddleware');

        $this->assertInstanceOf('TestApp\Middleware\SampleMiddleware', $queue->current());
        $this->assertInstanceOf('TestApp\Middleware\SampleMiddleware', $queue->current());
    }

    /**
     * Test updating queue using array
     */
    public function testAddingPrependingUsingArray(): void
    {
        $one = function (): void {
        };

        $queue = new MiddlewareQueue();
        $queue->add([$one]);
        $queue->prepend(['TestApp\Middleware\SampleMiddleware']);

        $this->assertInstanceOf('TestApp\Middleware\SampleMiddleware', $queue->current());
        $queue->next();
        $this->assertSame($one, $queue->current()->getCallable());
    }

    /**
     * Test insertAt ordering
     */
    public function testInsertAt(): void
    {
        $one = function (): void {
        };
        $two = function (): void {
        };
        $three = function (): void {
        };
        $four = new SampleMiddleware();

        $queue = new MiddlewareQueue();
        $queue->add($one)->add($two)->insertAt(0, $three)->insertAt(2, $four);
        $this->assertSame($three, $queue->current()->getCallable());
        $queue->next();
        $this->assertSame($one, $queue->current()->getCallable());
        $queue->next();
        $this->assertInstanceOf(SampleMiddleware::class, $queue->current());
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
     */
    public function testInsertAtOutOfBounds(): void
    {
        $one = function (): void {
        };
        $two = function (): void {
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
     */
    public function testInsertAtNegative(): void
    {
        $one = function (): void {
        };
        $two = function (): void {
        };
        $three = new SampleMiddleware();

        $queue = new MiddlewareQueue();
        $queue->add($one)->insertAt(-1, $two)->insertAt(-1, $three);

        $this->assertCount(3, $queue);
        $this->assertSame($two, $queue->current()->getCallable());
        $queue->next();
        $this->assertInstanceOf(SampleMiddleware::class, $queue->current());
        $queue->next();
        $this->assertSame($one, $queue->current()->getCallable());
    }

    /**
     * Test insertBefore
     */
    public function testInsertBefore(): void
    {
        $one = function (): void {
        };
        $two = new SampleMiddleware();
        $three = function (): void {
        };
        $four = new DumbMiddleware();

        $queue = new MiddlewareQueue();
        $queue->add($one)->add($two)->insertBefore(SampleMiddleware::class, $three)->insertBefore(SampleMiddleware::class, $four);

        $this->assertCount(4, $queue);
        $this->assertSame($one, $queue->current()->getCallable());
        $queue->next();
        $this->assertSame($three, $queue->current()->getCallable());
        $queue->next();
        $this->assertInstanceOf(DumbMiddleware::class, $queue->current());
        $queue->next();
        $this->assertInstanceOf(SampleMiddleware::class, $queue->current());

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
        $this->assertInstanceOf(SampleMiddleware::class, $queue->current());
    }

    /**
     * Test insertBefore an invalid classname
     */
    public function testInsertBeforeInvalid(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No middleware matching \'InvalidClassName\' could be found.');
        $one = function (): void {
        };
        $two = new SampleMiddleware();
        $three = function (): void {
        };
        $queue = new MiddlewareQueue();
        $queue->add($one)->add($two)->insertBefore('InvalidClassName', $three);
    }

    /**
     * Test insertAfter
     */
    public function testInsertAfter(): void
    {
        $one = new SampleMiddleware();
        $two = function (): void {
        };
        $three = function (): void {
        };
        $four = new DumbMiddleware();
        $queue = new MiddlewareQueue();
        $queue
            ->add($one)
            ->add($two)
            ->insertAfter(SampleMiddleware::class, $three)
            ->insertAfter(SampleMiddleware::class, $four);

        $this->assertCount(4, $queue);
        $this->assertInstanceOf(SampleMiddleware::class, $queue->current());
        $queue->next();
        $this->assertInstanceOf(DumbMiddleware::class, $queue->current());
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
        $this->assertInstanceOf(SampleMiddleware::class, $queue->current());
        $queue->next();
        $this->assertSame($three, $queue->current()->getCallable());
        $queue->next();
        $this->assertSame($two, $queue->current()->getCallable());
    }

    /**
     * Test insertAfter an invalid classname
     */
    public function testInsertAfterInvalid(): void
    {
        $one = new SampleMiddleware();
        $two = function (): void {
        };
        $three = function (): void {
        };
        $queue = new MiddlewareQueue();
        $queue->add($one)->add($two)->insertAfter('InvalidClass', $three);

        $this->assertCount(3, $queue);
        $this->assertInstanceOf(SampleMiddleware::class, $queue->current());
        $queue->next();
        $this->assertSame($two, $queue->current()->getCallable());
        $queue->next();
        $this->assertSame($three, $queue->current()->getCallable());
    }

    /**
     * @deprecated
     */
    public function testAddingDeprecatedDoublePassMiddleware(): void
    {
        $queue = new MiddlewareQueue();
        $cb = function ($request, $response, $next) {
            return $next($request, $response);
        };
        $queue->add($cb);
        $this->deprecated(function () use ($queue, $cb): void {
            $this->assertSame($cb, $queue->current()->getCallable());
        });
    }
}
