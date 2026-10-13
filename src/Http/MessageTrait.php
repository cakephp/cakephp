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
use Psr\Http\Message\StreamInterface;

/**
 * Implements the methods defined in `Psr\Http\Message\MessageInterface`.
 */
trait MessageTrait
{
    /**
     * List of all registered headers, as original name => list of values.
     *
     * @var array<non-empty-string, non-empty-list<string>>
     */
    protected array $headers = [];

    /**
     * Map of lowercased header name to the original name used to register the header.
     *
     * @var array<non-empty-string, non-empty-string>
     */
    protected array $headerNames = [];

    /**
     * The HTTP protocol version.
     *
     * @var string
     */
    protected string $protocol = '1.1';

    /**
     * The message body.
     *
     * @var \Psr\Http\Message\StreamInterface
     */
    protected StreamInterface $stream;

    /**
     * Retrieves the HTTP protocol version as a string.
     *
     * @return string HTTP protocol version.
     */
    public function getProtocolVersion(): string
    {
        return $this->protocol;
    }

    /**
     * Return an instance with the specified HTTP protocol version.
     *
     * @param string $version HTTP protocol version.
     * @return static
     * @throws \InvalidArgumentException For unsupported protocol versions.
     */
    public function withProtocolVersion(string $version): static
    {
        // HTTP/1 uses a "<major>.<minor>" version while HTTP/2 and HTTP/3 use "<major>" or "<major>.0".
        if (!preg_match('#^(?:1\.[01]|[23](?:\.0)?)$#D', $version)) {
            throw new InvalidArgumentException(sprintf('Unsupported HTTP protocol version `%s` provided.', $version));
        }

        return clone($this, ['protocol' => $version]);
    }

    /**
     * Retrieves all message headers.
     *
     * @return array<non-empty-string, non-empty-list<string>> Header names mapped to their list of values.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $name Case-insensitive header name.
     * @return bool
     */
    public function hasHeader(string $name): bool
    {
        return isset($this->headerNames[strtolower($name)]);
    }

    /**
     * Retrieves a message header value by the given case-insensitive name.
     *
     * @param string $name Case-insensitive header name.
     * @return list<string> The header values, or an empty array if the header is not set.
     */
    public function getHeader(string $name): array
    {
        $name = strtolower($name);
        if (!isset($this->headerNames[$name])) {
            return [];
        }

        return $this->headers[$this->headerNames[$name]];
    }

    /**
     * Retrieves a comma-separated string of the values for a single header.
     *
     * @param string $name Case-insensitive header name.
     * @return string The header values joined by a comma, or an empty string if the header is not set.
     */
    public function getHeaderLine(string $name): string
    {
        return implode(',', $this->getHeader($name));
    }

    /**
     * Return an instance with the provided header, replacing any existing
     * values of any headers with the same case-insensitive name.
     *
     * @param string $name Case-insensitive header name.
     * @param array<string>|string $value Header value(s).
     * @return static
     * @throws \InvalidArgumentException For invalid header names or values.
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function withHeader(string $name, $value): static
    {
        $this->assertHeaderName($name);
        $value = $this->filterHeaderValue($value);

        $new = clone $this;
        $new->replaceHeader($name, $value);

        return $new;
    }

    /**
     * Return an instance with the specified header appended with the given value.
     *
     * @param string $name Case-insensitive header name.
     * @param array<string>|string $value Header value(s).
     * @return static
     * @throws \InvalidArgumentException For invalid header names or values.
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function withAddedHeader(string $name, $value): static
    {
        $this->assertHeaderName($name);
        $normalized = strtolower($name);
        if (!isset($this->headerNames[$normalized])) {
            return $this->withHeader($name, $value);
        }

        $value = $this->filterHeaderValue($value);
        $original = $this->headerNames[$normalized];

        $new = clone $this;
        $new->headers[$original] = array_merge($this->headers[$original], $value);

        return $new;
    }

    /**
     * Return an instance without the specified header.
     *
     * @param string $name Case-insensitive header name.
     * @return static
     */
    public function withoutHeader(string $name): static
    {
        $new = clone $this;
        $new->removeHeader($name);

        return $new;
    }

    /**
     * Gets the body of the message.
     *
     * @return \Psr\Http\Message\StreamInterface
     */
    public function getBody(): StreamInterface
    {
        return $this->stream;
    }

    /**
     * Return an instance with the specified message body.
     *
     * @param \Psr\Http\Message\StreamInterface $body The new body.
     * @return static
     */
    public function withBody(StreamInterface $body): static
    {
        return clone($this, ['stream' => $body]);
    }

    /**
     * Replace a header on the current instance.
     *
     * @param non-empty-string $name The header name.
     * @param non-empty-list<string> $value The filtered header values.
     * @return void
     */
    protected function replaceHeader(string $name, array $value): void
    {
        $this->removeHeader($name);
        $this->headerNames[strtolower($name)] = $name;
        $this->headers[$name] = $value;
    }

    /**
     * Remove a header from the current instance.
     *
     * @param string $name Case-insensitive header name.
     * @return void
     */
    protected function removeHeader(string $name): void
    {
        $normalized = strtolower($name);
        if (!isset($this->headerNames[$normalized])) {
            return;
        }

        unset($this->headers[$this->headerNames[$normalized]], $this->headerNames[$normalized]);
    }

    /**
     * Validate a header name.
     *
     * @param string $name The header name.
     * @return void
     * @phpstan-assert non-empty-string $name
     * @throws \InvalidArgumentException When the name isn't a valid RFC 7230 token.
     */
    protected function assertHeaderName(string $name): void
    {
        if (!preg_match('/^[a-zA-Z0-9\'`#$%&*+.^_|~!-]+$/D', $name)) {
            throw new InvalidArgumentException(sprintf('`%s` is not a valid header name.', $name));
        }
    }

    /**
     * Validate and normalize header values.
     *
     * @param mixed $values A header value or list of header values.
     * @return non-empty-list<string>
     * @throws \InvalidArgumentException When a value is invalid.
     */
    protected function filterHeaderValue(mixed $values): array
    {
        if (!is_array($values)) {
            $values = [$values];
        }
        if ($values === []) {
            throw new InvalidArgumentException(
                'Invalid header value: must be a string or array of strings; cannot be an empty array.',
            );
        }

        $result = [];
        foreach ($values as $value) {
            if (!is_string($value) && !is_int($value) && !is_float($value)) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid header value type; must be a string or numeric; received `%s`.',
                    get_debug_type($value),
                ));
            }
            $value = (string)$value;

            // Reject CRLF injection and non-visible characters. Only obsolete line
            // folding (CRLF followed by space or tab) is accepted (RFC 7230 section 3.2).
            if (
                preg_match("#(?:(?<!\r)\n|\r(?!\n)|\r\n(?![ \t]))#", $value) ||
                preg_match('/[^\x09\x0a\x0d\x20-\x7E\x80-\xFE]/', $value)
            ) {
                throw new InvalidArgumentException(sprintf('`%s` is not a valid header value.', $value));
            }

            // Normalize line folding to a single space and trim optional whitespace.
            $result[] = trim(str_replace(["\r\n\t", "\r\n "], ' ', $value), "\t ");
        }

        return $result;
    }
}
