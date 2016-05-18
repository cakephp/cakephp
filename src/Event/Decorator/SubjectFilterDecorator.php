<?php
/**
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Event\Decorator;

use Cake\Event\Event;
use RuntimeException;

/**
 * Event Subject Filter Decorator
 */
class SubjectFilterDecorator extends BaseDecorator {

    /**
     * @inheritdoc
     */
    public function __invoke()
    {
        $args = func_get_args();
        if (!$this->canTrigger($args[0])) {
            return false;
        }
        return call_user_func_array('parent::__invoke', $args);
    }

    /**
     * Checks if the event is triggered for this listener.
     *
     * @param \Cake\Event\Event $event Event object.
     * @return bool
     */
    public function canTrigger(Event $event)
    {
        $class = get_class($event->subject());
        if (!isset($this->_options['allowedSubject'])) {
            throw new RuntimeException(self::class . ' Missing subject filter options!');
        }
        if (is_string($this->_options['allowedSubject'])) {
            $this->_options['allowedSubject'] = [$this->_options['allowedSubject']];
        }
        return in_array($class, $this->_options['allowedSubject']);
    }
}
