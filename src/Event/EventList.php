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
namespace Cake\Event;

use ArrayAccess;
use Countable;

/**
 * The Event List
 *
 * @template-implements \ArrayAccess<int, \Cake\Event\EventInterface>
 */
class EventList implements ArrayAccess, Countable
{
    /**
     * Events list
     *
     * @var array<\Cake\Event\EventInterface<object>>
     */
    protected array $_events = [];

    /**
     * Empties the list of dispatched events.
     */
    public function flush(): void
    {
        $this->_events = [];
    }

    /**
     * Adds an event to the list when event listing is enabled.
     *
     * @param \Cake\Event\EventInterface<object> $event An event to the list of dispatched events.
     */
    public function add(EventInterface $event): void
    {
        $this->_events[] = $event;
    }

    /**
     * Whether a offset exists
     *
     * @link https://secure.php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset An offset to check for.
     * @return bool True on success or false on failure.
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->_events[$offset]);
    }

    /**
     * Offset to retrieve
     *
     * @link https://secure.php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset The offset to retrieve.
     * @return \Cake\Event\EventInterface<object>|null
     */
    public function offsetGet(mixed $offset): ?EventInterface
    {
        if (!$this->offsetExists($offset)) {
            return null;
        }

        return $this->_events[$offset];
    }

    /**
     * Offset to set
     *
     * @link https://secure.php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value The value to set.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->_events[$offset] = $value;
    }

    /**
     * Offset to unset
     *
     * @link https://secure.php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset The offset to unset.
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->_events[$offset]);
    }

    /**
     * Count elements of an object
     *
     * @link https://secure.php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     */
    public function count(): int
    {
        return count($this->_events);
    }

    /**
     * Checks if an event is in the list.
     *
     * @param string $name Event name.
     */
    public function hasEvent(string $name): bool
    {
        foreach ($this->_events as $event) {
            if ($event->getName() === $name) {
                return true;
            }
        }

        return false;
    }
}
