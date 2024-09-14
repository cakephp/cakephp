<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.5.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Cookie;

use ArrayIterator;
use Countable;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use IteratorAggregate;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Traversable;
use TypeError;
use function Cake\Core\triggerWarning;

/**
 * Cookie Collection
 *
 * Provides an immutable collection of cookies objects. Adding or removing
 * to a collection returns a *new* collection that you must retain.
 *
 * @template-implements \IteratorAggregate<string, \Cake\Http\Cookie\CookieInterface>
 */
class CookieCollection implements IteratorAggregate, Countable
{
    /**
     * Cookie objects
     *
     * @var array<string, \Cake\Http\Cookie\CookieInterface>
     */
    protected array $cookies = [];

    /**
     * Constructor
     *
     * @param array<\Cake\Http\Cookie\CookieInterface> $cookies Array of cookie objects
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
     * @param list<string> $header The array of set-cookie header values.
     * @param array<string, mixed> $defaults The defaults attributes.
     * @return static
     */
    public static function createFromHeader(array $header, array $defaults = []): static
    {
        $cookies = [];
        foreach ($header as $value) {
            try {
                $cookies[] = Cookie::createFromHeaderString($value, $defaults);
            } catch (Exception | TypeError) {
                // Don't blow up on invalid cookies
            }
        }

        return new static($cookies);
    }

    /**
     * Create a new collection from the cookies in a ServerRequest
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request to extract cookie data from
     * @return static
     */
    public static function createFromServerRequest(ServerRequestInterface $request): static
    {
        $data = $request->getCookieParams();
        $cookies = [];
        foreach ($data as $name => $value) {
            $cookies[] = new Cookie((string)$name, $value);
        }

        return new static($cookies);
    }

    /**
     * Get the number of cookies in the collection.
     *
     * @return int
     */
    public function count(): int
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
    public function add(CookieInterface $cookie): static
    {
        $new = clone $this;
        $new->cookies[$cookie->getId()] = $cookie;

        return $new;
    }

    /**
     * Get the first cookie by name.
     *
     * @param string $name The name of the cookie.
     * @return \Cake\Http\Cookie\CookieInterface
     * @throws \InvalidArgumentException If cookie not found.
     */
    public function get(string $name): CookieInterface
    {
        $cookie = $this->__get($name);

        if ($cookie === null) {
            throw new InvalidArgumentException(
                sprintf(
                    'Cookie `%s` not found. Use `has()` to check first for existence.',
                    $name
                )
            );
        }

        return $cookie;
    }

    /**
     * Check if a cookie with the given name exists
     *
     * @param string $name The cookie name to check.
     * @return bool True if the cookie exists, otherwise false.
     */
    public function has(string $name): bool
    {
        return $this->__get($name) !== null;
    }

    /**
     * Get the first cookie by name if cookie with provided name exists
     *
     * @param string $name The name of the cookie.
     * @return \Cake\Http\Cookie\CookieInterface|null
     */
    public function __get(string $name): ?CookieInterface
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
    public function __isset(string $name): bool
    {
        return $this->__get($name) !== null;
    }

    /**
     * Create a new collection with all cookies matching $name removed.
     *
     * If the cookie is not in the collection, this method will do nothing.
     *
     * @param string $name The name of the cookie to remove.
     * @return static
     */
    public function remove(string $name): static
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
     * @param array<\Cake\Http\Cookie\CookieInterface> $cookies Array of cookie objects
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function checkCookies(array $cookies): void
    {
        foreach ($cookies as $index => $cookie) {
            if (!$cookie instanceof CookieInterface) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Expected `%s[]` as $cookies but instead got `%s` at index %d',
                        static::class,
                        get_debug_type($cookie),
                        $index
                    )
                );
            }
        }
    }

    /**
     * Gets the iterator
     *
     * @return \Traversable<string, \Cake\Http\Cookie\CookieInterface>
     */
    public function getIterator(): Traversable
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
    public function addToRequest(RequestInterface $request, array $extraCookies = []): RequestInterface
    {
        $uri = $request->getUri();
        $cookies = $this->findMatchingCookies(
            $uri->getScheme(),
            $uri->getHost(),
            $uri->getPath() ?: '/'
        );
        $cookies = $extraCookies + $cookies;
        $cookiePairs = [];
        foreach ($cookies as $key => $value) {
            $cookie = sprintf('%s=%s', rawurlencode((string)$key), rawurlencode($value));
            $size = strlen($cookie);
            if ($size > 4096) {
                triggerWarning(sprintf(
                    'The cookie `%s` exceeds the recommended maximum cookie length of 4096 bytes.',
                    $key
                ));
            }
            $cookiePairs[] = $cookie;
        }

        if (!$cookiePairs) {
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
     * @return array<string, mixed> An array of cookie name/value pairs
     */
    protected function findMatchingCookies(string $scheme, string $host, string $path): array
    {
        $out = [];
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        foreach ($this->cookies as $cookie) {
            if ($scheme === 'http' && $cookie->isSecure()) {
                continue;
            }
            if (!str_starts_with($path, $cookie->getPath())) {
                continue;
            }
            $domain = $cookie->getDomain();
            $leadingDot = str_starts_with($domain, '.');
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
    public function addFromResponse(ResponseInterface $response, RequestInterface $request): static
    {
        $uri = $request->getUri();
        $host = $uri->getHost();
        $path = $uri->getPath() ?: '/';

        $cookies = static::createFromHeader(
            $response->getHeader('Set-Cookie'),
            ['domain' => $host, 'path' => $path]
        );
        $new = clone $this;
        foreach ($cookies as $cookie) {
            $new->cookies[$cookie->getId()] = $cookie;
        }
        $new->removeExpiredCookies($host, $path);

        return $new;
    }

    /**
     * Remove expired cookies from the collection.
     *
     * @param string $host The host to check for expired cookies on.
     * @param string $path The path to check for expired cookies on.
     * @return void
     */
    protected function removeExpiredCookies(string $host, string $path): void
    {
        $time = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $hostPattern = '/' . preg_quote($host, '/') . '$/';

        foreach ($this->cookies as $i => $cookie) {
            if (!$cookie->isExpired($time)) {
                continue;
            }
            $pathMatches = str_starts_with($path, $cookie->getPath());
            $hostMatches = preg_match($hostPattern, $cookie->getDomain());
            if ($pathMatches && $hostMatches) {
                unset($this->cookies[$i]);
            }
        }
    }
}
