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

use ArrayIterator;
use InvalidArgumentException;
use IteratorAggregate;

/**
 * Cookie Collection
 */
class CookieCollection implements IteratorAggregate
{

    /**
     * Cookie objects
     *
     * @var Cookie[]
     */
    protected $cookies = [];

    /**
     * Constructor
     *
     * @param array $cookies Array of cookie objects
     */
    public function __construct(array $cookies = [])
    {
        $this->checkCookies($cookies);
        foreach ($cookies as $cookie) {
            $name = $cookie->getName();
            $key = mb_strtolower($name);
            $this->cookies[$key] = $cookie;
        }
    }

    /**
     * Get a cookie by name
     *
     * If the provided name matches a URL (matches `#^https?://#`) this method
     * will assume you want a list of cookies that match that URL. This is
     * backwards compatible behavior that will be removed in 4.0.0
     *
     * @param string $name The name of the cookie. If the name looks like a URL,
     *  backwards compatible behavior will be used.
     * @return \Cake\Http\Cookie\Cookie|null|array
     */
    public function get($name)
    {
        $key = mb_strtolower($name);
        if (isset($this->cookies[$key])) {
            return $this->cookies[$key];
        }
        return null;
    }

    /**
     * Check if a cookie with the given name exists
     *
     * @param string $name The cookie name to check.
     * @return bool True if the cookie exists, otherwise false.
     */
    public function has($name)
    {
        $key = mb_strtolower($name);
        return isset($this->cookies[$key]);
    }

    /**
     * Checks if only valid cookie objects are in the array
     *
     * @param array $cookies Array of cookie objects
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function checkCookies(array $cookies)
    {
        foreach ($cookies as $index => $cookie) {
            if (!$cookie instanceof CookieInterface) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Expected `%s[]` as $cookies but instead got `%s` at index %d',
                        static::class,
                        is_object($cookie) ? get_class($cookie) : gettype($cookie),
                        $index
                    )
                );
            }
        }
    }

    /**
     * Gets the iterator
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->cookies);
    }
}
