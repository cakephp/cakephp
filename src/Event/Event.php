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

/**
 * Class Event
 *
 * @template TSubject of object
 * @implements \Cake\Event\EventInterface<TSubject>
 */
class Event implements EventInterface
{
    /**
     * Name of the event
     *
     * @var string
     */
    protected string $name;

    /**
     * The object this event applies to (usually the same object that generates the event)
     *
     * @var object|null
     * @phpstan-var TSubject|null
     */
    protected ?object $subject = null;

    /**
     * Custom data for the method that receives the event
     *
     * @var array
     */
    protected array $data;

    /**
     * Property used to retain the result value of the event listeners
     *
     * Use setResult() and getResult() to set and get the result.
     *
     * @var mixed
     */
    protected mixed $result = null;

    /**
     * Flags an event as stopped or not, default is false
     *
     * @var bool
     */
    protected bool $stopped = false;

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
     * @param string $name Name of the event
     * @param object|null $subject the object that this event applies to
     *   (usually the object that is generating the event).
     * @param array $data any value you wish to be transported
     *   with this event to it can be read by listeners.
     * @phpstan-param TSubject|null $subject
     */
    public function __construct(string $name, ?object $subject = null, array $data = [])
    {
        $this->name = $name;
        $this->subject = $subject;
        $this->data = $data;
    }

    /**
     * Returns the name of this event. This is usually used as the event identifier
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the subject of this event
     *
     * @return object|null
     * @phpstan-return TSubject|null
     */
    public function getSubject(): ?object
    {
        return $this->subject;
    }

    /**
     * Stops the event from being used anymore
     *
     * @return void
     */
    public function stopPropagation(): void
    {
        $this->stopped = true;
    }

    /**
     * Check if the event is stopped
     *
     * @return bool True if the event is stopped
     */
    public function isStopped(): bool
    {
        return $this->stopped;
    }

    /**
     * The result value of the event listeners
     *
     * @return mixed
     */
    public function getResult(): mixed
    {
        return $this->result;
    }

    /**
     * Listeners can attach a result value to the event.
     *
     * Setting the result to `false` will also stop event propagation.
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
            return $this->data[$key] ?? null;
        }

        return $this->data;
    }

    /**
     * @inheritDoc
     */
    public function setData(array|string $key, $value = null): static
    {
        if (is_array($key)) {
            $this->data = $key;
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }
}
