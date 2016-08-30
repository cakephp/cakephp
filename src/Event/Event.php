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
 * @property string $name Name of the event
 * @property object $subject The object this event applies to
 * @property mixed $result Property used to retain the result value of the event listeners
 * @property array $data Custom data for the method that receives the event
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
     * @var array
     */
    protected $_data;

    /**
     * Property used to retain the result value of the event listeners
     *
     * @var mixed
     */
    protected $_result = null;

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
        if ($data !== null && !is_array($data)) {
            trigger_error('Data parameter will be changing to array hint typing', E_USER_DEPRECATED);
        }

        $this->_name = $name;
        $this->_data = (array)$data;
        $this->_subject = $subject;
    }

    /**
     * Provides read-only access for the name and subject properties.
     *
     * @param string $attribute Attribute name.
     * @return mixed
     */
    public function __get($attribute)
    {
        if ($attribute === 'name' || $attribute === 'subject') {
            return $this->{$attribute}();
        }
        if ($attribute === 'data')
        {
            trigger_error('Public read access to data is deprecated, use data()', E_USER_DEPRECATED);
            return $this->_data;
        }
        if ($attribute === 'result')
        {
            trigger_error('Public read access to result is deprecated, use result()', E_USER_DEPRECATED);
            return $this->_result;
        }
    }

    /**
     * Provides backward compatibility for write access to data and result properties.
     *
     * @param string $attribute Attribute name.
     * @param mixed $value
     */
    public function __set($attribute, $value)
    {
        if($attribute === 'data')
        {
            trigger_error('Public write access to data is deprecated, use setData()', E_USER_DEPRECATED);
            $this->_data = (array)$value;
        }
        if ($attribute === 'result')
        {
            trigger_error('Public write access to result is deprecated, use setResult()', E_USER_DEPRECATED);
            $this->_result = $value;
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
     * The result value of the event listeners
     *
     * @return mixed
     */
    public function result()
    {
        return $this->_result;
    }

    /**
     * @param null $value
     * @return $this
     */
    public function setResult($value = null)
    {
        $this->_result = $value;
        return $this;
    }

    /**
     * Access the event data/payload.
     *
     * @param string|null $key
     * @return array|null The data payload if $key is null, or the data value for the given $key. If the $key does not
     * exist a null value is returned.
     */
    public function data($key = null)
    {
        if ($key !== null) {
            return isset($this->_data[$key]) ? $this->_data[$key] : null;
        }
        return (array)$this->_data;
    }

    /**
     * @param array|string $key
     * @param mixed $value
     * @return $this
     */
    public function setData($key, $value = null)
    {
        if(is_array($key)) {
            $this->_data = $key;
        } else {
            $this->_data[$key] = $value;
        }
        return $this;
    }
}
