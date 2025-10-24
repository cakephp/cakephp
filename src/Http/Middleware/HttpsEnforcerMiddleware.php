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
 * @since         4.0.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Middleware;

use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\ServerRequest;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use UnexpectedValueException;

/**
 * Enforces use of HTTPS (SSL) for requests.
 */
class HttpsEnforcerMiddleware implements MiddlewareInterface
{
    /**
     * Configuration.
     *
     * ### Options
     *
     * - `redirect` - If set to true (default) redirects GET requests to same URL with https.
     * - `statusCode` - Status code to use in case of redirect, defaults to 301 - Permanent redirect.
     * - `headers` - Array of response headers in case of redirect.
     * - `disableOnDebug` - Whether HTTPS check should be disabled when debug is on. Default `true`.
     * - `trustedProxies` - Array of trusted proxies that will be passed to the request. Defaults to `null`.
     * - 'hsts' - Strict-Transport-Security header for HTTPS response configuration. Defaults to `null`.
     *    If enabled, an array of config options:
     *
     *        - 'maxAge' - `max-age` directive value in seconds.
     *        - 'includeSubDomains' - Whether to include `includeSubDomains` directive. Defaults to `false`.
     *        - 'preload' - Whether to include 'preload' directive. Defaults to `false`.
     *
     * @var array<string, mixed>
     */
    protected array $config = [
        'redirect' => true,
        'statusCode' => 301,
        'headers' => [],
        'disableOnDebug' => true,
        'trustedProxies' => null,
        'hsts' => null,
    ];

    /**
     * Constructor
     *
     * @param array<string, mixed> $config The options to use.
     * @see \Cake\Http\Middleware\HttpsEnforcerMiddleware::$config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config + $this->config;
    }

    /**
     * Check whether request has been made using HTTPS.
     *
     * Depending on the configuration and request method, either redirects to
     * same URL with https or throws an exception.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     * @throws \Cake\Http\Exception\BadRequestException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request instanceof ServerRequest && is_array($this->config['trustedProxies'])) {
            $request->setTrustedProxies($this->config['trustedProxies']);
        }

        if (
            $request->getUri()->getScheme() === 'https'
            || ($this->config['disableOnDebug']
                && Configure::read('debug'))
        ) {
            $response = $handler->handle($request);
            if ($this->config['hsts']) {
                return $this->addHsts($response);
            }

            return $response;
        }

        if ($this->config['redirect'] && $request->getMethod() === 'GET') {
            $uri = $request->getUri()->withScheme('https');
            $base = $request->getAttribute('base');
            if ($base) {
                $uri = $uri->withPath($base . $uri->getPath());
            }

            return new RedirectResponse(
                $uri,
                $this->config['statusCode'],
                $this->config['headers'],
            );
        }

        throw new BadRequestException(
            'Requests to this URL must be made with HTTPS.',
        );
    }

    /**
     * Adds Strict-Transport-Security header to response.
     *
     * @param \Psr\Http\Message\ResponseInterface $response Response
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function addHsts(ResponseInterface $response): ResponseInterface
    {
        $config = $this->config['hsts'];
        if (!is_array($config)) {
            throw new UnexpectedValueException('The `hsts` config must be an array.');
        }

        $value = 'max-age=' . $config['maxAge'];
        if ($config['includeSubDomains'] ?? false) {
            $value .= '; includeSubDomains';
        }
        if ($config['preload'] ?? false) {
            $value .= '; preload';
        }

        return $response->withHeader('strict-transport-security', $value);
    }
}
