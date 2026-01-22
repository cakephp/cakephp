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
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Event\Psr14;

use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * PSR-14 ListenerProvider implementation.
 *
 * Stores and retrieves event listeners based on the event type.
 * Listeners are matched using instanceof checks, so a listener
 * for a parent class will receive events of child classes too.
 *
 * ### Usage
 *
 * ```php
 * $provider = new ListenerProvider();
 *
 * // Add a listener for a specific event class
 * $provider->addListener(UserCreatedEvent::class, function (UserCreatedEvent $event) {
 *     sendWelcomeEmail($event->user);
 * });
 *
 * // Listeners can be any callable
 * $provider->addListener(OrderPlacedEvent::class, [$orderService, 'processOrder']);
 * $provider->addListener(PaymentReceivedEvent::class, new PaymentHandler());
 * ```
 */
class ListenerProvider implements ListenerProviderInterface
{
    /**
     * Registered listeners indexed by event class name.
     *
     * @var array<class-string, array<array{callable, int}>>
     */
    private array $listeners = [];

    /**
     * @inheritDoc
     */
    public function getListenersForEvent(object $event): iterable
    {
        $eventClass = $event::class;
        $matchedListeners = [];

        foreach ($this->listeners as $listenedClass => $listeners) {
            if ($event instanceof $listenedClass) {
                foreach ($listeners as [$listener, $priority]) {
                    $matchedListeners[] = [$listener, $priority];
                }
            }
        }

        // Sort by priority (higher = earlier)
        usort($matchedListeners, fn (array $a, array $b): int => $b[1] <=> $a[1]);

        foreach ($matchedListeners as [$listener, $priority]) {
            yield $listener;
        }
    }

    /**
     * Add a listener for an event type.
     *
     * @param class-string $eventClass The event class name to listen for.
     * @param callable $listener The listener callable.
     * @param int $priority The listener priority (higher = earlier). Default is 0.
     * @return $this
     */
    public function addListener(string $eventClass, callable $listener, int $priority = 0): static
    {
        if (!isset($this->listeners[$eventClass])) {
            $this->listeners[$eventClass] = [];
        }

        $this->listeners[$eventClass][] = [$listener, $priority];

        return $this;
    }

    /**
     * Remove a listener for an event type.
     *
     * @param class-string $eventClass The event class name.
     * @param callable $listener The listener callable to remove.
     * @return $this
     */
    public function removeListener(string $eventClass, callable $listener): static
    {
        if (!isset($this->listeners[$eventClass])) {
            return $this;
        }

        $this->listeners[$eventClass] = array_values(array_filter(
            $this->listeners[$eventClass],
            fn (array $entry): bool => $entry[0] !== $listener
        ));

        if (empty($this->listeners[$eventClass])) {
            unset($this->listeners[$eventClass]);
        }

        return $this;
    }

    /**
     * Clear all listeners, optionally for a specific event type.
     *
     * @param class-string|null $eventClass The event class name, or null to clear all.
     * @return $this
     */
    public function clearListeners(?string $eventClass = null): static
    {
        if ($eventClass === null) {
            $this->listeners = [];
        } else {
            unset($this->listeners[$eventClass]);
        }

        return $this;
    }

    /**
     * Check if there are listeners for an event type.
     *
     * @param class-string $eventClass The event class name.
     * @return bool
     */
    public function hasListeners(string $eventClass): bool
    {
        return !empty($this->listeners[$eventClass]);
    }
}
