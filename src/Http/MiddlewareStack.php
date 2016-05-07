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
namespace Cake\Http;

use Countable;

/**
 * Provides methods for creating and manipulating a 'stack' of
 * middleware callables. This stack is used to process a request and response
 * via \Cake\Http\Runner.
 */
class MiddlewareStack implements Countable
{
    /**
     * The stack of middleware callables.
     *
     * @var array
     */
    protected $stack = [];

    /**
     * Get the middleware object at the provided index.
     *
     * @param int $index The index to fetch.
     * @return callable|null Either the callable middleware or null
     *   if the index is undefined.
     */
    public function get($index)
    {
        if (isset($this->stack[$index])) {
            return $this->stack[$index];
        }
        return null;
    }

    /**
     * Append a middleware callable to the end of the stack.
     *
     * @param callable $callable The middleware callable to append.
     * @return $this
     */
    public function push(callable $callable)
    {
        $this->stack[] = $callable;
        return $this;
    }

    /**
     * Prepend a middleware callable to the start of the stack.
     *
     * @param callable $callable The middleware callable to prepend.
     * @return $this
     */
    public function prepend(callable $callable)
    {
        array_unshift($this->stack, $callable);
        return $this;
    }

    /**
     * Insert a middleware callable at a specific index.
     *
     * If the index already exists, the new callable will be inserted,
     * and the existing element will be shifted one index greater.
     *
     * @param int $index The index to insert at.
     * @param callable $callable The callable to insert.
     * @return $this
     */
    public function insertAt($index, callable $callable)
    {
        array_splice($this->stack, $index, 0, $callable);
        return $this;
    }

    /**
     * Insert a middleware object before the first matching class.
     *
     * Finds the index of the first middleware that matches the provided class,
     * and inserts the supplied callable before it. If the class is not found,
     * this method will behave like push().
     *
     * @param string $class The classname to insert the middleware before.
     * @param callable $callable The middleware to insert
     * @return $this
     */
    public function insertBefore($class, $callable)
    {
        $found = false;
        foreach ($this->stack as $i => $object) {
            if (is_a($object, $class)) {
                $found = true;
                break;
            }
        }
        if ($found) {
            return $this->insertAt($i, $callable);
        }
        return $this->push($callable);
    }

    /**
     * Insert a middleware object after the first matching class.
     *
     * Finds the index of the first middleware that matches the provided class,
     * and inserts the supplied callable after it. If the class is not found,
     * this method will behave like push().
     *
     * @param string $class The classname to insert the middleware before.
     * @param callable $callable The middleware to insert
     * @return $this
     */
    public function insertAfter($class, $callable)
    {
        $found = false;
        foreach ($this->stack as $i => $object) {
            if (is_a($object, $class)) {
                $found = true;
                break;
            }
        }
        if ($found) {
            return $this->insertAt($i + 1, $callable);
        }
        return $this->push($callable);
    }

    /**
     * Get the number of connected middleware layers.
     *
     * Implement the Countable interface.
     *
     * @return int
     */
    public function count()
    {
        return count($this->stack);
    }
}
