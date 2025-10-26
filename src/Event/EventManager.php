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
 * @since         2.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Event;

use InvalidArgumentException;

/**
 * The event manager is responsible for keeping track of event listeners, passing the correct
 * data to them, and firing them in the correct order, when associated events are triggered. You
 * can create multiple instances of this object to manage local events or keep a single instance
 * and pass it around to manage all events in your app.
 */
class EventManager implements EventManagerInterface
{
    /**
     * The default priority queue value for new, attached listeners
     *
     * @var int
     */
    public static int $defaultPriority = 10;

    /**
     * The globally available instance, used for dispatching events attached from any scope
     *
     * @var \Cake\Event\EventManager|null
     */
    protected static ?EventManager $generalManager = null;

    /**
     * List of listener callbacks associated to
     *
     * @var array
     */
    protected array $listeners = [];

    /**
     * Internal flag to distinguish a common manager from the singleton
     *
     * @var bool
     */
    protected bool $isGlobal = false;

    /**
     * The event list object.
     *
     * @var \Cake\Event\EventList|null
     */
    protected ?EventList $eventList = null;

    /**
     * Enables automatic adding of events to the event list object if it is present.
     *
     * @var bool
     */
    protected bool $trackEvents = false;

    /**
     * Returns the globally available instance of a Cake\Event\EventManager
     * this is used for dispatching events attached from outside the scope
     * other managers were created. Usually for creating hook systems or inter-class
     * communication
     *
     * If called with the first parameter, it will be set as the globally available instance
     *
     * @param \Cake\Event\EventManager|null $manager Event manager instance.
     * @return \Cake\Event\EventManager The global event manager
     */
    public static function instance(?EventManager $manager = null): EventManager
    {
        if ($manager === null && static::$generalManager) {
            return static::$generalManager;
        }

        if ($manager instanceof EventManager) {
            static::$generalManager = $manager;
        }
        static::$generalManager ??= new static();
        static::$generalManager->isGlobal = true;

        return static::$generalManager;
    }

    /**
     * @inheritDoc
     */
    public function on(
        EventListenerInterface|string $eventKey,
        callable|array $options = [],
        ?callable $callable = null,
    ): static {
        if ($eventKey instanceof EventListenerInterface) {
            $this->attachSubscriber($eventKey);

            return $this;
        }

        if ($callable === null && !is_callable($options)) {
            throw new InvalidArgumentException(
                'Second argument of `EventManager::on()` must be a callable if `$callable` is null.',
            );
        }

        if ($callable === null) {
            /** @var callable $options */
            $this->listeners[$eventKey][static::$defaultPriority][] = [
                'callable' => $options(...),
            ];

            return $this;
        }

        /** @var array $options */
        $priority = $options['priority'] ?? static::$defaultPriority;
        $this->listeners[$eventKey][$priority][] = [
            'callable' => $callable(...),
        ];

        return $this;
    }

    /**
     * Auxiliary function to attach all implemented callbacks of a Cake\Event\EventListenerInterface class instance
     * as individual methods on this manager
     *
     * @param \Cake\Event\EventListenerInterface $subscriber Event listener.
     * @return void
     */
    protected function attachSubscriber(EventListenerInterface $subscriber): void
    {
        foreach ($subscriber->implementedEvents() as $eventKey => $handlers) {
            foreach ($this->normalizeHandlers($subscriber, $handlers) as $handler) {
                $this->on($eventKey, $handler['settings'], $handler['callable']);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function off(
        EventListenerInterface|callable|string $eventKey,
        EventListenerInterface|callable|null $callable = null,
    ): static {
        if ($eventKey instanceof EventListenerInterface) {
            $this->detachSubscriber($eventKey);

            return $this;
        }

        if (!is_string($eventKey)) {
            foreach (array_keys($this->listeners) as $name) {
                $this->off($name, $eventKey);
            }

            return $this;
        }

        if ($callable instanceof EventListenerInterface) {
            $this->detachSubscriber($callable, $eventKey);

            return $this;
        }

        if ($callable === null) {
            unset($this->listeners[$eventKey]);

            return $this;
        }

        if (empty($this->listeners[$eventKey])) {
            return $this;
        }

        $callable = $callable(...);
        foreach ($this->listeners[$eventKey] as $priority => $callables) {
            foreach ($callables as $k => $callback) {
                if ($callback['callable'] == $callable) {
                    unset($this->listeners[$eventKey][$priority][$k]);
                    break;
                }
            }
        }

        return $this;
    }

    /**
     * Auxiliary function to help detach all listeners provided by an object implementing EventListenerInterface
     *
     * @param \Cake\Event\EventListenerInterface $subscriber the subscriber to be detached
     * @param string|null $eventKey optional event key name to unsubscribe the listener from
     * @return void
     */
    protected function detachSubscriber(EventListenerInterface $subscriber, ?string $eventKey = null): void
    {
        $events = $subscriber->implementedEvents();
        if ($eventKey && empty($events[$eventKey])) {
            return;
        }
        if ($eventKey) {
            $events = [$eventKey => $events[$eventKey]];
        }
        foreach ($events as $key => $handlers) {
            foreach ($this->normalizeHandlers($subscriber, $handlers) as $handler) {
                $this->off($key, $handler['callable']);
            }
        }
    }

    /**
     * Builds an array of normalized handlers.
     *
     * A normalized handler is an array with these keys:
     *
     *  - `callable` - The event handler closure
     *  - `settings` - The event handler settings
     *
     * @param \Cake\Event\EventListenerInterface $subscriber Event subscriber
     * @param callable|array|string $handlers Event handlers
     * @return array
     */
    protected function normalizeHandlers(
        EventListenerInterface $subscriber,
        callable|array|string $handlers,
    ): array {
        // Check if an array of handlers not single handler config array
        if (is_array($handlers) && !isset($handlers['callable'])) {
            foreach ($handlers as &$handler) {
                $handler = $this->normalizeHandler($subscriber, $handler);
            }

            return $handlers;
        }

        return [$this->normalizeHandler($subscriber, $handlers)];
    }

    /**
     * Builds a single normalized handler.
     *
     * A normalized handler is an array with these keys:
     *
     *  - `callable` - The event handler closure
     *  - `settings` - The event handler settings
     *
     * @param \Cake\Event\EventListenerInterface $subscriber Event subscriber
     * @param callable|array|string $handler Event handler
     * @return array
     */
    protected function normalizeHandler(
        EventListenerInterface $subscriber,
        callable|array|string $handler,
    ): array {
        $callable = $handler;
        $settings = [];

        if (is_array($handler)) {
            $callable = $handler['callable'];
            $settings = $handler;
            unset($settings['callable']);
        }

        if (is_string($callable)) {
            $callable = $subscriber->$callable(...);
        }

        return ['callable' => $callable, 'settings' => $settings];
    }

    /**
     * @inheritDoc
     */
    public function dispatch(EventInterface|string $event): EventInterface
    {
        if (is_string($event)) {
            $event = new Event($event);
        }

        $listeners = $this->listeners($event->getName());

        if ($this->trackEvents) {
            $this->addEventToList($event);
        }

        if (!$this->isGlobal && static::instance()->isTrackingEvents()) {
            static::instance()->addEventToList($event);
        }

        if (!$listeners) {
            return $event;
        }

        foreach ($listeners as $listener) {
            if ($event->isStopped()) {
                break;
            }

            $this->callListener($listener['callable'], $event);
        }

        return $event;
    }

    /**
     * Calls a listener.
     *
     * @template TSubject of object
     * @param callable $listener The listener to trigger.
     * @param \Cake\Event\EventInterface<TSubject> $event Event instance.
     * @return void
     */
    protected function callListener(callable $listener, EventInterface $event): void
    {
        $listener($event, ...array_values($event->getData()));

        if ($event->getResult() === false) {
            $event->stopPropagation();
        }
    }

    /**
     * @inheritDoc
     */
    public function listeners(string $eventKey): array
    {
        $localListeners = [];
        if (!$this->isGlobal) {
            $localListeners = $this->prioritisedListeners($eventKey);
        }
        $globalListeners = static::instance()->prioritisedListeners($eventKey);

        $priorities = array_merge(array_keys($globalListeners), array_keys($localListeners));
        $priorities = array_unique($priorities);
        asort($priorities);

        $result = [];
        foreach ($priorities as $priority) {
            if (isset($globalListeners[$priority])) {
                $result = array_merge($result, $globalListeners[$priority]);
            }
            if (isset($localListeners[$priority])) {
                $result = array_merge($result, $localListeners[$priority]);
            }
        }

        return $result;
    }

    /**
     * Returns the listeners for the specified event key indexed by priority
     *
     * @param string $eventKey Event key.
     * @return array
     */
    public function prioritisedListeners(string $eventKey): array
    {
        if (empty($this->listeners[$eventKey])) {
            return [];
        }

        return $this->listeners[$eventKey];
    }

    /**
     * Returns the listeners matching a specified pattern
     *
     * @param string $eventKeyPattern Pattern to match.
     * @return array
     */
    public function matchingListeners(string $eventKeyPattern): array
    {
        $matchPattern = '/' . preg_quote($eventKeyPattern, '/') . '/';

        return array_intersect_key(
            $this->listeners,
            array_flip(
                preg_grep($matchPattern, array_keys($this->listeners), 0) ?: [],
            ),
        );
    }

    /**
     * Returns the event list.
     *
     * @return \Cake\Event\EventList|null
     */
    public function getEventList(): ?EventList
    {
        return $this->eventList;
    }

    /**
     * Adds an event to the list if the event list object is present.
     *
     * @template TSubject of object
     * @param \Cake\Event\EventInterface<TSubject> $event An event to add to the list.
     * @return $this
     */
    public function addEventToList(EventInterface $event): static
    {
        $this->eventList?->add($event);

        return $this;
    }

    /**
     * Enables / disables event tracking at runtime.
     *
     * @param bool $enabled True or false to enable / disable it.
     * @return $this
     */
    public function trackEvents(bool $enabled): static
    {
        $this->trackEvents = $enabled;

        return $this;
    }

    /**
     * Returns whether this manager is set up to track events
     *
     * @return bool
     */
    public function isTrackingEvents(): bool
    {
        return $this->trackEvents && $this->eventList;
    }

    /**
     * Enables the listing of dispatched events.
     *
     * @param \Cake\Event\EventList $eventList The event list object to use.
     * @return $this
     */
    public function setEventList(EventList $eventList): static
    {
        $this->eventList = $eventList;
        $this->trackEvents = true;

        return $this;
    }

    /**
     * Disables the listing of dispatched events.
     *
     * @return $this
     */
    public function unsetEventList(): static
    {
        $this->eventList = null;
        $this->trackEvents = false;

        return $this;
    }

    /**
     * Debug friendly object properties.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        $properties = get_object_vars($this);
        $properties['generalManager'] = '(object) EventManager';
        $properties['listeners'] = [];
        foreach ($this->listeners as $key => $priorities) {
            $listenerCount = 0;
            foreach ($priorities as $listeners) {
                $listenerCount += count($listeners);
            }
            $properties['listeners'][$key] = $listenerCount . ' listener(s)';
        }
        if ($this->eventList) {
            $count = count($this->eventList);
            for ($i = 0; $i < $count; $i++) {
                assert(!empty($this->eventList[$i]), 'Given event item not present');

                $event = $this->eventList[$i];
                $subject = $event->getSubject();
                if ($subject) {
                    $properties['dispatchedEvents'][] = $event->getName() . ' with subject ' . $subject::class;
                } else {
                    $properties['dispatchedEvents'][] = $event->getName() . ' with no subject';
                }
            }
        } else {
            $properties['dispatchedEvents'] = null;
        }
        unset($properties['eventList']);

        return $properties;
    }
}
