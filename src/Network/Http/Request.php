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

use Cake\Core\Exception\Exception;

/**
 * Implements methods for HTTP requests.
 *
 * Used by Cake\Network\Http\Client to contain request information
 * for making requests.
 */
class Request extends Message
{

    /**
     * The HTTP method to use.
     *
     * @var string
     */
    protected $_method = self::METHOD_GET;

    /**
     * Request body to send.
     *
     * @var mixed
     */
    protected $_body;

    /**
     * The URL to request.
     *
     * @var string
     */
    protected $_url;

    /**
     * Headers to be sent.
     *
     * @var array
     */
    protected $_headers = [
        'Connection' => 'close',
        'User-Agent' => 'CakePHP'
    ];

    /**
     * Gets/Sets the HTTP method.
     *
     * @param string|null $method The method for the request.
     * @return $this|string Either this or the current method.
     * @throws \Cake\Core\Exception\Exception On invalid methods.
     */
    public function method($method = null)
    {
        if (func_num_args() === 0) {
            return $this->_method;
        }
        $name = get_called_class() . '::METHOD_' . strtoupper($method);
        if (!defined($name)) {
            throw new Exception('Invalid method type');
        }
        $this->_method = $method;
        return $this;
    }

    /**
     * Gets/Sets the url for the request.
     *
     * @param string|null $url The url for the request. Leave null for get
     * @return $this|string Either $this or the url value.
     */
    public function url($url = null)
    {
        if (func_num_args() === 0) {
            return $this->_url;
        }
        $this->_url = $url;
        return $this;
    }

    /**
     * Gets/Sets headers into the request.
     *
     * You can get the value of a header, or set one/many headers.
     * Headers are set / fetched in a case insensitive way.
     *
     * ### Getting one header
     *
     * ```
     * $request->header('Content-Type');
     * ```
     *
     * ### Getting all headers
     *
     * ```
     * $request->header();
     * ```
     *
     * ### Setting one header
     *
     * ```
     * $request->header('Content-Type', 'application/json');
     * ```
     *
     * ### Setting multiple headers
     *
     * ```
     * $request->header(['Connection' => 'close', 'User-Agent' => 'CakePHP']);
     * ```
     *
     * @param string|array|null $name The name to get, or array of multiple values to set.
     * @param string|null $value The value to set for the header.
     * @return mixed Either $this when setting or header value when getting.
     */
    public function header($name = null, $value = null)
    {
        if (func_num_args() === 0) {
            return $this->_headers;
        }
        if (func_num_args() === 1 && is_string($name)) {
            $name = $this->_normalizeHeader($name);
            return isset($this->_headers[$name]) ? $this->_headers[$name] : null;
        }
        if (func_num_args() === 2 && !is_array($name)) {
            $name = [$name => $value];
        }
        foreach ($name as $key => $val) {
            $key = $this->_normalizeHeader($key);
            $this->_headers[$key] = $val;
        }
        return $this;
    }

    /**
     * Gets/Sets cookie values.
     *
     * ### Getting a cookie
     *
     * ```
     * $request->cookie('session');
     * ```
     *
     * ### Setting one cookie
     *
     * ```
     * $request->cookie('session', '123456');
     * ```
     *
     * ### Setting multiple headers
     *
     * ```
     * $request->cookie(['test' => 'value', 'split' => 'banana']);
     * ```
     *
     * @param string $name The name of the cookie to Gets/Sets
     * @param string|null $value Either the value or null when getting values.
     * @return mixed Either $this or the cookie value.
     */
    public function cookie($name, $value = null)
    {
        if (func_num_args() === 1 && is_string($name)) {
            return isset($this->_cookies[$name]) ? $this->_cookies[$name] : null;
        }
        if (is_string($name) && is_string($value)) {
            $name = [$name => $value];
        }
        foreach ($name as $key => $val) {
            $this->_cookies[$key] = $val;
        }
        return $this;
    }

    /**
     * Gets/Sets HTTP version.
     *
     * @param string|null $version The HTTP version.
     *
     * @return $this|string Either $this or the HTTP version.
     */
    public function version($version = null)
    {
        if ($version === null) {
            return parent::version();
        }

        $this->_version = $version;
        return $this;
    }
}
