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

/**
 * Trait for implementing PSR-14 StoppableEventInterface.
 *
 * Use this trait in your event classes to make them stoppable:
 *
 * ```php
 * use Psr\EventDispatcher\StoppableEventInterface;
 *
 * class UserCreatedEvent implements StoppableEventInterface
 * {
 *     use StoppableEventTrait;
 *
 *     public function __construct(
 *         public readonly User $user,
 *     ) {}
 * }
 * ```
 */
trait StoppableEventTrait
{
    /**
     * Whether propagation was stopped.
     */
    private bool $propagationStopped = false;

    /**
     * Stop event propagation.
     *
     * After calling this method, isPropagationStopped() will return true
     * and the dispatcher will stop calling further listeners.
     *
     * @return void
     */
    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    /**
     * @inheritDoc
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }
}
