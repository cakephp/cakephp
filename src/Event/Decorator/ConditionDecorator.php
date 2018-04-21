<?php
/**
 * CakePHP : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Event\Decorator;

use Cake\Event\Event;
use RuntimeException;

/**
 * Event Condition Decorator
 *
 * Use this decorator to allow your event listener to only
 * be invoked if the `if` and/or `unless` conditions pass.
 */
class ConditionDecorator extends AbstractDecorator
{

    /**
     * {@inheritDoc}
     */
    public function __invoke()
    {
        $args = func_get_args();
        if (!$this->canTrigger($args[0])) {
            return;
        }

        return $this->_call($args);
    }

    /**
     * Checks if the event is triggered for this listener.
     *
     * @param \Cake\Event\Event $event Event object.
     * @return bool
     */
    public function canTrigger(Event $event)
    {
        $if = $this->_evaluateCondition('if', $event);
        $unless = $this->_evaluateCondition('unless', $event);

        return $if && !$unless;
    }

    /**
     * Evaluates the filter conditions
     *
     * @param string $condition Condition type
     * @param \Cake\Event\Event $event Event object
     * @return bool
     */
    protected function _evaluateCondition($condition, Event $event)
    {
        if (!isset($this->_options[$condition])) {
            return $condition !== 'unless';
        }
        if (!is_callable($this->_options[$condition])) {
            throw new RuntimeException(self::class . ' the `' . $condition . '` condition is not a callable!');
        }

        return $this->_options[$condition]($event);
    }
}
