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
 * @since         4.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Middleware;

use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

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
     *
     * @var array
     * @psalm-var array{redirect: bool, statusCode: int, headers: array, disableOnDebug: bool}
     */
    protected $config = [
        'redirect' => true,
        'statusCode' => 301,
        'headers' => [],
        'disableOnDebug' => true,
    ];

    /**
     * Constructor
     *
     * @param array $config The options to use.
     * @see self::$config
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
        if (
            $request->getUri()->getScheme() === 'https'
            || ($this->config['disableOnDebug']
                && Configure::read('debug'))
        ) {
            return $handler->handle($request);
        }

        if ($this->config['redirect'] && $request->getMethod() === 'GET') {
            $uri = $request->getUri()->withScheme('https');

            return new RedirectResponse(
                $uri,
                $this->config['statusCode'],
                $this->config['headers']
            );
        }

        throw new BadRequestException(
            'Requests to this URL must be made with HTTPS.'
        );
    }
}
