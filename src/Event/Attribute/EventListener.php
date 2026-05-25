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
namespace Cake\Event\Attribute;

use Attribute;

/**
 * Declares an event listener for a class or method using PHP attributes.
 *
 * When placed on a method, registers that method as the listener callable.
 * When placed on a class, resolves the callable using the following priority:
 *  1. The explicit `$method` argument when provided.
 *  2. `__invoke()` when present on the class.
 *  3. A method name inferred from the event name (e.g. `Order.afterPlace` → `onOrderAfterPlace`).
 *
 * The attribute is repeatable, allowing a single method or class to listen to multiple events.
 *
 * ### Examples
 *
 * Method-level (most common):
 * ```php
 * #[EventListener('Order.afterPlace')]
 * #[EventListener('Order.afterCancel', priority: 20)]
 * public function updateMetrics(EventInterface $event): void {}
 * ```
 *
 * Class-level with explicit method:
 * ```php
 * #[EventListener('Order.afterPlace', method: 'handleOrder')]
 * class OrderListener {}
 * ```
 *
 * Class-level with invokable class:
 * ```php
 * #[EventListener('Order.afterPlace')]
 * class OrderListener {
 *     public function __invoke(EventInterface $event): void {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
readonly class EventListener
{
    /**
     * Initializes an event listener attribute definition.
     *
     * @param string $event Event name to listen to (e.g. `'Order.afterPlace'`).
     * @param int|null $priority Listener priority. When null, the current value of
     *   `EventManager::$defaultPriority` is used at connection time.
     * @param string|null $method Explicit method name to use as the listener callable.
     *   Only relevant for class-level attributes; ignored for method-level attributes
     *   that do not override the target method.
     */
    public function __construct(
        public string $event,
        public ?int $priority = null,
        public ?string $method = null,
    ) {
    }
}
