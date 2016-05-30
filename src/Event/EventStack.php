<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Event;

/**
 * The Event Stack
 */
class EventStack implements \ArrayAccess, \Countable
{

    /**
     * Events list
     *
     * @var array
     */
    protected $_events = [];

    /**
     * Empties the list of dispatched events.
     *
     * @return void
     */
    public function flush()
    {
        $this->_events = [];
    }

    /**
     * Adds an event to the list when stacking is enabled.
     *
     * @param \Cake\Event\Event $event An event to the list of dispatched events.
     * @return void
     */
    public function add(Event $event)
    {
        $this->_events[] = $event;
    }

    /**
     * Whether a offset exists
     *
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset An offset to check for.
     * @return boole True on success or false on failure.
     */
    public function offsetExists($offset)
    {
        return isset($this->_events[$offset]);
    }

    /**
     * Offset to retrieve
     *
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset The offset to retrieve.
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return $this->_events[$offset];
        }
        return null;
    }

    /**
     * Offset to set
     *
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value The value to set.
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->_events[$offset] = $value;
    }

    /**
     * Offset to unset
     *
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset The offset to unset.
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->_events[$offset]);
    }

    /**
     * Count elements of an object
     *
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     */
    public function count()
    {
        return count($this->_events);
    }

    /**
     * Checks if an event is in the stack.
     *
     * @param string $name Event name.
     * @return bool
     */
    public function hasEvent($name)
    {
        foreach ($this->_events as $event) {
            if ($event->name() === $name) {
                return true;
            }
        }
        return false;
    }
}
