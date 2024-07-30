<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         3.2.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite\Constraint;

use Cake\Event\EventList;
use Cake\Event\EventManager;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Constraint\Constraint;

/**
 * EventFired constraint
 *
 * @internal
 */
class EventFired extends Constraint
{
    /**
     * Constructor
     *
     * @param \Cake\Event\EventManager $_eventManager Event manager to check
     */
    public function __construct(protected EventManager $_eventManager)
    {
        if (!$this->_eventManager->getEventList() instanceof EventList) {
            throw new AssertionFailedError(
                'The event manager you are asserting against is not configured to track events.'
            );
        }
    }

    /**
     * Checks if event is in fired array
     *
     * @param mixed $other Constraint check
     */
    protected function matches(mixed $other): bool
    {
        $list = $this->_eventManager->getEventList();

        return $list instanceof EventList && $list->hasEvent($other);
    }

    /**
     * Assertion message string
     */
    public function toString(): string
    {
        return 'was fired';
    }
}
