<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Client;

// This alias is necessary to avoid class name conflicts
// with the deprecated class in this namespace.
use Cake\Http\Cookie\CookieCollection as CookiesCollection;
use Cake\Http\Cookie\CookieInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Zend\Diactoros\MessageTrait;
use Zend\Diactoros\Stream;

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
 * $val = $response->getHeaderLine('content-type');
 * ```
 *
 * Will read the Content-Type header. You can get all set
 * headers using:
 *
 * ```
 * $response->getHeaders();
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
 * You can access the response body stream using:
 *
 * ```
 * $content = $response->getBody();
 * ```
 *
 * You can also use object access to get the string version
 * of the response body:
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
 * $content = $response->getStatusCode();
 * ```
 *
 * You can also use object access:
 *
 * ```
 * $content = $response->code;
 * ```
 */
class Response extends Message implements ResponseInterface
{
    use MessageTrait;

    /**
     * The status code of the response.
     *
     * @var int
     */
    protected $code;

    /**
     * Cookie Collection instance
     *
     * @var \Cake\Http\Cookie\CookieCollection
     */
    protected $cookies;

    /**
     * The reason phrase for the status code
     *
     * @var string
     */
    protected $reasonPhrase;

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
        'cookies' => '_getCookies',
        'body' => '_getBody',
        'code' => 'code',
        'json' => '_getJson',
        'xml' => '_getXml',
        'headers' => '_getHeaders',
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
        if ($this->getHeaderLine('Content-Encoding') === 'gzip') {
            $body = $this->_decodeGzipBody($body);
        }
        $stream = new Stream('php://memory', 'wb+');
        $stream->write($body);
        $stream->rewind();
        $this->stream = $stream;
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
     * - Decodes the status code and reasonphrase.
     * - Parses and normalizes header names + values.
     *
     * @param array $headers Headers to parse.
     * @return void
     */
    protected function _parseHeaders($headers)
    {
        foreach ($headers as $key => $value) {
            if (substr($value, 0, 5) === 'HTTP/') {
                preg_match('/HTTP\/([\d.]+) ([0-9]+)(.*)/i', $value, $matches);
                $this->protocol = $matches[1];
                $this->code = (int)$matches[2];
                $this->reasonPhrase = trim($matches[3]);
                continue;
            }
            list($name, $value) = explode(':', $value, 2);
            $value = trim($value);
            $name = trim($name);

            $normalized = strtolower($name);

            if (isset($this->headers[$name])) {
                $this->headers[$name][] = $value;
            } else {
                $this->headers[$name] = (array)$value;
                $this->headerNames[$normalized] = $name;
            }
        }
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

        return in_array($this->code, $codes);
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
            in_array($this->code, $codes) &&
            $this->getHeaderLine('Location')
        );
    }

    /**
     * Get the status code from the response
     *
     * @return int
     * @deprecated 3.3.0 Use getStatusCode() instead.
     */
    public function statusCode()
    {
        return $this->code;
    }

    /**
     * {@inheritdoc}
     *
     * @return int The status code.
     */
    public function getStatusCode()
    {
        return $this->code;
    }

    /**
     * {@inheritdoc}
     *
     * @param int $code The status code to set.
     * @param string $reasonPhrase The status reason phrase.
     * @return $this A copy of the current object with an updated status code.
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        $new = clone $this;
        $new->code = $code;
        $new->reasonPhrase = $reasonPhrase;

        return $new;
    }

    /**
     * {@inheritdoc}
     *
     * @return string The current reason phrase.
     */
    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }

    /**
     * Get the encoding if it was set.
     *
     * @return string|null
     * @deprecated 3.3.0 Use getEncoding() instead.
     */
    public function encoding()
    {
        return $this->getEncoding();
    }

    /**
     * Get the encoding if it was set.
     *
     * @return string|null
     */
    public function getEncoding()
    {
        $content = $this->getHeaderLine('content-type');
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
     * @deprecated 3.3.0 Use getHeader() and getHeaderLine() instead.
     */
    public function header($name = null)
    {
        if ($name === null) {
            return $this->_getHeaders();
        }
        $header = $this->getHeader($name);
        if (count($header) === 1) {
            return $header[0];
        }

        return $header;
    }

    /**
     * Read single/multiple cookie values out.
     *
     * *Note* This method will only provide access to cookies that
     * were added as part of the constructor. If cookies are added post
     * construction they will not be accessible via this method.
     *
     * @param string|null $name The name of the cookie you want. Leave
     *   null to get all cookies.
     * @param bool $all Get all parts of the cookie. When false only
     *   the value will be returned.
     * @return mixed
     * @deprecated 3.3.0 Use getCookie(), getCookieData() or getCookies() instead.
     */
    public function cookie($name = null, $all = false)
    {
        if ($name === null) {
            return $this->getCookies();
        }
        if ($all) {
            return $this->getCookieData($name);
        }

        return $this->getCookie($name);
    }

    /**
     * Get the all cookie data.
     *
     * @return array The cookie data
     */
    public function getCookies()
    {
        return $this->_getCookies();
    }

    /**
     * Get the cookie collection from this response.
     *
     * This method exposes the response's CookieCollection
     * instance allowing you to interact with cookie objects directly.
     *
     * @return \Cake\Http\Cookie\CookieCollection
     */
    public function getCookieCollection()
    {
        $this->buildCookieCollection();

        return $this->cookies;
    }

    /**
     * Get the value of a single cookie.
     *
     * @param string $name The name of the cookie value.
     * @return string|null Either the cookie's value or null when the cookie is undefined.
     */
    public function getCookie($name)
    {
        $this->buildCookieCollection();
        if (!$this->cookies->has($name)) {
            return null;
        }

        return $this->cookies->get($name)->getValue();
    }

    /**
     * Get the full data for a single cookie.
     *
     * @param string $name The name of the cookie value.
     * @return array|null Either the cookie's data or null when the cookie is undefined.
     */
    public function getCookieData($name)
    {
        $this->buildCookieCollection();

        if (!$this->cookies->has($name)) {
            return null;
        }

        $cookie = $this->cookies->get($name);

        return $this->convertCookieToArray($cookie);
    }

    /**
     * Convert the cookie into an array of its properties.
     *
     * This method is compatible with older client code that
     * expects date strings instead of timestamps.
     *
     * @param \Cake\Http\Cookie\CookieInterface $cookie Cookie object.
     * @return array
     */
    protected function convertCookieToArray(CookieInterface $cookie)
    {
        return [
            'name' => $cookie->getName(),
            'value' => $cookie->getValue(),
            'path' => $cookie->getPath(),
            'domain' => $cookie->getDomain(),
            'secure' => $cookie->isSecure(),
            'httponly' => $cookie->isHttpOnly(),
            'expires' => $cookie->getFormattedExpires()
        ];
    }

    /**
     * Lazily build the CookieCollection and cookie objects from the response header
     *
     * @return void
     */
    protected function buildCookieCollection()
    {
        if ($this->cookies) {
            return;
        }
        $this->cookies = CookiesCollection::createFromHeader($this->getHeader('Set-Cookie'));
    }

    /**
     * Property accessor for `$this->cookies`
     *
     * @return array Array of Cookie data.
     */
    protected function _getCookies()
    {
        $this->buildCookieCollection();

        $cookies = [];
        foreach ($this->cookies as $cookie) {
            $cookies[$cookie->getName()] = $this->convertCookieToArray($cookie);
        }

        return $cookies;
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
        $stream = $this->stream;
        $stream->rewind();
        if ($parser) {
            return $parser($stream->getContents());
        }

        return $stream->getContents();
    }

    /**
     * Get the response body as JSON decoded data.
     *
     * @return array|null
     */
    protected function _getJson()
    {
        if ($this->_json) {
            return $this->_json;
        }

        return $this->_json = json_decode($this->_getBody(), true);
    }

    /**
     * Get the response body as XML decoded data.
     *
     * @return null|\SimpleXMLElement
     */
    protected function _getXml()
    {
        if ($this->_xml) {
            return $this->_xml;
        }
        libxml_use_internal_errors();
        $data = simplexml_load_string($this->_getBody());
        if ($data) {
            $this->_xml = $data;

            return $this->_xml;
        }

        return null;
    }

    /**
     * Provides magic __get() support.
     *
     * @return array
     */
    protected function _getHeaders()
    {
        $out = [];
        foreach ($this->headers as $key => $values) {
            $out[$key] = implode(',', $values);
        }

        return $out;
    }

    /**
     * Provides magic __get() support.
     *
     * @return array
     */
    protected function _getBody()
    {
        $this->stream->rewind();

        return $this->stream->getContents();
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

        return isset($this->{$key});
    }
}

// @deprecated Add backwards compat alias.
class_alias('Cake\Http\Client\Response', 'Cake\Network\Http\Response');
