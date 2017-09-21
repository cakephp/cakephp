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
use Cake\Http\Client\Response as ClientResponse;
use Countable;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use IteratorAggregate;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
     * @var \Cake\Http\Cookie\CookieInterface[]
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
            $this->cookies[$cookie->getId()] = $cookie;
        }
    }

    /**
     * Create a Cookie Collection from an array of Set-Cookie Headers
     *
     * @param array $header The array of set-cookie header values.
     * @return static
     */
    public static function createFromHeader(array $header)
    {
        $cookies = static::parseSetCookieHeader($header);

        return new static($cookies);
    }

    /**
     * Create a new collection from the cookies in a ServerRequest
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request to extract cookie data from
     * @return static
     */
    public static function createFromServerRequest(ServerRequestInterface $request)
    {
        $data = $request->getCookieParams();
        $cookies = [];
        foreach ($data as $name => $value) {
            $cookies[] = new Cookie($name, $value);
        }

        return new static($cookies);
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
     * Cookies are stored by id. This means that there can be duplicate
     * cookies if a cookie collection is used for cookies across multiple
     * domains. This can impact how get(), has() and remove() behave.
     *
     * @param \Cake\Http\Cookie\CookieInterface $cookie Cookie instance to add.
     * @return static
     */
    public function add(CookieInterface $cookie)
    {
        $new = clone $this;
        $new->cookies[$cookie->getId()] = $cookie;

        return $new;
    }

    /**
     * Get the first cookie by name.
     *
     * @param string $name The name of the cookie.
     * @return \Cake\Http\Cookie\CookieInterface|null
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
     * Add cookies that match the path/domain/expiration to the request.
     *
     * This allows CookieCollections to be used as a 'cookie jar' in an HTTP client
     * situation. Cookies that match the request's domain + path that are not expired
     * when this method is called will be applied to the request.
     *
     * @param \Psr\Http\Message\RequestInterface $request The request to update.
     * @param array $extraCookies Associative array of additional cookies to add into the request. This
     *   is useful when you have cookie data from outside the collection you want to send.
     * @return \Psr\Http\Message\RequestInterface An updated request.
     */
    public function addToRequest(RequestInterface $request, array $extraCookies = [])
    {
        $uri = $request->getUri();
        $cookies = $this->findMatchingCookies(
            $uri->getScheme(),
            $uri->getHost(),
            $uri->getPath() ?: '/'
        );
        $cookies = array_merge($cookies, $extraCookies);
        $cookiePairs = [];
        foreach ($cookies as $key => $value) {
            $cookiePairs[] = sprintf("%s=%s", rawurlencode($key), rawurlencode($value));
        }
        if (empty($cookiePairs)) {
            return $request;
        }

        return $request->withHeader('Cookie', implode('; ', $cookiePairs));
    }

    /**
     * Find cookies matching the scheme, host, and path
     *
     * @param string $scheme The http scheme to match
     * @param string $host The host to match.
     * @param string $path The path to match
     * @return array An array of cookie name/value pairs
     */
    protected function findMatchingCookies($scheme, $host, $path)
    {
        $out = [];
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        foreach ($this->cookies as $cookie) {
            if ($scheme === 'http' && $cookie->isSecure()) {
                continue;
            }
            if (strpos($path, $cookie->getPath()) !== 0) {
                continue;
            }
            $domain = $cookie->getDomain();
            $leadingDot = substr($domain, 0, 1) === '.';
            if ($leadingDot) {
                $domain = ltrim($domain, '.');
            }

            if ($cookie->isExpired($now)) {
                continue;
            }

            $pattern = '/' . preg_quote($domain, '/') . '$/';
            if (!preg_match($pattern, $host)) {
                continue;
            }

            $out[$cookie->getName()] = $cookie->getValue();
        }

        return $out;
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

        $cookies = static::parseSetCookieHeader($response->getHeader('Set-Cookie'));
        $cookies = $this->setRequestDefaults($cookies, $host, $path);
        $new = clone $this;
        foreach ($cookies as $cookie) {
            $new->cookies[$cookie->getId()] = $cookie;
        }
        $new->removeExpiredCookies($host, $path);

        return $new;
    }

    /**
     * Apply path and host to the set of cookies if they are not set.
     *
     * @param array $cookies An array of cookies to update.
     * @param string $host The host to set.
     * @param string $path The path to set.
     * @return array An array of updated cookies.
     */
    protected function setRequestDefaults(array $cookies, $host, $path)
    {
        $out = [];
        foreach ($cookies as $name => $cookie) {
            if (!$cookie->getDomain()) {
                $cookie = $cookie->withDomain($host);
            }
            if (!$cookie->getPath()) {
                $cookie = $cookie->withPath($path);
            }
            $out[] = $cookie;
        }

        return $out;
    }

    /**
     * Parse Set-Cookie headers into array
     *
     * @param array $values List of Set-Cookie Header values.
     * @return \Cake\Http\Cookie\Cookie[] An array of cookie objects
     */
    protected static function parseSetCookieHeader($values)
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
                'expires' => null,
                'max-age' => null
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
                if (array_key_exists($key, $cookie) && !strlen($cookie[$key])) {
                    $cookie[$key] = $value;
                }
            }
            $expires = null;
            if ($cookie['max-age'] !== null) {
                $expires = new DateTimeImmutable('@' . (time() + $cookie['max-age']));
            } elseif ($cookie['expires']) {
                $expires = new DateTimeImmutable('@' . strtotime($cookie['expires']));
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

    /**
     * Remove expired cookies from the collection.
     *
     * @param string $host The host to check for expired cookies on.
     * @param string $path The path to check for expired cookies on.
     * @return void
     */
    protected function removeExpiredCookies($host, $path)
    {
        $time = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $hostPattern = '/' . preg_quote($host, '/') . '$/';

        foreach ($this->cookies as $i => $cookie) {
            $expired = $cookie->isExpired($time);
            $pathMatches = strpos($path, $cookie->getPath()) === 0;
            $hostMatches = preg_match($hostPattern, $cookie->getDomain());
            if ($pathMatches && $hostMatches && $expired) {
                unset($this->cookies[$i]);
            }
        }
    }
}
