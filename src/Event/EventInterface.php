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
 * @since         3.3.2
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Event;

/**
 * Represents the transport interface for events across the system.
 */
interface EventInterface
{
    /**
     * Access the event data/payload.
     *
     * @param string|null $key The string identifier for the data payload, or if null all of the payload is returned.
     * @return array|null The data payload if $key is null, or the data value for the given $key. If the $key does not
     * exist a null value should be returned.
     */
    public function data($key = null);

    /**
     * Check if the event is stopped
     *
     * @return bool True if the event is stopped
     */
    public function isStopped();

    /**
     * Returns the name of this event. This is usually used as the event identifier
     *
     * @return string
     */
    public function name();

    /**
     * The result value of the event listeners
     *
     * @return mixed
     */
    public function result();

    /**
     * Modify the event data/payload.
     *
     * @param array|string $key The string identifier to be modified, or an array to replace the payload.
     * @param mixed $value The payload value to be modified if $key is a string, otherwise ignored.
     * @return $this
     */
    public function setData($key, $value = null);

    /**
     * Assigns a result value for the event listener. If a result has already be assigned it will be overwritten.
     *
     * @param mixed $value
     * @return $this
     */
    public function setResult($value = null);

    /**
     * Stops the event from being used anymore
     *
     * @return void
     */
    public function stopPropagation();

    /**
     * Returns the subject of this event
     *
     * @return object
     */
    public function subject();
}
