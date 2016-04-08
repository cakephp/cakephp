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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Network\Http;

use RuntimeException;

/**
 * Implements methods for HTTP responses.
 *
 * All of the following examples assume that `$response` is an
 * instance of this class.
 *
 * ### Get header values
 *
 * Header names are case-insensitive, but normalized to Title-Case
 * when the response is parsed.
 *
 * ```
 * $val = $response->header('content-type');
 * ```
 *
 * Will read the Content-Type header. You can get all set
 * headers using:
 *
 * ```
 * $response->header();
 * ```
 *
 * You can also get at the headers using object access. When getting
 * headers with object access, you have to use case-sensitive header
 * names:
 *
 * ```
 * $val = $response->headers['Content-Type'];
 * ```
 *
 * ### Get the response body
 *
 * You can access the response body using:
 *
 * ```
 * $content = $response->body();
 * ```
 *
 * You can also use object access:
 *
 * ```
 * $content = $response->body;
 * ```
 *
 * If your response body is in XML or JSON you can use
 * special content type specific accessors to read the decoded data.
 * JSON data will be returned as arrays, while XML data will be returned
 * as SimpleXML nodes:
 *
 * ```
 * // Get as xml
 * $content = $response->xml
 * // Get as json
 * $content = $response->json
 * ```
 *
 * If the response cannot be decoded, null will be returned.
 *
 * ### Check the status code
 *
 * You can access the response status code using:
 *
 * ```
 * $content = $response->statusCode();
 * ```
 *
 * You can also use object access:
 *
 * ```
 * $content = $response->code;
 * ```
 */
class Response extends Message
{

    /**
     * The status code of the response.
     *
     * @var int
     */
    protected $_code;

    /**
     * The response body
     *
     * @var string
     */
    protected $_body;

    /**
     * Cached decoded XML data.
     *
     * @var \SimpleXMLElement
     */
    protected $_xml;

    /**
     * Cached decoded JSON data.
     *
     * @var array
     */
    protected $_json;

    /**
     * Map of public => property names for __get()
     *
     * @var array
     */
    protected $_exposedProperties = [
        'cookies' => '_cookies',
        'headers' => '_headers',
        'body' => '_body',
        'code' => '_code',
        'json' => '_getJson',
        'xml' => '_getXml'
    ];

    /**
     * Constructor
     *
     * @param array $headers Unparsed headers.
     * @param string $body The response body.
     */
    public function __construct($headers = [], $body = '')
    {
        $this->_parseHeaders($headers);
        if ($this->header('Content-Encoding') === 'gzip') {
            $body = $this->_decodeGzipBody($body);
        }
        $this->_body = $body;
    }

    /**
     * Uncompress a gzip response.
     *
     * Looks for gzip signatures, and if gzinflate() exists,
     * the body will be decompressed.
     *
     * @param string $body Gzip encoded body.
     * @return string
     * @throws \RuntimeException When attempting to decode gzip content without gzinflate.
     */
    protected function _decodeGzipBody($body)
    {
        if (!function_exists('gzinflate')) {
            throw new RuntimeException('Cannot decompress gzip response body without gzinflate()');
        }
        $offset = 0;
        // Look for gzip 'signature'
        if (substr($body, 0, 2) === "\x1f\x8b") {
            $offset = 2;
        }
        // Check the format byte
        if (substr($body, $offset, 1) === "\x08") {
            return gzinflate(substr($body, $offset + 8));
        }
    }

    /**
     * Parses headers if necessary.
     *
     * - Decodes the status code.
     * - Parses and normalizes header names + values.
     *
     * @param array $headers Headers to parse.
     * @return void
     */
    protected function _parseHeaders($headers)
    {
        foreach ($headers as $key => $value) {
            if (substr($value, 0, 5) === 'HTTP/') {
                preg_match('/HTTP\/([\d.]+) ([0-9]+)/i', $value, $matches);
                $this->_version = $matches[1];
                $this->_code = $matches[2];
                continue;
            }
            list($name, $value) = explode(':', $value, 2);
            $value = trim($value);
            $name = $this->_normalizeHeader($name);
            if ($name === 'Set-Cookie') {
                $this->_parseCookie($value);
            }
            if (isset($this->_headers[$name])) {
                $this->_headers[$name] = (array)$this->_headers[$name];
                $this->_headers[$name][] = $value;
            } else {
                $this->_headers[$name] = $value;
            }
        }
    }

    /**
     * Parse a cookie header into data.
     *
     * @param string $value The cookie value to parse.
     * @return void
     */
    protected function _parseCookie($value)
    {
        $value = rtrim($value, ';');
        $nestedSemi = '";"';
        if (strpos($value, $nestedSemi) !== false) {
            $value = str_replace($nestedSemi, "{__cookie_replace__}", $value);
            $parts = explode(';', $value);
            $parts = str_replace("{__cookie_replace__}", $nestedSemi, $parts);
        } else {
            $parts = preg_split('/\;[ \t]*/', $value);
        }

        $name = false;
        foreach ($parts as $i => $part) {
            if (strpos($part, '=') !== false) {
                list($key, $value) = explode('=', $part, 2);
            } else {
                $key = $part;
                $value = true;
            }
            if ($i === 0) {
                $name = $key;
                $cookie['value'] = $value;
                continue;
            }
            $key = strtolower($key);
            if (!isset($cookie[$key])) {
                $cookie[$key] = $value;
            }
        }
        $cookie['name'] = $name;
        $this->_cookies[$name] = $cookie;
    }

    /**
     * Check if the response was OK
     *
     * @return bool
     */
    public function isOk()
    {
        $codes = [
            static::STATUS_OK,
            static::STATUS_CREATED,
            static::STATUS_ACCEPTED
        ];
        return in_array($this->_code, $codes);
    }

    /**
     * Check if the response had a redirect status code.
     *
     * @return bool
     */
    public function isRedirect()
    {
        $codes = [
            static::STATUS_MOVED_PERMANENTLY,
            static::STATUS_FOUND,
            static::STATUS_SEE_OTHER,
            static::STATUS_TEMPORARY_REDIRECT,
        ];
        return (
            in_array($this->_code, $codes) &&
            $this->header('Location')
        );
    }

    /**
     * Get the status code from the response
     *
     * @return int
     */
    public function statusCode()
    {
        return $this->_code;
    }

    /**
     * Get the encoding if it was set.
     *
     * @return string|null
     */
    public function encoding()
    {
        $content = $this->header('content-type');
        if (!$content) {
            return null;
        }
        preg_match('/charset\s?=\s?[\'"]?([a-z0-9-_]+)[\'"]?/i', $content, $matches);
        if (empty($matches[1])) {
            return null;
        }
        return $matches[1];
    }

    /**
     * Read single/multiple header value(s) out.
     *
     * @param string|null $name The name of the header you want. Leave
     *   null to get all headers.
     * @return mixed Null when the header doesn't exist. An array
     *   will be returned when getting all headers or when getting
     *   a header that had multiple values set. Otherwise a string
     *   will be returned.
     */
    public function header($name = null)
    {
        if ($name === null) {
            return $this->_headers;
        }
        $name = $this->_normalizeHeader($name);
        if (!isset($this->_headers[$name])) {
            return null;
        }
        return $this->_headers[$name];
    }

    /**
     * Read single/multiple cookie values out.
     *
     * @param string|null $name The name of the cookie you want. Leave
     *   null to get all cookies.
     * @param bool $all Get all parts of the cookie. When false only
     *   the value will be returned.
     * @return mixed
     */
    public function cookie($name = null, $all = false)
    {
        if ($name === null) {
            return $this->_cookies;
        }
        if (!isset($this->_cookies[$name])) {
            return null;
        }
        if ($all) {
            return $this->_cookies[$name];
        }
        return $this->_cookies[$name]['value'];
    }

    /**
     * Get the response body.
     *
     * By passing in a $parser callable, you can get the decoded
     * response content back.
     *
     * For example to get the json data as an object:
     *
     * ```
     * $body = $response->body('json_decode');
     * ```
     *
     * @param callable|null $parser The callback to use to decode
     *   the response body.
     * @return mixed The response body.
     */
    public function body($parser = null)
    {
        if ($parser) {
            return $parser($this->_body);
        }
        return $this->_body;
    }

    /**
     * Get the response body as JSON decoded data.
     *
     * @return null|array
     */
    protected function _getJson()
    {
        if (!empty($this->_json)) {
            return $this->_json;
        }
        $data = json_decode($this->_body, true);
        if (is_array($data)) {
            $this->_json = $data;
            return $this->_json;
        }
        return null;
    }

    /**
     * Get the response body as XML decoded data.
     *
     * @return null|\SimpleXMLElement
     */
    protected function _getXml()
    {
        if (!empty($this->_xml)) {
            return $this->_xml;
        }
        libxml_use_internal_errors();
        $data = simplexml_load_string($this->_body);
        if ($data) {
            $this->_xml = $data;
            return $this->_xml;
        }
        return null;
    }

    /**
     * Read values as properties.
     *
     * @param string $name Property name.
     * @return mixed
     */
    public function __get($name)
    {
        if (!isset($this->_exposedProperties[$name])) {
            return false;
        }
        $key = $this->_exposedProperties[$name];
        if (substr($key, 0, 4) === '_get') {
            return $this->{$key}();
        }
        return $this->{$key};
    }

    /**
     * isset/empty test with -> syntax.
     *
     * @param string $name Property name.
     * @return bool
     */
    public function __isset($name)
    {
        if (!isset($this->_exposedProperties[$name])) {
            return false;
        }
        $key = $this->_exposedProperties[$name];
        if (substr($key, 0, 4) === '_get') {
            $val = $this->{$key}();
            return $val !== null;
        }
        return isset($this->$key);
    }
}
