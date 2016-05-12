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
namespace Cake\Http\Client;

/**
 * Base class for other HTTP requests/responses
 *
 * Defines some common helper methods, constants
 * and properties.
 */
class Message
{

    /**
     * HTTP 200 code
     *
     * @var int
     */
    const STATUS_OK = 200;

    /**
     * HTTP 201 code
     *
     * @var int
     */
    const STATUS_CREATED = 201;

    /**
     * HTTP 202 code
     *
     * @var int
     */
    const STATUS_ACCEPTED = 202;

    /**
     * HTTP 301 code
     *
     * @var int
     */
    const STATUS_MOVED_PERMANENTLY = 301;

    /**
     * HTTP 302 code
     *
     * @var int
     */
    const STATUS_FOUND = 302;

    /**
     * HTTP 303 code
     *
     * @var int
     */
    const STATUS_SEE_OTHER = 303;

    /**
     * HTTP 307 code
     *
     * @var int
     */
    const STATUS_TEMPORARY_REDIRECT = 307;

    /**
     * HTTP GET method
     *
     * @var string
     */
    const METHOD_GET = 'GET';

    /**
     * HTTP POST method
     *
     * @var string
     */
    const METHOD_POST = 'POST';

    /**
     * HTTP PUT method
     *
     * @var string
     */
    const METHOD_PUT = 'PUT';

    /**
     * HTTP DELETE method
     *
     * @var string
     */
    const METHOD_DELETE = 'DELETE';

    /**
     * HTTP PATCH method
     *
     * @var string
     */
    const METHOD_PATCH = 'PATCH';

    /**
     * HTTP OPTIONS method
     *
     * @var string
     */
    const METHOD_OPTIONS = 'OPTIONS';

    /**
     * HTTP TRACE method
     *
     * @var string
     */
    const METHOD_TRACE = 'TRACE';

    /**
     * HTTP HEAD method
     *
     * @var string
     */
    const METHOD_HEAD = 'HEAD';

    /**
     * The array of headers in the response.
     *
     * @var array
     */
    protected $_headers = [];

    /**
     * The array of cookies in the response.
     *
     * @var array
     */
    protected $_cookies = [];

    /**
     * Normalize header names to Camel-Case form.
     *
     * @param string $name The header name to normalize.
     * @return string Normalized header name.
     */
    protected function _normalizeHeader($name)
    {
        $parts = explode('-', trim($name));
        $parts = array_map('strtolower', $parts);
        $parts = array_map('ucfirst', $parts);
        return implode('-', $parts);
    }

    /**
     * Get all headers
     *
     * @return array
     * @deprecated 3.3.0 Use getHeaders() instead.
     */
    public function headers()
    {
        return $this->_headers;
    }

    /**
     * Get all cookies
     *
     * @return array
     */
    public function cookies()
    {
        return $this->_cookies;
    }

    /**
     * Get the HTTP version used.
     *
     * @return string
     * @deprecated 3.3.0 Use getProtocolVersion()
     */
    public function version()
    {
        return $this->protocol;
    }

    /**
     * Get/set the body for the message.
     *
     * @param string|null $body The body for the request. Leave null for get
     * @return mixed Either $this or the body value.
     */
    public function body($body = null)
    {
        if ($body === null) {
            return $this->_body;
        }
        $this->_body = $body;
        return $this;
    }
}
