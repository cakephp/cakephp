<?php
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
 * @since         3.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Network;

use Cake\Http\Response as HttpResponse;

/**
 * A builder object that assists in defining Cross Origin Request related
 * headers.
 *
 * Each of the methods in this object provide a fluent interface. Once you've
 * set all the headers you want to use, the `build()` method can be used to return
 * a modified Response.
 *
 * It is most convenient to get this object via `Request::cors()`.
 *
 * @see \Cake\Http\Response::cors()
 */
class CorsBuilder
{

    /**
     * The response object this builder is attached to.
     *
     * @var \Cake\Http\Response
     */
    protected $_response;

    /**
     * The request's Origin header value
     *
     * @var string
     */
    protected $_origin;

    /**
     * Whether or not the request was over SSL.
     *
     * @var bool
     */
    protected $_isSsl;

    /**
     * The headers that have been queued so far.
     *
     * @var array
     */
    protected $_headers = [];

    /**
     * Constructor.
     *
     * @param \Cake\Http\Response $response The response object to add headers onto.
     * @param string $origin The request's Origin header.
     * @param bool $isSsl Whether or not the request was over SSL.
     */
    public function __construct(HttpResponse $response, $origin, $isSsl = false)
    {
        $this->_origin = $origin;
        $this->_isSsl = $isSsl;
        $this->_response = $response;
    }

    /**
     * Apply the queued headers to the response.
     *
     * If the builder has no Origin, or if there are no allowed domains,
     * or if the allowed domains do not match the Origin header no headers will be applied.
     *
     * @return \Cake\Http\Response
     */
    public function build()
    {
        if (empty($this->_origin)) {
            return $this->_response;
        }
        if (isset($this->_headers['Access-Control-Allow-Origin'])) {
            $this->_response->header($this->_headers);
        }

        return $this->_response;
    }

    /**
     * Set the list of allowed domains.
     *
     * Accepts a string or an array of domains that have CORS enabled.
     * You can use `*.example.com` wildcards to accept subdomains, or `*` to allow all domains
     *
     * @param string|array $domain The allowed domains
     * @return $this
     */
    public function allowOrigin($domain)
    {
        $allowed = $this->_normalizeDomains((array)$domain);
        foreach ($allowed as $domain) {
            if (!preg_match($domain['preg'], $this->_origin)) {
                continue;
            }
            $value = $domain['original'] === '*' ? '*' : $this->_origin;
            $this->_headers['Access-Control-Allow-Origin'] = $value;
            break;
        }

        return $this;
    }

    /**
     * Normalize the origin to regular expressions and put in an array format
     *
     * @param array $domains Domain names to normalize.
     * @return array
     */
    protected function _normalizeDomains($domains)
    {
        $result = [];
        foreach ($domains as $domain) {
            if ($domain === '*') {
                $result[] = ['preg' => '@.@', 'original' => '*'];
                continue;
            }

            $original = $preg = $domain;
            if (strpos($domain, '://') === false) {
                $preg = ($this->_isSsl ? 'https://' : 'http://') . $domain;
            }
            $preg = '@^' . str_replace('\*', '.*', preg_quote($preg, '@')) . '$@';
            $result[] = compact('original', 'preg');
        }

        return $result;
    }

    /**
     * Set the list of allowed HTTP Methods.
     *
     * @param array $methods The allowed HTTP methods
     * @return $this
     */
    public function allowMethods(array $methods)
    {
        $this->_headers['Access-Control-Allow-Methods'] = implode(', ', $methods);

        return $this;
    }

    /**
     * Enable cookies to be sent in CORS requests.
     *
     * @return $this
     */
    public function allowCredentials()
    {
        $this->_headers['Access-Control-Allow-Credentials'] = 'true';

        return $this;
    }

    /**
     * Whitelist headers that can be sent in CORS requests.
     *
     * @param array $headers The list of headers to accept in CORS requests.
     * @return $this
     */
    public function allowHeaders(array $headers)
    {
        $this->_headers['Access-Control-Allow-Headers'] = implode(', ', $headers);

        return $this;
    }

    /**
     * Define the headers a client library/browser can expose to scripting
     *
     * @param array $headers The list of headers to expose CORS responses
     * @return $this
     */
    public function exposeHeaders(array $headers)
    {
        $this->_headers['Access-Control-Expose-Headers'] = implode(', ', $headers);

        return $this;
    }

    /**
     * Define the max-age preflight OPTIONS requests are valid for.
     *
     * @param int $age The max-age for OPTIONS requests in seconds
     * @return $this
     */
    public function maxAge($age)
    {
        $this->_headers['Access-Control-Max-Age'] = $age;

        return $this;
    }
}
