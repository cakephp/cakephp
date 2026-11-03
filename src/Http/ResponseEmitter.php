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
 * @since         3.3.5
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 *
 * Parts of this file are derived from Zend-Diactoros
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (https://www.zend.com/)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */
namespace Cake\Http;

use Cake\Http\Cookie\Cookie;
use Cake\Http\Cookie\CookieInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Link\LinkInterface;
use RuntimeException;
use function Cake\Core\triggerWarning;

/**
 * Emits a Response to the PHP Server API.
 */
class ResponseEmitter
{
    /**
     * Constructor
     *
     * @param int $maxBufferLength Maximum output buffering size for each iteration.
     * @param bool $flushOutputBuffers Remove nested output buffers before emitting live bodies.
     *   Empty inner compression buffers are discarded. Nonempty custom handlers must be drained by the caller.
     */
    public function __construct(
        protected int $maxBufferLength = 8192,
        protected bool $flushOutputBuffers = false,
    ) {
    }

    /**
     * Emit a response.
     *
     * Emits a response, including status line, headers, and the message body,
     * according to the environment.
     *
     * @param \Psr\Http\Message\ResponseInterface $response The response to emit.
     * @param bool|null $bodyIsRange Whether Content-Range is already applied to the body.
     *   Null detects partial readable bodies by size; bodies of unknown size are assumed already ranged.
     *   Pass false to slice a full stream of unknown size, or true to forward an already ranged body.
     * @return void
     */
    public function emit(ResponseInterface $response, ?bool $bodyIsRange = null): void
    {
        if ($this->flushOutputBuffers && !$response->getBody()->isSeekable()) {
            $this->drainOutputBuffers();
        }

        $file = '';
        $line = 0;
        if (headers_sent($file, $line)) {
            $message = "Unable to emit headers. Headers sent in file={$file} line={$line}";
            triggerWarning($message);
        }

        $this->emitStatusLine($response);
        $this->emitHeaders($response);

        $range = $response->getStatusCode() >= 200 && !in_array($response->getStatusCode(), [204, 205, 304], true)
            ? $this->parseContentRange($response->getHeaderLine('Content-Range'))
            : null;
        if (is_array($range) && $bodyIsRange === null) {
            $body = $response->getBody();
            $size = $body->getSize();
            $bodyIsRange = $body->isReadable() && ($size === null || $size === $range[2] - $range[1] + 1);
        }
        if (is_array($range) && !$bodyIsRange) {
            $this->emitBodyRange($range, $response);
        } else {
            $this->emitBody($response);
        }

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }

    /**
     * Emit the message body.
     *
     * @param \Psr\Http\Message\ResponseInterface $response The response to emit
     * @return void
     */
    protected function emitBody(ResponseInterface $response): void
    {
        $body = $response->getBody();
        if ($response->getStatusCode() < 200 || in_array($response->getStatusCode(), [204, 205, 304], true)) {
            if (!$body->isSeekable()) {
                $body->close();
            }

            return;
        }

        if (!$body->isReadable()) {
            echo $body;

            return;
        }

        $live = !$body->isSeekable();
        if (!$live) {
            $body->rewind();
        }
        try {
            do {
                $chunk = $body->read($this->maxBufferLength);
                $eof = $body->eof();
                if ($chunk === '' && !$eof) {
                    throw new RuntimeException(
                        'Cannot emit a stream that returns no data before EOF; use a blocking stream.',
                    );
                }
                echo $chunk;
                if ($live && ob_get_level() <= 1) {
                    if (ob_get_level() === 1) {
                        ob_flush();
                    }
                    flush();
                }
            } while (!$eof);
        } finally {
            if ($live) {
                $body->close();
            }
        }
    }

    /**
     * Drain nested buffers before streaming, retaining the outer buffer for incremental flushing.
     *
     * @return void
     */
    protected function drainOutputBuffers(): void
    {
        $buffers = ob_get_status(true);
        foreach (array_slice($buffers, 1) as $buffer) {
            if (!($buffer['flags'] & PHP_OUTPUT_HANDLER_REMOVABLE)) {
                throw new RuntimeException('Cannot drain a non-removable output buffer.');
            }
        }
        if (isset($buffers[0]) && !($buffers[0]['flags'] & PHP_OUTPUT_HANDLER_FLUSHABLE)) {
            throw new RuntimeException('Cannot flush the outer output buffer.');
        }
        while (ob_get_level() > 1) {
            $buffer = ob_get_status();
            if ($buffer['buffer_used'] > 0 && $buffer['name'] !== 'default output handler') {
                throw new RuntimeException('Drain nonempty custom output handlers before emitting a live response.');
            }
            if (ob_get_length() === 0) {
                ob_end_clean();
            } else {
                ob_end_flush();
            }
        }
    }

    /**
     * Emit a range of the message body.
     *
     * @param array $range The range data to emit
     * @param \Psr\Http\Message\ResponseInterface $response The response to emit
     * @return void
     */
    protected function emitBodyRange(array $range, ResponseInterface $response): void
    {
        [, $first, $last] = $range;

        $body = $response->getBody();

        if (!$body->isSeekable()) {
            $contents = $body->getContents();
            echo substr($contents, $first, $last - $first + 1);

            return;
        }

        $body->seek($first);
        $pos = 0;
        /** @var int $length */
        $length = $last - $first + 1;
        while (!$body->eof() && $pos < $length) {
            $chunk = $body->read(min($this->maxBufferLength, $length - $pos));
            if ($chunk === '') {
                break;
            }

            echo $chunk;
            $pos += strlen($chunk);
        }
    }

    /**
     * Emit the status line.
     *
     * Emits the status line using the protocol version and status code from
     * the response; if a reason phrase is available, it, too, is emitted.
     *
     * @param \Psr\Http\Message\ResponseInterface $response The response to emit
     * @return void
     */
    protected function emitStatusLine(ResponseInterface $response): void
    {
        $reasonPhrase = $response->getReasonPhrase();
        header(sprintf(
            'HTTP/%s %d%s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $reasonPhrase ? ' ' . $reasonPhrase : '',
        ));
    }

    /**
     * Emit response headers.
     *
     * Loops through each header, emitting each; if the header value
     * is an array with multiple values, ensures that each is sent
     * in such a way as to create aggregate headers (instead of replace
     * the previous).
     *
     * @param \Psr\Http\Message\ResponseInterface $response The response to emit
     * @return void
     */
    protected function emitHeaders(ResponseInterface $response): void
    {
        $cookies = [];
        if ($response instanceof Response) {
            $cookies = iterator_to_array($response->getCookieCollection());
        }

        foreach ($response->getHeaders() as $name => $values) {
            if (strtolower($name) === 'set-cookie') {
                $cookies = array_merge($cookies, $values);
                continue;
            }
            $first = true;
            foreach ($values as $value) {
                header(sprintf(
                    '%s: %s',
                    $name,
                    $value,
                ), $first);
                $first = false;
            }
        }

        $this->emitCookies($cookies);
        $this->emitLinks($response);
    }

    /**
     * Emit PSR-13 links as HTTP Link headers.
     *
     * @param \Psr\Http\Message\ResponseInterface $response The response to emit.
     * @return void
     */
    protected function emitLinks(ResponseInterface $response): void
    {
        if (!$response instanceof Response) {
            return;
        }

        $links = $response->getLinks()->getLinks();
        foreach ($links as $link) {
            header(sprintf('Link: %s', $this->formatLink($link)), false);
        }
    }

    /**
     * Format a PSR-13 link as an HTTP Link header value.
     *
     * @param \Psr\Link\LinkInterface $link The link to format.
     * @return string The formatted header value.
     * @link https://www.rfc-editor.org/rfc/rfc8288 Web Linking (RFC 8288)
     */
    protected function formatLink(LinkInterface $link): string
    {
        $parts = ['<' . $link->getHref() . '>'];

        $rels = $link->getRels();
        if ($rels) {
            $parts[] = 'rel="' . implode(' ', $rels) . '"';
        }

        foreach ($link->getAttributes() as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $parts[] = $key;
                }
                continue;
            }

            if (is_array($value)) {
                foreach ($value as $v) {
                    $parts[] = $key . '="' . $this->escapeHeaderValue((string)$v) . '"';
                }
                continue;
            }

            $parts[] = $key . '="' . $this->escapeHeaderValue((string)$value) . '"';
        }

        return implode('; ', $parts);
    }

    /**
     * Escape a header value for use in a quoted string.
     *
     * @param string $value The value to escape.
     * @return string The escaped value.
     */
    protected function escapeHeaderValue(string $value): string
    {
        return str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
    }

    /**
     * Emit cookies using setcookie()
     *
     * @param array<\Cake\Http\Cookie\CookieInterface|string> $cookies An array of cookies.
     * @return void
     */
    protected function emitCookies(array $cookies): void
    {
        foreach ($cookies as $cookie) {
            $this->setCookie($cookie);
        }
    }

    /**
     * Helper methods to set cookie.
     *
     * @param \Cake\Http\Cookie\CookieInterface|string $cookie Cookie.
     * @return bool
     */
    protected function setCookie(CookieInterface|string $cookie): bool
    {
        if (is_string($cookie)) {
            $cookie = Cookie::createFromHeaderString($cookie, ['path' => '']);
        }

        return setcookie($cookie->getName(), $cookie->getScalarValue(), $cookie->getOptions());
    }

    /**
     * Parse content-range header
     * https://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.16
     *
     * @param string $header The Content-Range header to parse.
     * @return array|false [unit, first, last, length]; returns false if no
     *     content range or an invalid content range is provided
     */
    protected function parseContentRange(string $header): array|false
    {
        if (preg_match('/(?P<unit>[\w]+)\s+(?P<first>\d+)-(?P<last>\d+)\/(?P<length>\d+|\*)/', $header, $matches)) {
            return [
                $matches['unit'],
                (int)$matches['first'],
                (int)$matches['last'],
                $matches['length'] === '*' ? '*' : (int)$matches['length'],
            ];
        }

        return false;
    }
}
