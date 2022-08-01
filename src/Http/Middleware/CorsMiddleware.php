<?php
declare(strict_types=1);

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
 * @since         4.4.3
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Middleware;

use Cake\Http\CorsBuilder;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Handles cors request in a convenient way
 */
class CorsMiddleware implements MiddlewareInterface
{
    /**
     * simple header set
     *
     * @var array
     */
    protected $simpleHeaders = [
        'accept',
        'accept-language',
        'content-language',
        'origin',
    ];

    /**
     * @var array Access-Control-Allow-Origin
     */
    protected $allowedOrigins = [];

    /**
     * @var array Access-Control-Allow-Credentials
     */
    protected $allowCredentials = [];

    /**
     * @var array Access-Control-Expose-Headers
     */
    protected $exposedHeaders = [];

    /**
     * @var array  Access-Control-Allow-Methods
     */
    protected $allowedMethods = [];

    /**
     * @var array  Access-Control-Allow-Headers
     */
    protected $allowedHeaders = [];

    /**
     * @var int Access-Control-Max-Age
     */
    protected $maxAge = 0;

    /**
     * Access-Control-Allow-Origin
     *
     * Sets the allowed origins. You can use *.example.com wildcards to accept subdomains, or * to allow all domains
     *
     * @param array|string $origins list of allowed cors request origins or '*' to allow all origins
     * @return $this
     */
    public function setAllowedOrigins($origins)
    {
        $this->allowedOrigins = $origins;

        return $this;
    }

    /**
     * Access-Control-Allow-Credentials
     *
     * enable / disabled allowed credentials
     *
     * @param bool $enabled enable / disable value
     * @return $this
     */
    public function setAllowCredentials(bool $enabled)
    {
        $this->allowCredentials = $origins;

        return $this;
    }

    /**
     * Access-Control-Expose-Headers
     *
     * Sets the headers to expose
     *
     * @param array $headers list of headers to expose
     * @return $this
     */
    public function setExposedHeaders(array $headers)
    {
        $this->exposedHeaders = $headers;

        return $this;
    }

    /**
     * Access-Control-Allowed-Methods
     *
     * Sets the allowed HTTP methods
     *
     * @param array $methods list of allowed cors request http methods
     * @return $this
     */
    public function setAllowedMethods(array $methods)
    {
        $this->allowedMethods = $methods;

        return $this;
    }

    /**
     * Access-Control-Allowed-Headers
     *
     * Sets the allowed HTTP request headers
     *
     * @param array|string $headers list of allowed cors request http headers or '*' to allow all headers
     * @return $this
     */
    public function setAllowedHeaders($headers)
    {
        $this->allowedHeaders = $headers;

        return $this;
    }

    /**
     * Check if the request is a CORS request
     *
     * @param \Cake\Http\ServerRequest $request the request
     * @return bool
     */
    protected function isCors(ServerRequest $request): bool
    {
        $host = $request->scheme() . '://' . $request->host();

        return !empty($request->getHeaderLine('Origin')) && $request->getHeaderLine('Origin') != $host;
    }

    /**
     * Check if the request is a CORS preflight request
     *
     * @param \Cake\Http\ServerRequest $request the request
     * @return bool
     */
    protected function isPreflight(ServerRequest $request): bool
    {
        return strtolower($request->getEnv('ORIGINAL_REQUEST_METHOD')) == 'options'
            && !empty($request->getHeaderLine('Access-Control-Request-Method'));
    }

    /**
     * Create a response to a preflight request with all necessary headers
     *
     * @param \Cake\Http\ServerRequest $request the request
     * @return \Psr\Http\Message\ResponseInterface the preflight request response with all necessary headers
     */
    protected function getPreflightResponse(ServerRequest $request): ResponseInterface
    {
        $response = new Response();
        $cors = new CorsBuilder($response, $request->getHeaderLine('Origin'), $request->scheme() == 'https');
        $cors->maxAge($this->maxAge);
        //set the allowed origin on top so the browsers will show debug informations
        //in case of wrong cors directive
        $cors->allowOrigin($request->getHeaderLine('Origin'));

        $requestMethod = $request->getHeaderLine('Access-Control-Request-Method');

        if ($this->allowedMethods && in_array($requestMethod, $this->allowedMethods, true)) {
            $cors->allowMethods([$requestMethod]);
        } else {
            return $cors->build()->withStatus(405);
        }

        $requestHeaders = $request->getHeaderLine('Access-Control-Request-Headers')
            ? explode(', ', $request->getHeaderLine('Access-Control-Request-Headers')) :
            [];
        $allowedHeaders = $this->allowedHeaders == '*'
            ? $requestHeaders
            : $this->allowedHeaders;
        $requestHeaders = array_filter($requestHeaders, function ($h) {

            return !in_array(strtolower($h), $this->simpleHeaders);
        });
        $headersNotMatch = array_udiff($requestHeaders, $allowedHeaders, 'strcasecmp');

        if ($requestHeaders) {
            if ($allowedHeaders && empty($headersNotMatch)) {
                $cors->allowHeaders($allowedHeaders);
            } else {
                return $cors->build()->withStatus(400);
            }
        }

        if ($this->allowCredentials) {
            $cors->allowCredentials();
        }

        return $cors->build();
    }

    /**
     * Create a response to an actual or simple cors request with all necessary headers
     *
     * @param \Psr\Http\Message\ResponseInterface $response the generated response. it can be a simple respone or a redirect response
     * @param \Cake\Http\ServerRequest $request the request
     * @return \Psr\Http\Message\ResponseInterface the cors request response with all necessary headers
     */
    protected function setResponseHeaders(ResponseInterface $response, ServerRequest $request): ResponseInterface
    {
        $cors = new CorsBuilder($response, $request->getHeaderLine('Origin'), $request->scheme() == 'https');
        $cors->allowOrigin($request->getHeaderLine('Origin'));

        if ($this->exposedHeaders) {
            $cors->exposeHeaders($this->exposedHeaders);
        }

        return $cors->build();
    }

    /**
     * Check if the request origin is allowed based on the current configuration
     *
     * @param \Cake\Http\ServerRequest $request the request
     * @return bool
     */
    protected function checkOrigin(ServerRequest $request): bool
    {
        $origin = $request->getHeaderLine('Origin');
        $allowedOrigins = $this->allowedOrigins;

        if ($allowedOrigins == '*') {
            return true;
        }

        foreach ($allowedOrigins as $allowedOrigin) {
            $regex = $allowedOrigin;
            if (strpos($allowedOrigin, '://') === false) {
                $regex = $request->scheme() . '://' . $regex;
            }
            $regex = str_replace('\*', '.*', preg_quote($regex, '/'));
            if (preg_match('/' . $regex . '/i', $origin)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create an empty response in the case of the origin is not allowed
     *
     * @return \Cake\Http\Response
     */
    protected function getNotAllowedResponse(): Response
    {
        $response = new Response();

        return $response->withStatus(204);
    }

    /**
     * Check if the current request is a cors request and send the correct response
     *
     * @param \Cake\Http\ServerRequest $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->isCors($request)) {
            if (!$this->checkOrigin($request)) {
                //if a cors request has a not allowed origin, return an empty response
                return $this->getNotAllowedResponse();
            }
            if ($this->isPreflight($request)) {
                $response = $this->getPreflightResponse($request);
            } else {
                $response = $handler->handle($request);
                $response = $this->setResponseHeaders($response, $request);
            }
        } else {
            $response = $handler->handle($request);
        }

        return $response;
    }
}
