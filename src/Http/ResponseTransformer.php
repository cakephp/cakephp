<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http;

use Cake\Http\Response as CakeResponse;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Zend\Diactoros\Response as DiactorosResponse;
use Zend\Diactoros\Stream;

/**
 * This class converts PSR7 responses into CakePHP ones and back again.
 *
 * By bridging the CakePHP and PSR7 responses together, applications
 * can be embedded as PSR7 middleware in a fully compatible way.
 *
 * @internal
 * @deprecated 3.4.0 No longer used. Will be removed in 4.0.0
 */
class ResponseTransformer
{
    /**
     * Convert a PSR7 Response into a CakePHP one.
     *
     * @param \Psr\Http\Message\ResponseInterface $response The response to convert.
     * @return \Cake\Network\Response The equivalent CakePHP response
     */
    public static function toCake(PsrResponse $response)
    {
        $body = static::getBody($response);
        $data = [
            'status' => $response->getStatusCode(),
            'body' => $body['body'],
        ];
        $cake = new CakeResponse($data);
        if ($body['file']) {
            $cake->file($body['file']);
        }
        $cookies = static::parseCookies($response->getHeader('Set-Cookie'));
        foreach ($cookies as $cookie) {
            $cake->cookie($cookie);
        }
        $headers = static::collapseHeaders($response);
        $cake->header($headers);

        if (!empty($headers['Content-Type'])) {
            $cake->type($headers['Content-Type']);
        }

        return $cake;
    }

    /**
     * Get the response body from a PSR7 Response.
     *
     * @param \Psr\Http\Message\ResponseInterface $response The response to convert.
     * @return array A hash of 'body' and 'file'
     */
    protected static function getBody(PsrResponse $response)
    {
        $stream = $response->getBody();
        if ($stream->getMetadata('wrapper_type') === 'plainfile') {
            return ['body' => '', 'file' => $stream->getMetadata('uri')];
        }
        if ($stream->getSize() === 0) {
            return ['body' => '', 'file' => false];
        }
        $stream->rewind();

        return ['body' => $stream->getContents(), 'file' => false];
    }

    /**
     * Parse the Set-Cookie headers in a PSR7 response
     * into the format CakePHP expects.
     *
     * @param array $cookieHeader A list of Set-Cookie headers.
     * @return array Parsed cookie data.
     */
    protected static function parseCookies(array $cookieHeader)
    {
        $cookies = [];
        foreach ($cookieHeader as $cookie) {
            if (strpos($cookie, '";"') !== false) {
                $cookie = str_replace('";"', "{__cookie_replace__}", $cookie);
                $parts = preg_split('/\;[ \t]*/', $cookie);
                $parts = str_replace("{__cookie_replace__}", '";"', $parts);
            } else {
                $parts = preg_split('/\;[ \t]*/', $cookie);
            }

            list($name, $value) = explode('=', array_shift($parts), 2);
            $parsed = ['name' => $name, 'value' => urldecode($value)];

            foreach ($parts as $part) {
                if (strpos($part, '=') !== false) {
                    list($key, $value) = explode('=', $part);
                } else {
                    $key = $part;
                    $value = true;
                }

                $key = strtolower($key);
                if ($key === 'httponly') {
                    $key = 'httpOnly';
                }
                if ($key === 'expires') {
                    $key = 'expire';
                    $value = strtotime($value);
                }
                if (!isset($parsed[$key])) {
                    $parsed[$key] = $value;
                }
            }
            $cookies[] = $parsed;
        }

        return $cookies;
    }

    /**
     * Convert a PSR7 Response headers into a flat array
     *
     * @param \Psr\Http\Message\ResponseInterface $response The response to convert.
     * @return array Headers.
     */
    protected static function collapseHeaders(PsrResponse $response)
    {
        $out = [];
        foreach ($response->getHeaders() as $name => $value) {
            if (count($value) === 1) {
                $out[$name] = $value[0];
            } else {
                $out[$name] = $value;
            }
        }

        return $out;
    }

    /**
     * Convert a CakePHP response into a PSR7 one.
     *
     * @param \Cake\Network\Response $response The CakePHP response to convert
     * @return \Psr\Http\Message\ResponseInterface $response The equivalent PSR7 response.
     */
    public static function toPsr(CakeResponse $response)
    {
        $status = $response->statusCode();
        $headers = $response->header();
        if (!isset($headers['Content-Type'])) {
            $headers = static::setContentType($headers, $response);
        }
        $cookies = $response->cookie();
        if ($cookies) {
            $headers['Set-Cookie'] = static::buildCookieHeader($cookies);
        }
        $stream = static::getStream($response);

        return new DiactorosResponse($stream, $status, $headers);
    }

    /**
     * Add in the Content-Type header if necessary.
     *
     * @param array $headers The headers to update
     * @param \Cake\Network\Response $response The CakePHP response to convert
     * @return array The updated headers.
     */
    protected static function setContentType($headers, $response)
    {
        if (isset($headers['Content-Type'])) {
            return $headers;
        }
        if (in_array($response->statusCode(), [204, 304])) {
            return $headers;
        }

        $whitelist = [
            'application/javascript', 'application/json', 'application/xml', 'application/rss+xml'
        ];

        $type = $response->type();
        $charset = $response->charset();

        $hasCharset = false;
        if ($charset && (strpos($type, 'text/') === 0 || in_array($type, $whitelist))) {
            $hasCharset = true;
        }

        $value = $type;
        if ($hasCharset) {
            $value = "{$type}; charset={$charset}";
        }
        $headers['Content-Type'] = $value;

        return $headers;
    }

    /**
     * Convert an array of cookies into header lines.
     *
     * @param array $cookies The cookies to serialize.
     * @return array A list of cookie header values.
     */
    protected static function buildCookieHeader($cookies)
    {
        $headers = [];
        foreach ($cookies as $cookie) {
            $parts = [
                sprintf('%s=%s', urlencode($cookie['name']), urlencode($cookie['value']))
            ];
            if ($cookie['expire']) {
                $cookie['expire'] = gmdate('D, d M Y H:i:s T', $cookie['expire']);
            }
            $attributes = [
                'expire' => 'Expires=%s',
                'path' => 'Path=%s',
                'domain' => 'Domain=%s',
                'httpOnly' => 'HttpOnly',
                'secure' => 'Secure',
            ];
            foreach ($attributes as $key => $attr) {
                if ($cookie[$key]) {
                    $parts[] = sprintf($attr, $cookie[$key]);
                }
            }
            $headers[] = implode('; ', $parts);
        }

        return $headers;
    }

    /**
     * Get the stream for the new response.
     *
     * @param \Cake\Network\Response $response The cake response to extract the body from.
     * @return \Psr\Http\Message\StreamInterface|string The stream.
     */
    protected static function getStream($response)
    {
        $stream = 'php://memory';
        $body = $response->body();
        if (is_string($body) && strlen($body)) {
            $stream = new Stream('php://memory', 'wb');
            $stream->write($body);

            return $stream;
        }
        if (is_callable($body)) {
            $stream = new CallbackStream($body);

            return $stream;
        }
        $file = $response->getFile();
        if ($file) {
            $stream = new Stream($file->path, 'rb');

            return $stream;
        }

        return $stream;
    }
}
