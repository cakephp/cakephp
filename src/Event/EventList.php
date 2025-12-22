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
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;
use function Cake\Core\deprecationWarning;

/**
 * The Event List
 *
 * @template Tsubject of object
 * @implements \ArrayAccess<int, \Cake\Event\EventInterface<Tsubject>>
 * @implements \IteratorAggregate<\Cake\Event\EventInterface<Tsubject>>
 */
class EventList implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * Events list
     *
     * @var array<\Cake\Event\EventInterface<Tsubject>>
     */
    protected array $_events = [];

    /**
     * Empties the list of dispatched events.
     *
     * @return void
     */
    public function flush(): void
    {
        $this->_events = [];
    }

    /**
     * Adds an event to the list when event listing is enabled.
     *
     * @param \Cake\Event\EventInterface<Tsubject> $event An event to the list of dispatched events.
     * @return void
     */
    public function add(EventInterface $event): void
    {
        $this->_events[] = $event;
    }

    /**
     * Whether a offset exists
     *
     * @deprecated 5.3.0 Array access for `EventList` is deprecated, use `EventList::hasEvent()` instead.
     * @link https://secure.php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset An offset to check for.
     * @return bool True on success or false on failure.
     */
    public function offsetExists(mixed $offset): bool
    {
        deprecationWarning(
            '5.3.0',
            'Array access for `EventList` is deprecated, use `EventList::hasEvent()` instead.',
        );

        return isset($this->_events[$offset]);
    }

    /**
     * Offset to retrieve
     *
     * @deprecated 5.3.0 Array access for `EventList` is deprecated, you can iterate the instance instead.
     * @link https://secure.php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset The offset to retrieve.
     * @return \Cake\Event\EventInterface<Tsubject>|null
     */
    public function offsetGet(mixed $offset): ?EventInterface
    {
        deprecationWarning(
            '5.3.0',
            'Array access for `EventList` is deprecated, you can iterate the instance instead.',
        );

        return $this->_events[$offset] ?? null;
    }

    /**
     * Offset to set
     *
     * @deprecated 5.3.0 Array access for `EventList` is deprecated, use `EventList::add() instead.`.
     * @link https://secure.php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value The value to set.
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        deprecationWarning(
            '5.3.0',
            'Array access for `EventList` is deprecated, use `EventList::add() instead.',
        );

        $this->_events[$offset] = $value;
    }

    /**
     * Offset to unset
     *
     * @deprecated 5.3.0 Array access for `EventList` is deprecated.
     * Individual events cannot be unset anymore, use `EventList::flush()` to clear the list.
     * @link https://secure.php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset The offset to unset.
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        deprecationWarning(
            '5.3.0',
            'Array access for `EventList` is deprecated.'
            . ' Individual events cannot be unset anymore, use `EventList::flush()` to clear the list.',
        );
        unset($this->_events[$offset]);
    }

    /**
     * Retrieve an external iterator
     *
     * @return \Traversable<\Cake\Event\EventInterface<Tsubject>>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->_events);
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
     * @return bool
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
