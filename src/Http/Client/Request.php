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

use Cake\Core\Exception\Exception;
use Psr\Http\Message\RequestInterface;
use Zend\Diactoros\MessageTrait;
use Zend\Diactoros\RequestTrait;
use Zend\Diactoros\Stream;

/**
 * Implements methods for HTTP requests.
 *
 * Used by Cake\Network\Http\Client to contain request information
 * for making requests.
 */
class Request extends Message implements RequestInterface
{
    use MessageTrait;
    use RequestTrait;

    /**
     * Constructor
     *
     * Provides backwards compatible defaults for some properties.
     *
     * @param string $url The request URL
     * @param string $method The HTTP method to use.
     * @param array $headers The HTTP headers to set.
     * @param array|string $data The request body to use.
     */
    public function __construct($url = '', $method = self::METHOD_GET, array $headers = [], $data = null)
    {
        $this->validateMethod($method);
        $this->method = $method;
        $this->uri = $this->createUri($url);
        $headers += [
            'Connection' => 'close',
            'User-Agent' => 'CakePHP'
        ];
        $this->addHeaders($headers);
        $this->body($data);
    }

    /**
     * Get/Set the HTTP method.
     *
     * *Warning* This method mutates the request in-place for backwards
     * compatibility reasons, and is not part of the PSR7 interface.
     *
     * @param string|null $method The method for the request.
     * @return $this|string Either this or the current method.
     * @throws \Cake\Core\Exception\Exception On invalid methods.
     * @deprecated 3.3.0 Use getMethod() and withMethod() instead.
     */
    public function method($method = null)
    {
        if ($method === null) {
            return $this->method;
        }
        $name = get_called_class() . '::METHOD_' . strtoupper($method);
        if (!defined($name)) {
            throw new Exception('Invalid method type');
        }
        $this->method = $method;
        return $this;
    }

    /**
     * Get/Set the url for the request.
     *
     * *Warning* This method mutates the request in-place for backwards
     * compatibility reasons, and is not part of the PSR7 interface.
     *
     * @param string|null $url The url for the request. Leave null for get
     * @return $this|string Either $this or the url value.
     * @deprecated 3.3.0 Use getUri() and withUri() instead.
     */
    public function url($url = null)
    {
        if ($url === null) {
            return '' . $this->getUri();
        }
        $this->uri = $this->createUri($url);
        return $this;
    }

    /**
     * Get/Set headers into the request.
     *
     * You can get the value of a header, or set one/many headers.
     * Headers are set / fetched in a case insensitive way.
     *
     * ### Getting headers
     *
     * ```
     * $request->header('Content-Type');
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
     * *Warning* This method mutates the request in-place for backwards
     * compatibility reasons, and is not part of the PSR7 interface.
     *
     * @param string|array|null $name The name to get, or array of multiple values to set.
     * @param string|null $value The value to set for the header.
     * @return mixed Either $this when setting or header value when getting.
     * @deprecated 3.3.0 Use withHeader() and getHeaderLine() instead.
     */
    public function header($name = null, $value = null)
    {
        if ($value === null && is_string($name)) {
            $val = $this->getHeaderLine($name);
            if ($val === '') {
                return null;
            }
            return $val;
        }

        if ($value !== null && !is_array($name)) {
            $name = [$name => $value];
        }
        $this->addHeaders($name);
        return $this;
    }

    /**
     * Add an array of headers to the request.
     *
     * @param array $headers The headers to add.
     * @return void
     */
    protected function addHeaders($headers)
    {
        foreach ($headers as $key => $val) {
            $normalized = strtolower($key);
            $this->headers[$key] = (array)$val;
            $this->headerNames[$normalized] = $key;
        }
    }

    /**
     * Get/Set cookie values.
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
     * @param string $name The name of the cookie to get/set
     * @param string|null $value Either the value or null when getting values.
     * @return mixed Either $this or the cookie value.
     */
    public function cookie($name, $value = null)
    {
        if ($value === null && is_string($name)) {
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
     * Get/Set HTTP version.
     *
     * *Warning* This method mutates the request in-place for backwards
     * compatibility reasons, and is not part of the PSR7 interface.
     *
     * @param string|null $version The HTTP version.
     * @return $this|string Either $this or the HTTP version.
     * @deprecated 3.3.0 Use getProtocolVersion() and withProtocolVersion() instead.
     */
    public function version($version = null)
    {
        if ($version === null) {
            return $this->protocol;
        }

        $this->protocol = $version;
        return $this;
    }

    /**
     * Get/set the body/payload for the message.
     *
     * Array data will be serialized with Cake\Http\FormData,
     * and the content-type will be set.
     *
     * @param string|array|null $body The body for the request. Leave null for get
     * @return mixed Either $this or the body value.
     */
    public function body($body = null)
    {
        if ($body === null) {
            $body = $this->getBody();
            return $body ? $body->__toString() : '';
        }
        if (is_array($body)) {
            $formData = new FormData();
            $formData->addMany($body);
            $this->header('Content-Type', $formData->contentType());
            $body = (string)$formData;
        }
        $stream = new Stream('php://memory', 'rw');
        $stream->write($body);
        $this->stream = $stream;
        return $this;
    }
}
