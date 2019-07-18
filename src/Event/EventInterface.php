<?php
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
 * @since         3.6.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Event;

/**
 * Represents the transport class of events across the system. It receives a name, subject and an optional
 * payload. The name can be any string that uniquely identifies the event across the application, while the subject
 * represents the object that the event applies to.
 *
 * @property string $name (deprecated) Name of the event
 * @property object $subject (deprecated) The object this event applies to
 * @property mixed $result (deprecated) Property used to retain the result value of the event listeners
 * @property array $data (deprecated) Custom data for the method that receives the event
 */
interface EventInterface
{
    /**
     * Returns the name of this event. This is usually used as the event identifier.
     *
     * @return string
     */
    public function getName();

    /**
     * Returns the subject of this event.
     *
     * @return object
     */
    public function getSubject();

    /**
     * Stops the event from being used anymore.
     *
     * @return void
     */
    public function stopPropagation();

    /**
     * Checks if the event is stopped.
     *
     * @return bool True if the event is stopped
     */
    public function isStopped();

    /**
     * The result value of the event listeners.
     *
     * @return mixed
     */
    public function getResult();

    /**
     * Listeners can attach a result value to the event.
     *
     * @param mixed $value The value to set.
     * @return $this
     */
    public function setResult($value = null);

    /**
     * Accesses the event data/payload.
     *
     * @param string|null $key The data payload element to return, or null to return all data.
     * @return array|mixed|null The data payload if $key is null, or the data value for the given $key. If the $key does not
     * exist a null value is returned.
     */
    public function getData($key = null);

    /**
     * Assigns a value to the data/payload of this event.
     *
     * @param array|string $key An array will replace all payload data, and a key will set just that array item.
     * @param mixed $value The value to set.
     * @return $this
     */
    public function setData($key, $value = null);
}
