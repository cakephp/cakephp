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
use Countable;
use DateTime;
use InvalidArgumentException;
use IteratorAggregate;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Cookie Collection
 *
 * Provides an immutable collection of cookies objects. Adding or removing
 * to a collection returns a *new* collection that you must retain.
 */
class CookieCollection implements IteratorAggregate, Countable
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
     * Get the number of cookies in the collection.
     *
     * @return int
     */
    public function count()
    {
        return count($this->cookies);
    }

    /**
     * Add a cookie and get an updated collection.
     *
     * Cookie names do not have to be unique in a collection, but
     * having duplicate cookie names will change how get() behaves.
     *
     * @param \Cake\Http\Cookie\CookieInterface $cookie Cookie instance to add.
     * @return static
     */
    public function add(CookieInterface $cookie)
    {
        $new = clone $this;
        $new->cookies[] = $cookie;

        return $new;
    }

    /**
     * Get the first cookie by name.
     *
     * If the provided name matches a URL (matches `#^https?://#`) this method
     * will assume you want a list of cookies that match that URL. This is
     * backwards compatible behavior that will be removed in 4.0.0
     *
     * @param string $name The name of the cookie. If the name looks like a URL,
     *  backwards compatible behavior will be used.
     * @return \Cake\Http\Cookie\CookieInterface|null|array
     */
    public function get($name)
    {
        $key = mb_strtolower($name);
        foreach ($this->cookies as $cookie) {
            if (mb_strtolower($cookie->getName()) === $key) {
                return $cookie;
            }
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
        foreach ($this->cookies as $cookie) {
            if (mb_strtolower($cookie->getName()) === $key) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create a new collection with all cookies matching $name removed.
     *
     * If the cookie is not in the collection, this method will do nothing.
     *
     * @param string $name The name of the cookie to remove.
     * @return static
     */
    public function remove($name)
    {
        $new = clone $this;
        $key = mb_strtolower($name);
        foreach ($new->cookies as $i => $cookie) {
            if (mb_strtolower($cookie->getName()) === $key) {
                unset($new->cookies[$i]);
            }
        }

        return $new;
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

    /**
     * Create a new collection that includes cookies from the response.
     *
     * @param \Psr\Http\Message\ResponseInterface $response Response to extract cookies from.
     * @param \Psr\Http\Message\RequestInterface $request Request to get cookie context from.
     * @return static
     */
    public function addFromResponse(ResponseInterface $response, RequestInterface $request)
    {
        $uri = $request->getUri();
        $host = $uri->getHost();
        $path = $uri->getPath() ?: '/';

        $header = $response->getHeader('Set-Cookie');
        $cookies = $this->parseSetCookieHeader($header);
        $new = clone $this;
        foreach ($cookies as $name => $cookie) {
            // Apply path/domain from request if the cookie
            // didn't have one.
            if (!$cookie->getDomain()) {
                $cookie = $cookie->withDomain($host);
            }
            if (!$cookie->getPath()) {
                $cookie = $cookie->withPath($path);
            }

            $expires = $cookie->getExpiry();
            // Don't store expired cookies
            if ($expires && $expires <= time()) {
                continue;
            }
            $new->cookies[] = $cookie;
        }

        return $new;
    }

    /**
     * Parse Set-Cookie headers into array
     *
     * @param array $values List of Set-Cookie Header values.
     * @return \Cake\Http\Cookie\Cookie[] An array of cookie objects
     */
    protected function parseSetCookieHeader($values)
    {
        $cookies = [];
        foreach ($values as $value) {
            $value = rtrim($value, ';');
            $parts = preg_split('/\;[ \t]*/', $value);

            $name = false;
            $cookie = [
                'value' => '',
                'path' => '',
                'domain' => '',
                'secure' => false,
                'httponly' => false,
                'expires' => null
            ];
            foreach ($parts as $i => $part) {
                if (strpos($part, '=') !== false) {
                    list($key, $value) = explode('=', $part, 2);
                } else {
                    $key = $part;
                    $value = true;
                }
                if ($i === 0) {
                    $name = $key;
                    $cookie['value'] = urldecode($value);
                    continue;
                }
                $key = strtolower($key);
                if (!strlen($cookie[$key])) {
                    $cookie[$key] = $value;
                }
            }
            $expires = null;
            if ($cookie['expires']) {
                $expires = new DateTime($cookie['expires']);
            }

            $cookies[] = new Cookie(
                $name,
                $cookie['value'],
                $expires,
                $cookie['path'],
                $cookie['domain'],
                $cookie['secure'],
                $cookie['httponly']
            );
        }

        return $cookies;
    }
}
