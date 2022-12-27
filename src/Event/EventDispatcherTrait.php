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
 * @since         3.0.10
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Event;

/**
 * Implements Cake\Event\EventDispatcherInterface.
 *
 * @template TSubject of object
 */
trait EventDispatcherTrait
{
    /**
     * Instance of the Cake\Event\EventManager this object is using
     * to dispatch inner events.
     *
     * @var \Cake\Event\EventManagerInterface|null
     */
    protected ?EventManagerInterface $_eventManager = null;

    /**
     * Default class name for new event objects.
     *
     * @var string
     */
    protected string $_eventClass = Event::class;

    /**
     * Returns the Cake\Event\EventManager manager instance for this object.
     *
     * You can use this instance to register any new listeners or callbacks to the
     * object events, or create your own events and trigger them at will.
     *
     * @return \Cake\Event\EventManagerInterface
     */
    public function getEventManager(): EventManagerInterface
    {
        return $this->_eventManager ??= new EventManager();
    }

    /**
     * Returns the Cake\Event\EventManagerInterface instance for this object.
     *
     * You can use this instance to register any new listeners or callbacks to the
     * object events, or create your own events and trigger them at will.
     *
     * @param \Cake\Event\EventManagerInterface $eventManager the eventManager to set
     * @return $this
     */
    public function setEventManager(EventManagerInterface $eventManager)
    {
        $this->_eventManager = $eventManager;

        return $this;
    }

    /**
     * Wrapper for creating and dispatching events.
     *
     * Returns a dispatched event.
     *
     * @param string $name Name of the event.
     * @param array $data Any value you wish to be transported with this event to
     * it can be read by listeners.
     * @param TSubject|null $subject The object that this event applies to
     * ($this by default).
     * @return \Cake\Event\EventInterface<TSubject>
     */
    public function dispatchEvent(string $name, array $data = [], ?object $subject = null): EventInterface
    {
        $subject ??= $this;

        /** @var \Cake\Event\EventInterface<TSubject> $event Coerce for psalm/phpstan */
        $event = new $this->_eventClass($name, $subject, $data);
        $this->getEventManager()->dispatch($event);

        return $event;
    }
}
