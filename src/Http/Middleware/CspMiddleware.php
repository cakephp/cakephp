<?php

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
 * @since         3.9.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Http\Middleware;

use ParagonIE\CSPBuilder\CSPBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/**
 * Content Security Policy Middleware
 */
class CspMiddleware
{
    /**
     * CSP Builder
     *
     * @var \ParagonIE\CSPBuilder\CSPBuilder $csp CSP Builder or config array
     */
    protected $csp;

    /**
     * Constructor
     *
     * @param \ParagonIE\CSPBuilder\CSPBuilder|array $csp CSP object or config array
     * @throws \RuntimeException
     */
    public function __construct($csp)
    {
        if (!class_exists(CSPBuilder::class)) {
            throw new RuntimeException('You must install paragonie/csp-builder to use CspMiddleware');
        }

        if (!$csp instanceof CSPBuilder) {
            $csp = new CSPBuilder($csp);
        }

        $this->csp = $csp;
    }

    /**
     * Apply the middleware.
     *
     * This will inject the CSP header into the response.
     *
     * @param ServerRequestInterface $requestInterface The Request.
     * @param ResponseInterface $responseInterface The Response.
     * @param callable $next Callback to invoke the next middleware.
     * @return \Psr\Http\Message\MessageInterface
     */
    public function __invoke(ServerRequestInterface $requestInterface, ResponseInterface $responseInterface, callable $next)
    {
        $response = $this->csp->injectCSPHeader($responseInterface);

        return $next($requestInterface, $response, $next);
    }
}
