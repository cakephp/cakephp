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

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * The Event List
 *
 * @template Tsubject of object
 * @implements \IteratorAggregate<\Cake\Event\EventInterface<Tsubject>>
 */
class EventList implements Countable, IteratorAggregate
{
    /**
     * Events list
     *
     * @var array<\Cake\Event\EventInterface<Tsubject>>
     */
    protected array $events = [];

    /**
     * Empties the list of dispatched events.
     *
     * @return void
     */
    public function flush(): void
    {
        $this->events = [];
    }

    /**
     * Adds an event to the list when event listing is enabled.
     *
     * @param \Cake\Event\EventInterface<Tsubject> $event An event to the list of dispatched events.
     * @return void
     */
    public function add(EventInterface $event): void
    {
        $this->events[] = $event;
    }

    /**
     * Retrieve an external iterator
     *
     * @return \Traversable<\Cake\Event\EventInterface<Tsubject>>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->events);
    }

    /**
     * Count elements of an object
     *
     * @link https://secure.php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     */
    public function count(): int
    {
        return count($this->events);
    }

    /**
     * Checks if an event is in the list.
     *
     * @param string $name Event name.
     * @return bool
     */
    public function hasEvent(string $name): bool
    {
        return array_any($this->events, fn(EventInterface $event) => $event->getName() === $name);
    }
}
