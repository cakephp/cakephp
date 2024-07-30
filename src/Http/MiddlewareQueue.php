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
namespace Cake\Http;

use Cake\Core\App;
use Cake\Core\ContainerInterface;
use Cake\Http\Middleware\ClosureDecoratorMiddleware;
use Closure;
use Countable;
use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;
use Psr\Http\Server\MiddlewareInterface;
use SeekableIterator;

/**
 * Provides methods for creating and manipulating a "queue" of middlewares.
 * This queue is used to process a request and generate response via \Cake\Http\Runner.
 *
 * @template-implements \SeekableIterator<int, \Psr\Http\Server\MiddlewareInterface>
 */
class MiddlewareQueue implements Countable, SeekableIterator
{
    /**
     * Internal position for iterator.
     */
    protected int $position = 0;

    /**
     * Constructor
     *
     * @param array $queue The list of middleware to append.
     * @param \Cake\Core\ContainerInterface $container Container instance.
     */
    public function __construct(
        protected array $queue = [],
        protected ?ContainerInterface $container = null
    ) {
    }

    /**
     * Resolve middleware name to a PSR 15 compliant middleware instance.
     *
     * @param \Psr\Http\Server\MiddlewareInterface|\Closure|string $middleware The middleware to resolve.
     * @throws \InvalidArgumentException If Middleware not found.
     */
    protected function resolve(MiddlewareInterface|Closure|string $middleware): MiddlewareInterface
    {
        if (is_string($middleware)) {
            if ($this->container instanceof ContainerInterface && $this->container->has($middleware)) {
                $middleware = $this->container->get($middleware);
            } else {
                /** @var class-string<\Psr\Http\Server\MiddlewareInterface>|null $className */
                $className = App::className($middleware, 'Middleware', 'Middleware');
                if ($className === null) {
                    throw new InvalidArgumentException(sprintf(
                        'Middleware `%s` was not found.',
                        $middleware
                    ));
                }

                $middleware = new $className();
            }
        }

        if ($middleware instanceof MiddlewareInterface) {
            return $middleware;
        }

        return new ClosureDecoratorMiddleware($middleware);
    }

    /**
     * Append a middleware to the end of the queue.
     *
     * @param \Psr\Http\Server\MiddlewareInterface|\Closure|array|string $middleware The middleware(s) to append.
     * @return $this
     */
    public function add(MiddlewareInterface|Closure|array|string $middleware): static
    {
        if (is_array($middleware)) {
            $this->queue = array_merge($this->queue, $middleware);

            return $this;
        }

        $this->queue[] = $middleware;

        return $this;
    }

    /**
     * Alias for MiddlewareQueue::add().
     *
     * @param \Psr\Http\Server\MiddlewareInterface|\Closure|array|string $middleware The middleware(s) to append.
     * @return $this
     * @see MiddlewareQueue::add()
     */
    public function push(MiddlewareInterface|Closure|array|string $middleware): static
    {
        return $this->add($middleware);
    }

    /**
     * Prepend a middleware to the start of the queue.
     *
     * @param \Psr\Http\Server\MiddlewareInterface|\Closure|array|string $middleware The middleware(s) to prepend.
     * @return $this
     */
    public function prepend(MiddlewareInterface|Closure|array|string $middleware): static
    {
        if (is_array($middleware)) {
            $this->queue = array_merge($middleware, $this->queue);

            return $this;
        }

        array_unshift($this->queue, $middleware);

        return $this;
    }

    /**
     * Insert a middleware at a specific index.
     *
     * If the index already exists, the new middleware will be inserted,
     * and the existing element will be shifted one index greater.
     *
     * @param int $index The index to insert at.
     * @param \Psr\Http\Server\MiddlewareInterface|\Closure|string $middleware The middleware to insert.
     * @return $this
     */
    public function insertAt(int $index, MiddlewareInterface|Closure|string $middleware): static
    {
        array_splice($this->queue, $index, 0, [$middleware]);

        return $this;
    }

    /**
     * Insert a middleware before the first matching class.
     *
     * Finds the index of the first middleware that matches the provided class,
     * and inserts the supplied middleware before it.
     *
     * @param string $class The classname to insert the middleware before.
     * @param \Psr\Http\Server\MiddlewareInterface|\Closure|string $middleware The middleware to insert.
     * @return $this
     * @throws \LogicException If middleware to insert before is not found.
     */
    public function insertBefore(string $class, MiddlewareInterface|Closure|string $middleware): static
    {
        $found = false;
        $i = 0;
        foreach ($this->queue as $i => $object) {
            /** @psalm-suppress ArgumentTypeCoercion */
            if (
                (
                    is_string($object)
                    && $object === $class
                )
                || is_a($object, $class)
            ) {
                $found = true;
                break;
            }
        }

        if ($found) {
            return $this->insertAt($i, $middleware);
        }

        throw new LogicException(sprintf('No middleware matching `%s` could be found.', $class));
    }

    /**
     * Insert a middleware object after the first matching class.
     *
     * Finds the index of the first middleware that matches the provided class,
     * and inserts the supplied middleware after it. If the class is not found,
     * this method will behave like add().
     *
     * @param string $class The classname to insert the middleware before.
     * @param \Psr\Http\Server\MiddlewareInterface|\Closure|string $middleware The middleware to insert.
     * @return $this
     */
    public function insertAfter(string $class, MiddlewareInterface|Closure|string $middleware): static
    {
        $found = false;
        $i = 0;
        foreach ($this->queue as $i => $object) {
            /** @psalm-suppress ArgumentTypeCoercion */
            if (
                (
                    is_string($object)
                    && $object === $class
                )
                || is_a($object, $class)
            ) {
                $found = true;
                break;
            }
        }

        if ($found) {
            return $this->insertAt($i + 1, $middleware);
        }

        return $this->add($middleware);
    }

    /**
     * Get the number of connected middleware layers.
     *
     * Implement the Countable interface.
     */
    public function count(): int
    {
        return count($this->queue);
    }

    /**
     * Seeks to a given position in the queue.
     *
     * @param int $position The position to seek to.
     * @see \SeekableIterator::seek()
     */
    public function seek(int $position): void
    {
        if (!isset($this->queue[$position])) {
            throw new OutOfBoundsException(sprintf('Invalid seek position (%s).', $position));
        }

        $this->position = $position;
    }

    /**
     * Rewinds back to the first element of the queue.
     *
     * @see \Iterator::rewind()
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     *  Returns the current middleware.
     *
     * @see \Iterator::current()
     */
    public function current(): MiddlewareInterface
    {
        if (!isset($this->queue[$this->position])) {
            throw new OutOfBoundsException(sprintf('Invalid current position (%s).', $this->position));
        }

        if ($this->queue[$this->position] instanceof MiddlewareInterface) {
            return $this->queue[$this->position];
        }

        return $this->queue[$this->position] = $this->resolve($this->queue[$this->position]);
    }

    /**
     * Return the key of the middleware.
     *
     * @see \Iterator::key()
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * Moves the current position to the next middleware.
     *
     * @see \Iterator::next()
     */
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * Checks if current position is valid.
     *
     * @see \Iterator::valid()
     */
    public function valid(): bool
    {
        return isset($this->queue[$this->position]);
    }
}
