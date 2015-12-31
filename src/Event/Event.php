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
 * @since         2.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Event;

/**
 * Represents the transport class of events across the system. It receives a name, subject and an optional
 * payload. The name can be any string that uniquely identifies the event across the application, while the subject
 * represents the object that the event applies to.
 *
 */
class Event
{

    /**
     * Name of the event
     *
     * @var string
     */
    protected $_name = null;

    /**
     * The object this event applies to (usually the same object that generates the event)
     *
     * @var object
     */
    protected $_subject;

    /**
     * Custom data for the method that receives the event
     *
     * @var mixed
     */
    public $data = null;

    /**
     * Property used to retain the result value of the event listeners
     *
     * @var mixed
     */
    public $result = null;

    /**
     * Flags an event as stopped or not, default is false
     *
     * @var bool
     */
    protected $_stopped = false;

    /**
     * Constructor
     *
     * ### Examples of usage:
     *
     * ```
     *  $event = new Event('Order.afterBuy', $this, ['buyer' => $userData]);
     *  $event = new Event('User.afterRegister', $UserModel);
     * ```
     *
     * @param string $name Name of the event
     * @param object|null $subject the object that this event applies to (usually the object that is generating the event)
     * @param array|null $data any value you wish to be transported with this event to it can be read by listeners
     */
    public function __construct($name, $subject = null, $data = null)
    {
        $this->_name = $name;
        $this->data = $data;
        $this->_subject = $subject;
    }

    /**
     * Dynamically returns the name and subject if accessed directly
     *
     * @param string $attribute Attribute name.
     * @return mixed
     */
    public function __get($attribute)
    {
        if ($attribute === 'name' || $attribute === 'subject') {
            return $this->{$attribute}();
        }
    }

    /**
     * Returns the name of this event. This is usually used as the event identifier
     *
     * @return string
     */
    public function name()
    {
        return $this->_name;
    }

    /**
     * Returns the subject of this event
     *
     * @return object
     */
    public function subject()
    {
        return $this->_subject;
    }

    /**
     * Stops the event from being used anymore
     *
     * @return void
     */
    public function stopPropagation()
    {
        $this->_stopped = true;
    }

    /**
     * Check if the event is stopped
     *
     * @return bool True if the event is stopped
     */
    public function isStopped()
    {
        return $this->_stopped;
    }

    /**
     * Access the event data/payload.
     *
     * @return array
     */
    public function data()
    {
        return (array)$this->data;
    }
}
