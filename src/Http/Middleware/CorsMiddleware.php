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
     * @var array Access-Control-Max-Age
     */
    protected $maxAge = [];

    /**
     * Access-Control-Allow-Origin
     *
     * Sets the allowed origins. You can use *.example.com wildcards to accept subdomains, or * to allow all domains
     *
     * @param array|string $origins list of allowed cors request origins or '*' to allow all origins
     * @param array|string $path the scope of the current configuration. Can be a string or an array of string
     * @return $this
     */
    public function setAllowedOrigins($origins, $path = '/')
    {
        foreach ((array)$path as $_path) {
            $this->allowedOrigins[$this->normalizePath($_path)] = $origins;
        }

        return $this;
    }

    /**
     * Access-Control-Allow-Credentials
     *
     * enable / disabled allowed credentials
     *
     * @param bool $enabled enable / disable value
     * @param array|string $path the scope of the current configuration. Can be a string or an array of string
     * @return $this
     */
    public function setAllowCredentials(bool $enabled, $path = '/')
    {
        foreach ((array)$path as $_path) {
            $this->allowCredentials[$this->normalizePath($_path)] = $enabled;
        }

        return $this;
    }

    /**
     * Access-Control-Expose-Headers
     *
     * Sets the headers to expose
     *
     * @param array $headers list of headers to expose
     * @param array|string $path the scope of the current configuration. Can be a string or an array of string
     * @return $this
     */
    public function setExposedHeaders(array $headers, $path = '/')
    {
        foreach ((array)$path as $_path) {
            $this->exposedHeaders[$this->normalizePath($_path)] = $headers;
        }

        return $this;
    }

    /**
     * Access-Control-Allowed-Methods
     *
     * Sets the allowed HTTP methods
     *
     * @param array $methods list of allowed cors request http methods
     * @param array|string $path the scope of the current configuration. Can be a string or an array of string
     * @return $this
     */
    public function setAllowedMethods(array $methods, $path = '/')
    {
        foreach ((array)$path as $_path) {
            $this->allowedMethods[$this->normalizePath($_path)] = $methods;
        }

        return $this;
    }

    /**
     * Access-Control-Allowed-Headers
     *
     * Sets the allowed HTTP request headers
     *
     * @param array|string $headers list of allowed cors request http headers or '*' to allow all headers
     * @param array|string $path the scope of the current configuration. Can be a string or an array of string
     * @return $this
     */
    public function setAllowedHeaders($headers, $path = '/')
    {
        foreach ((array)$path as $_path) {
            $this->allowedHeaders[$this->normalizePath($_path)] = $headers;
        }

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
        $path = $request->getPath();
        $cors = new CorsBuilder($response, $request->getHeaderLine('Origin'), $request->scheme() == 'https');
        $cors->maxAge(
            $this->getByPath($path, $this->maxAge, 0)
        );
        //set the allowed origin on top so the browsers will show debug informations
        //in case of wrong cors directive
        $cors->allowOrigin($request->getHeaderLine('Origin'));

        $requestMethod = $request->getHeaderLine('Access-Control-Request-Method');
        $allowMethods = $this->getByPath($path, $this->allowedMethods, []);

        if ($this->allowedMethods && in_array($requestMethod, $allowMethods, true)) {
            $cors->allowMethods([$requestMethod]);
        } else {
            return $cors->build()->withStatus(405);
        }

        $allowedHeaders = $this->getByPath($path, $this->allowedHeaders, []);
        $requestHeaders = $request->getHeaderLine('Access-Control-Request-Headers')
            ? explode(', ', $request->getHeaderLine('Access-Control-Request-Headers')) :
            [];
        $allowedHeaders = $allowedHeaders == '*'
            ? $requestHeaders
            : $allowedHeaders;
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

        if ($this->getByPath($path, $this->allowCredentials, false)) {
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

        $exposedHeaders = $this->getByPath($request->getPath(), $this->exposedHeaders, []);
        if ($exposedHeaders) {
            $cors->exposeHeaders($exposedHeaders);
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
        $allowedOrigins = $this->getByPath($request->getPath(), $this->allowedOrigins, []);

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
     * Get configured properties based on the requested path
     *
     * @param string $path the current path
     * @param array $props the array containing per path config
     * @param mixed $default the default value to return if no path found
     * @return mixed
     */
    protected function getByPath(string $path, array $props, $default = null)
    {
        $value = $default;
        $priority = 0;
        foreach ($props as $propPath => $propValue) {
            if ($propPath == '/' || preg_match('/^' . preg_quote($propPath, '/') . '(\/|$)/i', $path)) {
                //if the path matches a configured path with more matching segments
                $pathPriority = $propPath == '/' ? 0 : count(explode('/', $propPath));
                if ($pathPriority >= $priority) {
                    $priority = $pathPriority;
                    $value = $propValue;
                }
            }
        }

        return $value;
    }

    /**
     * Normalize a path adding a slash at start and
     * remove trailing slash
     *
     * @param string $path the current path
     * @return string
     */
    protected function normalizePath($path)
    {
        if ($path[0] != '/') {
            $path = '/' . $path;
        }
        if ($path[-1] == '/') {
            $path = substr($path, 0, -1);
        }

        return $path;
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
