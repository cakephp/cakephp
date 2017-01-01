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
 * @since         3.3.5
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * Parts of this file are derived from Zend-Diactoros
 *
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */
namespace Cake\Http;

use Cake\Core\Configure;
use Cake\Log\Log;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\RelativeStream;
use Zend\Diactoros\Response\EmitterInterface;

/**
 * Emits a Response to the PHP Server API.
 *
 * This emitter offers a few changes from the emitters offered by
 * diactoros:
 *
 * - It logs headers sent using CakePHP's logging tools.
 * - Cookies are emitted using setcookie() to not conflict with ext/session
 */
class ResponseEmitter implements EmitterInterface
{
    /**
     * {@inheritDoc}
     */
    public function emit(ResponseInterface $response, $maxBufferLength = 8192)
    {
        $file = $line = null;
        if (headers_sent($file, $line)) {
            $message = "Unable to emit headers. Headers sent in file=$file line=$line";
            if (Configure::read('debug')) {
                trigger_error($message, E_USER_WARNING);
            } else {
                Log::warning($message);
            }
        }

        $this->emitStatusLine($response);
        $this->emitHeaders($response);
        $this->flush();

        $range = $this->parseContentRange($response->getHeaderLine('Content-Range'));
        if (is_array($range)) {
            $this->emitBodyRange($range, $response, $maxBufferLength);
        } else {
            $this->emitBody($response, $maxBufferLength);
        }

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }

    /**
     * Emit the message body.
     *
     * @param \Psr\Http\Message\ResponseInterface $response The response to emit
     * @param int $maxBufferLength The chunk size to emit
     * @return void
     */
    protected function emitBody(ResponseInterface $response, $maxBufferLength)
    {
        if (in_array($response->getStatusCode(), [204, 304])) {
            return;
        }
        $body = $response->getBody();

        if (!$body->isSeekable()) {
            echo $body;

            return;
        }

        $body->rewind();
        while (!$body->eof()) {
            echo $body->read($maxBufferLength);
        }
    }

    /**
     * Emit a range of the message body.
     *
     * @param array $range The range data to emit
     * @param \Psr\Http\Message\ResponseInterface $response The response to emit
     * @param int $maxBufferLength The chunk size to emit
     * @return void
     */
    protected function emitBodyRange(array $range, ResponseInterface $response, $maxBufferLength)
    {
        list($unit, $first, $last, $length) = $range;

        $body = $response->getBody();

        if (!$body->isSeekable()) {
            $contents = $body->getContents();
            echo substr($contents, $first, $last - $first + 1);

            return;
        }

        $body = new RelativeStream($body, $first);
        $body->rewind();
        $pos = 0;
        $length = $last - $first + 1;
        while (!$body->eof() && $pos < $length) {
            if (($pos + $maxBufferLength) > $length) {
                echo $body->read($length - $pos);
                break;
            }

            echo $body->read($maxBufferLength);
            $pos = $body->tell();
        }
    }

    /**
     * Emit the status line.
     *
     * Emits the status line using the protocol version and status code from
     * the response; if a reason phrase is availble, it, too, is emitted.
     *
     * @param \Psr\Http\Message\ResponseInterface $response The response to emit
     * @return void
     */
    protected function emitStatusLine(ResponseInterface $response)
    {
        $reasonPhrase = $response->getReasonPhrase();
        header(sprintf(
            'HTTP/%s %d%s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            ($reasonPhrase ? ' ' . $reasonPhrase : '')
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
    protected function emitHeaders(ResponseInterface $response)
    {
        $cookies = [];
        if (method_exists($response, 'getCookies')) {
            $cookies = $response->getCookies();
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
                    $value
                ), $first);
                $first = false;
            }
        }

        $this->emitCookies($cookies);
    }

    /**
     * Emit cookies using setcookie()
     *
     * @param array $cookies An array of Set-Cookie headers.
     * @return void
     */
    protected function emitCookies(array $cookies)
    {
        foreach ($cookies as $cookie) {
            if (is_array($cookie)) {
                setcookie(
                    $cookie['name'],
                    $cookie['value'],
                    $cookie['expire'],
                    $cookie['path'],
                    $cookie['domain'],
                    $cookie['secure'],
                    $cookie['httpOnly']
                );
                continue;
            }

            if (strpos($cookie, '";"') !== false) {
                $cookie = str_replace('";"', "{__cookie_replace__}", $cookie);
                $parts = str_replace("{__cookie_replace__}", '";"', explode(';', $cookie));
            } else {
                $parts = preg_split('/\;[ \t]*/', $cookie);
            }

            list($name, $value) = explode('=', array_shift($parts), 2);
            $data = [
                'name' => urldecode($name),
                'value' => urldecode($value),
                'expires' => 0,
                'path' => '',
                'domain' => '',
                'secure' => false,
                'httponly' => false
            ];

            foreach ($parts as $part) {
                if (strpos($part, '=') !== false) {
                    list($key, $value) = explode('=', $part);
                } else {
                    $key = $part;
                    $value = true;
                }

                $key = strtolower($key);
                $data[$key] = $value;
            }
            if (!empty($data['expires'])) {
                $data['expires'] = strtotime($data['expires']);
            }
            setcookie(
                $data['name'],
                $data['value'],
                $data['expires'],
                $data['path'],
                $data['domain'],
                $data['secure'],
                $data['httponly']
            );
        }
    }

    /**
     * Loops through the output buffer, flushing each, before emitting
     * the response.
     *
     * @param int|null $maxBufferLevel Flush up to this buffer level.
     * @return void
     */
    protected function flush($maxBufferLevel = null)
    {
        if (null === $maxBufferLevel) {
            $maxBufferLevel = ob_get_level();
        }

        while (ob_get_level() > $maxBufferLevel) {
            ob_end_flush();
        }
    }

    /**
     * Parse content-range header
     * http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.16
     *
     * @param string $header The Content-Range header to parse.
     * @return false|array [unit, first, last, length]; returns false if no
     *     content range or an invalid content range is provided
     */
    protected function parseContentRange($header)
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
