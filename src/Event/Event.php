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

use Cake\Core\Exception\CakeException;

/**
 * Class Event
 *
 * @template TSubject of object
 * @implements \Cake\Event\EventInterface<TSubject>
 */
class Event implements EventInterface
{
    /**
     * Property used to retain the result value of the event listeners
     *
     * Use setResult() and getResult() to set and get the result.
     */
    protected mixed $result = null;

    /**
     * Flags an event as stopped or not, default is false
     */
    protected bool $_stopped = false;

    /**
     * Constructor
     *
     * ### Examples of usage:
     *
     * ```
     *  $event = new Event('Order.afterBuy', $this, ['buyer' => $userData]);
     *  $event = new Event('User.afterRegister', $userModel);
     * ```
     *
     * @param string $_name Name of the event
     * @param object|null $_subject the object that this event applies to
     *   (usually the object that is generating the event).
     * @param array $_data any value you wish to be transported
     *   with this event to it can be read by listeners.
     * @psalm-param TSubject|null $_subject
     */
    public function __construct(
        protected string $_name,
        /**
         * @psalm-var TSubject|null
         */
        protected ?object $_subject = null,
        protected array $_data = []
    ) {
    }

    /**
     * Returns the name of this event. This is usually used as the event identifier
     */
    public function getName(): string
    {
        return $this->_name;
    }

    /**
     * Returns the subject of this event
     *
     * If the event has no subject an exception will be raised.
     *
     * @throws \Cake\Core\Exception\CakeException
     * @psalm-return TSubject
     */
    public function getSubject(): object
    {
        if ($this->_subject === null) {
            throw new CakeException('No subject set for this event');
        }

        return $this->_subject;
    }

    /**
     * Stops the event from being used anymore
     */
    public function stopPropagation(): void
    {
        $this->_stopped = true;
    }

    /**
     * Check if the event is stopped
     *
     * @return bool True if the event is stopped
     */
    public function isStopped(): bool
    {
        return $this->_stopped;
    }

    /**
     * The result value of the event listeners
     */
    public function getResult(): mixed
    {
        return $this->result;
    }

    /**
     * Listeners can attach a result value to the event.
     *
     * @param mixed $value The value to set.
     * @return $this
     */
    public function setResult(mixed $value = null): static
    {
        $this->result = $value;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getData(?string $key = null): mixed
    {
        if ($key !== null) {
            return $this->_data[$key] ?? null;
        }

        return $this->_data;
    }

    /**
     * @inheritDoc
     */
    public function setData(array|string $key, $value = null): static
    {
        if (is_array($key)) {
            $this->_data = $key;
        } else {
            $this->_data[$key] = $value;
        }

        return $this;
    }
}
