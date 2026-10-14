<?php
declare(strict_types=1);

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
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 *
 * Parts of this file are derived from laminas-diactoros
 * @copyright Copyright (c) 2020 Laminas Project a Series of LF Projects, LLC. (https://getlaminas.org/)
 * @license   https://github.com/laminas/laminas-diactoros/blob/3.9.x/LICENSE.md New BSD License
 */
namespace Cake\Http;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use SensitiveParameter;
use Stringable;
use Uri\InvalidUriException;
use Uri\Rfc3986\Uri as Rfc3986Uri;

/**
 * Immutable PSR-7 URI using PHP's RFC 3986 parser.
 *
 * Components preserve percent-encoded sequences and dot segments. Non-ASCII
 * input is percent-encoded before parsing.
 */
class Uri implements UriInterface, Stringable
{
    /**
     * Unreserved characters as defined in RFC 3986 section 2.3.
     *
     * @var string
     */
    protected const string CHAR_UNRESERVED = 'a-zA-Z0-9_\-\.~';

    /**
     * Sub-delimiter characters as defined in RFC 3986 section 2.2.
     *
     * @var string
     */
    protected const string CHAR_SUB_DELIMS = '!\$&\'\(\)\*\+,;=';

    /**
     * Default ports for well known schemes.
     *
     * @var array<string, int>
     */
    protected const array DEFAULT_PORTS = [
        'http' => 80,
        'https' => 443,
    ];

    /**
     * Native URI containing the authority, scheme, query and fragment.
     *
     * An empty host allows PSR-7 user info and port mutations in any order.
     *
     * @var \Uri\Rfc3986\Uri
     */
    protected Rfc3986Uri $uri;

    /**
     * PSR-7 permits a relative path even when an authority is present.
     *
     * @var string
     */
    protected string $path = '';

    /**
     * Constructor
     *
     * @param \Uri\Rfc3986\Uri|string $uri The URI to parse or adapt to PSR-7.
     * @throws \InvalidArgumentException When the URI cannot be parsed.
     */
    public function __construct(string|Rfc3986Uri $uri = '')
    {
        if (is_string($uri)) {
            try {
                // Encode characters outside RFC 3986 before parsing, preserving delimiters
                // and existing percent-encoded sequences.
                $uri = new Rfc3986Uri($this->encode(
                    $uri,
                    '/(?:[^' . static::CHAR_UNRESERVED . static::CHAR_SUB_DELIMS
                        . ':\/\[\]@?#%]+|%(?![A-Fa-f0-9]{2}))/',
                ));
            } catch (InvalidUriException $e) {
                throw new InvalidArgumentException('Unable to parse URI.', previous: $e);
            }
        }

        $this->path = $this->filterPath($uri->getRawPath());
        $query = $this->filterQuery($uri->getRawQuery() ?? '');
        $fragment = $this->filterFragment($uri->getRawFragment() ?? '');
        $this->uri = $uri->withPath('')
            ->withHost(strtolower($uri->getRawHost() ?? ''))
            ->withQuery($query === '' ? null : $query)
            ->withFragment($fragment === '' ? null : $fragment);
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        $uri = '';
        if ($this->getScheme() !== '') {
            $uri .= $this->getScheme() . ':';
        }

        $authority = $this->getAuthority();
        if ($authority !== '') {
            $uri .= '//' . $authority;
        }

        $path = $this->getPath();
        if ($path !== '' && $authority !== '' && !str_starts_with($path, '/')) {
            $path = '/' . $path;
        }
        $uri .= $path;

        if ($this->getQuery() !== '') {
            $uri .= '?' . $this->getQuery();
        }
        if ($this->getFragment() !== '') {
            $uri .= '#' . $this->getFragment();
        }

        return $uri;
    }

    /**
     * @inheritDoc
     */
    public function getScheme(): string
    {
        return strtolower($this->uri->getRawScheme() ?? '');
    }

    /**
     * @inheritDoc
     */
    public function getAuthority(): string
    {
        if ($this->getHost() === '') {
            return '';
        }

        $authority = $this->getHost();
        if ($this->getUserInfo() !== '') {
            $authority = $this->getUserInfo() . '@' . $authority;
        }

        $port = $this->getPort();
        if ($port !== null) {
            $authority .= ':' . $port;
        }

        return $authority;
    }

    /**
     * @inheritDoc
     */
    public function getUserInfo(): string
    {
        return $this->uri->getRawUserInfo() ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getHost(): string
    {
        return $this->uri->getRawHost() ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getPort(): ?int
    {
        $port = $this->uri->getPort();
        if ($port === null) {
            return null;
        }
        if (isset(static::DEFAULT_PORTS[$this->getScheme()]) && static::DEFAULT_PORTS[$this->getScheme()] === $port) {
            return null;
        }

        return $port;
    }

    /**
     * @inheritDoc
     */
    public function getPath(): string
    {
        // Collapse multiple leading slashes so the path can't be mistaken for an authority.
        if (str_starts_with($this->path, '//')) {
            return '/' . ltrim($this->path, '/');
        }

        return $this->path;
    }

    /**
     * @inheritDoc
     */
    public function getQuery(): string
    {
        return $this->uri->getRawQuery() ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getFragment(): string
    {
        return $this->uri->getRawFragment() ?? '';
    }

    /**
     * @inheritDoc
     */
    public function withScheme(string $scheme): static
    {
        $scheme = strtolower((string)preg_replace('#:(//)?$#', '', $scheme));
        if ($scheme === $this->getScheme()) {
            return $this;
        }

        return $this->withNativeUri(
            static fn(Rfc3986Uri $uri): Rfc3986Uri => $uri->withScheme($scheme === '' ? null : $scheme),
        );
    }

    /**
     * @inheritDoc
     */
    public function withUserInfo(string $user, #[SensitiveParameter] ?string $password = null): static
    {
        $info = $this->filterUserInfoPart($user);
        if ($info !== '' && $password !== null && $password !== '') {
            $info .= ':' . $this->filterUserInfoPart($password);
        }
        if ($info === $this->getUserInfo()) {
            return $this;
        }

        return $this->withNativeUri(
            static fn(Rfc3986Uri $uri): Rfc3986Uri => $uri->withUserInfo($info === '' ? null : $info),
        );
    }

    /**
     * @inheritDoc
     */
    public function withHost(string $host): static
    {
        $host = strtolower($host);
        if ($host === $this->getHost()) {
            return $this;
        }

        return $this->withNativeUri(static fn(Rfc3986Uri $uri): Rfc3986Uri => $uri->withHost($host));
    }

    /**
     * @inheritDoc
     */
    public function withPort(?int $port): static
    {
        if ($port !== null && ($port < 1 || $port > 65_535)) {
            throw new InvalidArgumentException(
                sprintf('Invalid port `%d` specified; must be a valid TCP/UDP port.', $port),
            );
        }
        if ($port === $this->uri->getPort()) {
            return $this;
        }

        return $this->withNativeUri(static fn(Rfc3986Uri $uri): Rfc3986Uri => $uri->withPort($port));
    }

    /**
     * @inheritDoc
     */
    public function withPath(string $path): static
    {
        if (str_contains($path, '?')) {
            throw new InvalidArgumentException('Invalid path provided; must not contain a query string.');
        }
        if (str_contains($path, '#')) {
            throw new InvalidArgumentException('Invalid path provided; must not contain a URI fragment.');
        }

        $path = $this->filterPath($path);
        if ($path === $this->path) {
            return $this;
        }

        return clone($this, ['path' => $path]);
    }

    /**
     * @inheritDoc
     */
    public function withQuery(string $query): static
    {
        if (str_contains($query, '#')) {
            throw new InvalidArgumentException('Query string must not include a URI fragment.');
        }

        $query = $this->filterQuery($query);
        if ($query === $this->getQuery()) {
            return $this;
        }

        return $this->withNativeUri(
            static fn(Rfc3986Uri $uri): Rfc3986Uri => $uri->withQuery($query === '' ? null : $query),
        );
    }

    /**
     * @inheritDoc
     */
    public function withFragment(string $fragment): static
    {
        $fragment = $this->filterFragment($fragment);
        if ($fragment === $this->getFragment()) {
            return $this;
        }

        return $this->withNativeUri(
            static fn(Rfc3986Uri $uri): Rfc3986Uri => $uri->withFragment($fragment === '' ? null : $fragment),
        );
    }

    /**
     * Apply a native mutation using PSR-7 exception semantics.
     *
     * @param callable(\Uri\Rfc3986\Uri): \Uri\Rfc3986\Uri $mutator The native mutation.
     * @return static
     * @throws \InvalidArgumentException When a component is invalid.
     */
    protected function withNativeUri(callable $mutator): static
    {
        try {
            $uri = $mutator($this->uri);
        } catch (InvalidUriException $e) {
            throw new InvalidArgumentException($e->getMessage(), previous: $e);
        }

        return clone($this, ['uri' => $uri]);
    }

    /**
     * Percent-encode a user or password component.
     *
     * @param string $part The user info part to filter.
     * @return string
     */
    protected function filterUserInfoPart(string $part): string
    {
        return $this->encode(
            $part,
            '/(?:[^%' . static::CHAR_UNRESERVED . static::CHAR_SUB_DELIMS . ']+|%(?![A-Fa-f0-9]{2}))/',
        );
    }

    /**
     * Percent-encode a path, leaving already encoded sequences intact.
     *
     * @param string $path The path to filter.
     * @return string
     */
    protected function filterPath(string $path): string
    {
        return $this->encode($path, '/(?:[^' . static::CHAR_UNRESERVED . ')(:@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))/');
    }

    /**
     * Percent-encode a query string, preserving its delimiters.
     *
     * @param string $query The query string to filter.
     * @return string
     */
    protected function filterQuery(string $query): string
    {
        if (str_starts_with($query, '?')) {
            $query = substr($query, 1);
        }

        // Both `&` and `=` are allowed by the query/fragment filter, so keys
        // and values can be encoded together without splitting the query.
        return $this->filterQueryOrFragment($query);
    }

    /**
     * Percent-encode a fragment.
     *
     * @param string $fragment The fragment to filter.
     * @return string
     */
    protected function filterFragment(string $fragment): string
    {
        if (str_starts_with($fragment, '#')) {
            $fragment = '%23' . substr($fragment, 1);
        }

        return $this->filterQueryOrFragment($fragment);
    }

    /**
     * Percent-encode a query key, query value or fragment.
     *
     * @param string $value The value to filter.
     * @return string
     */
    protected function filterQueryOrFragment(string $value): string
    {
        return $this->encode(
            $value,
            '/(?:[^' . static::CHAR_UNRESERVED . static::CHAR_SUB_DELIMS . '%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/',
        );
    }

    /**
     * Percent-encode all characters matching a pattern.
     *
     * Non-ASCII bytes are always encoded.
     *
     * @param string $value The value to encode.
     * @param string $pattern The pattern matching the characters to encode.
     * @return string
     */
    protected function encode(string $value, string $pattern): string
    {
        if ($value === '') {
            return '';
        }

        return (string)preg_replace_callback(
            $pattern,
            static fn(array $matches): string => rawurlencode($matches[0]),
            $value,
        );
    }
}
