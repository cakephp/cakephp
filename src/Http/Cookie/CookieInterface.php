<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Cookie;

/**
 * Cookie Interface
 */
interface CookieInterface
{
    /**
     * Sets the cookie name
     *
     * @param string $name Name of the cookie
     * @return static
     */
    public function withName($name);

    /**
     * Gets the cookie name
     *
     * @return string
     */
    public function getName();

    /**
     * Gets the cookie value
     *
     * @return string|array
     */
    public function getValue();

    /**
     * Create a cookie with an updated value.
     *
     * @param string|array $value Value of the cookie to set
     * @return static
     */
    public function withValue($value);

    /**
     * Returns the cookie as header value
     *
     * @return string
     */
    public function toHeaderValue();
}
