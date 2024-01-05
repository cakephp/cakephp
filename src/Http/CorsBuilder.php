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
 * @since         3.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http;

use Psr\Http\Message\ResponseInterface;

/**
 * A builder object that assists in defining Cross Origin Request related
 * headers.
 *
 * Each of the methods in this object provide a fluent interface. Once you've
 * set all the headers you want to use, the `build()` method can be used to return
 * a modified Response.
 *
 * It is most convenient to get this object via `Response::cors()`.
 *
 * @see \Cake\Http\Response::cors()
 */
class CorsBuilder
{
    /**
     * The response object this builder is attached to.
     *
     * @var \Psr\Http\Message\ResponseInterface
     */
    protected ResponseInterface $_response;

    /**
     * The request's Origin header value
     *
     * @var string
     */
    protected string $_origin;

    /**
     * Whether the request was over SSL.
     *
     * @var bool
     */
    protected bool $_isSsl;

    /**
     * The headers that have been queued so far.
     *
     * @var array<string, mixed>
     */
    protected array $_headers = [];

    /**
     * Constructor.
     *
     * @param \Psr\Http\Message\ResponseInterface $response The response object to add headers onto.
     * @param string $origin The request's Origin header.
     * @param bool $isSsl Whether the request was over SSL.
     */
    public function __construct(ResponseInterface $response, string $origin, bool $isSsl = false)
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
     * @return \Psr\Http\Message\ResponseInterface A new instance of the response with new headers.
     */
    public function build(): ResponseInterface
    {
        $response = $this->_response;
        if (empty($this->_origin)) {
            return $response;
        }

        if (isset($this->_headers['Access-Control-Allow-Origin'])) {
            foreach ($this->_headers as $key => $value) {
                $response = $response->withHeader($key, $value);
            }
        }

        return $response;
    }

    /**
     * Set the list of allowed domains.
     *
     * Accepts a string or an array of domains that have CORS enabled.
     * You can use `*.example.com` wildcards to accept subdomains, or `*` to allow all domains
     *
     * @param array<string>|string $domains The allowed domains
     * @return $this
     */
    public function allowOrigin(array|string $domains)
    {
        $allowed = $this->_normalizeDomains((array)$domains);
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
     * @param list<string> $domains Domain names to normalize.
     * @return array<array<string, string>>
     */
    protected function _normalizeDomains(array $domains): array
    {
        $result = [];
        foreach ($domains as $domain) {
            if ($domain === '*') {
                $result[] = ['preg' => '@.@', 'original' => '*'];
                continue;
            }

            $original = $preg = $domain;
            if (!str_contains($domain, '://')) {
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
     * @param list<string> $methods The allowed HTTP methods
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
     * Allowed headers that can be sent in CORS requests.
     *
     * @param list<string> $headers The list of headers to accept in CORS requests.
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
     * @param list<string> $headers The list of headers to expose CORS responses
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
     * @param string|int $age The max-age for OPTIONS requests in seconds
     * @return $this
     */
    public function maxAge(string|int $age)
    {
        $this->_headers['Access-Control-Max-Age'] = $age;

        return $this;
    }
}
