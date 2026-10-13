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

/**
 * Immutable value object representing a URI as defined by RFC 3986.
 */
class Uri implements UriInterface, Stringable
{
    /**
     * Unreserved characters as defined in RFC 3986 section 2.3.
     *
     * Unicode letters are included so that IRIs are kept readable.
     *
     * @var string
     */
    protected const string CHAR_UNRESERVED = 'a-zA-Z0-9_\-\.~\pL';

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
     * @var string
     */
    protected string $scheme = '';

    /**
     * @var string
     */
    protected string $userInfo = '';

    /**
     * @var string
     */
    protected string $host = '';

    /**
     * @var int|null
     */
    protected ?int $port = null;

    /**
     * @var string
     */
    protected string $path = '';

    /**
     * @var string
     */
    protected string $query = '';

    /**
     * @var string
     */
    protected string $fragment = '';

    /**
     * Constructor
     *
     * @param string $uri The URI to parse.
     * @throws \InvalidArgumentException When the URI cannot be parsed.
     */
    public function __construct(string $uri = '')
    {
        if ($uri === '') {
            return;
        }

        $parts = $this->parse($uri);

        $this->scheme = isset($parts['scheme']) ? $this->filterScheme($parts['scheme']) : '';
        $this->userInfo = isset($parts['user']) ? $this->filterUserInfoPart($parts['user']) : '';
        if (isset($parts['pass'])) {
            $this->userInfo .= ':' . $this->filterUserInfoPart($parts['pass']);
        }
        $this->host = strtolower($parts['host'] ?? '');
        $this->port = $parts['port'] ?? null;
        $this->path = $this->filterPath($parts['path'] ?? '');
        $this->query = $this->filterQuery($parts['query'] ?? '');
        $this->fragment = $this->filterFragment($parts['fragment'] ?? '');
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        $uri = '';
        if ($this->scheme !== '') {
            $uri .= $this->scheme . ':';
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

        if ($this->query !== '') {
            $uri .= '?' . $this->query;
        }
        if ($this->fragment !== '') {
            $uri .= '#' . $this->fragment;
        }

        return $uri;
    }

    /**
     * @inheritDoc
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * @inheritDoc
     */
    public function getAuthority(): string
    {
        if ($this->host === '') {
            return '';
        }

        $authority = $this->host;
        if ($this->userInfo !== '') {
            $authority = $this->userInfo . '@' . $authority;
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
        return $this->userInfo;
    }

    /**
     * @inheritDoc
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @inheritDoc
     */
    public function getPort(): ?int
    {
        if ($this->port === null) {
            return null;
        }
        if (isset(static::DEFAULT_PORTS[$this->scheme]) && static::DEFAULT_PORTS[$this->scheme] === $this->port) {
            return null;
        }

        return $this->port;
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
        return $this->query;
    }

    /**
     * @inheritDoc
     */
    public function getFragment(): string
    {
        return $this->fragment;
    }

    /**
     * @inheritDoc
     */
    public function withScheme(string $scheme): static
    {
        $scheme = $this->filterScheme($scheme);
        if ($scheme === $this->scheme) {
            return $this;
        }

        return clone($this, ['scheme' => $scheme]);
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
        if ($info === $this->userInfo) {
            return $this;
        }

        return clone($this, ['userInfo' => $info]);
    }

    /**
     * @inheritDoc
     */
    public function withHost(string $host): static
    {
        $host = strtolower($host);
        if ($host === $this->host) {
            return $this;
        }

        return clone($this, ['host' => $host]);
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
        if ($port === $this->port) {
            return $this;
        }

        return clone($this, ['port' => $port]);
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
        if ($query === $this->query) {
            return $this;
        }

        return clone($this, ['query' => $query]);
    }

    /**
     * @inheritDoc
     */
    public function withFragment(string $fragment): static
    {
        $fragment = $this->filterFragment($fragment);
        if ($fragment === $this->fragment) {
            return $this;
        }

        return clone($this, ['fragment' => $fragment]);
    }

    /**
     * Split a URI into its components.
     *
     * `parse_url()` replaces control characters, and depending on the locale also
     * bytes in the range 0x80-0x9F, with `_`. To keep those bytes intact a sanitized
     * copy of the same length is parsed and the components are mapped back onto
     * the original string.
     *
     * @param string $uri The URI to parse.
     * @return array{scheme?: string, host?: string, port?: int, user?: string, pass?: string, path?: string, query?: string, fragment?: string}
     * @throws \InvalidArgumentException When the URI cannot be parsed.
     */
    protected function parse(string $uri): array
    {
        $sanitized = (string)preg_replace('/[\x00-\x1F\x7F-\xFF]/', 'x', $uri);
        $parts = parse_url($sanitized);
        if ($parts === false) {
            throw new InvalidArgumentException(sprintf('Unable to parse URI `%s`.', $uri));
        }
        if ($sanitized === $uri) {
            return $parts;
        }

        $offset = 0;
        foreach (['scheme', 'user', 'pass', 'host', 'path', 'query', 'fragment'] as $key) {
            if (!isset($parts[$key])) {
                continue;
            }
            $length = strlen($parts[$key]);
            $position = strpos($sanitized, $parts[$key], $offset);
            if ($position === false) {
                throw new InvalidArgumentException(sprintf('Unable to parse URI `%s`.', $uri));
            }
            $parts[$key] = substr($uri, $position, $length);
            $offset = $position + $length;
        }

        return $parts;
    }

    /**
     * Normalize and validate a scheme.
     *
     * @param string $scheme The scheme to filter.
     * @return string
     * @throws \InvalidArgumentException When the scheme contains invalid characters.
     */
    protected function filterScheme(string $scheme): string
    {
        $scheme = strtolower((string)preg_replace('#:(//)?$#', '', $scheme));
        if ($scheme !== '' && !preg_match('/^[a-z][a-z0-9+.\-]*$/D', $scheme)) {
            throw new InvalidArgumentException(sprintf('Invalid scheme `%s` provided.', $scheme));
        }

        return $scheme;
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
            '/(?:[^%' . static::CHAR_UNRESERVED . static::CHAR_SUB_DELIMS . ']+|%(?![A-Fa-f0-9]{2}))/u',
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
        return $this->encode($path, '/(?:[^' . static::CHAR_UNRESERVED . ')(:@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))/u');
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
            '/(?:[^' . static::CHAR_UNRESERVED . static::CHAR_SUB_DELIMS . '%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/u',
        );
    }

    /**
     * Percent-encode all characters matching a pattern.
     *
     * Bytes that are not valid UTF-8 are always encoded.
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

        if (!preg_match('//u', $value)) {
            $value = $this->encodeInvalidUtf8($value);
        }

        return (string)preg_replace_callback(
            $pattern,
            static fn(array $matches): string => rawurlencode($matches[0]),
            $value,
        );
    }

    /**
     * Percent-encode byte sequences that are not valid UTF-8.
     *
     * @param string $value The value to encode.
     * @return string
     */
    protected function encodeInvalidUtf8(string $value): string
    {
        // Valid multibyte sequences are matched first and kept, any other
        // non-ASCII byte is captured in group 1 and encoded.
        $pattern = '/[\xC2-\xDF][\x80-\xBF]'
            . '|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]'
            . '|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2}'
            . '|([\x80-\xFF])/';

        return (string)preg_replace_callback(
            $pattern,
            static fn(array $matches): string => isset($matches[1]) ? rawurlencode($matches[1]) : $matches[0],
            $value,
        );
    }
}
