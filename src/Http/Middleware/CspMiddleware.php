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

use Cake\Core\InstanceConfigTrait;
use ParagonIE\CSPBuilder\CSPBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/**
 * Content Security Policy Middleware
 *
 * ### Options
 *
 * - `scriptNonce` Enable to have a nonce policy added to the script-src directive.
 * - `styleNonce` Enable to have a nonce policy added to the style-src directive.
 */
class CspMiddleware implements MiddlewareInterface
{
    use InstanceConfigTrait;

    /**
     * CSP Builder
     *
     * @var \ParagonIE\CSPBuilder\CSPBuilder $csp CSP Builder or config array
     */
    protected $csp;

    /**
     * Configuration options.
     *
     * @var array<string, mixed>
     */
    protected $_defaultConfig = [
        'scriptNonce' => false,
        'styleNonce' => false,
    ];

    /**
     * Constructor
     *
     * @param \ParagonIE\CSPBuilder\CSPBuilder|array $csp CSP object or config array
     * @param array<string, mixed> $config Configuration options.
     * @throws \RuntimeException
     */
    public function __construct($csp, array $config = [])
    {
        if (!class_exists(CSPBuilder::class)) {
            throw new RuntimeException('You must install paragonie/csp-builder to use CspMiddleware');
        }
        $this->setConfig($config);

        if (!$csp instanceof CSPBuilder) {
            $csp = new CSPBuilder($csp);
        }

        $this->csp = $csp;
    }

    /**
     * Add nonces (if enabled) to the request and apply the CSP header to the response.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->getConfig('scriptNonce')) {
            $request = $request->withAttribute('cspScriptNonce', $this->csp->nonce('script-src'));
        }
        if ($this->getconfig('styleNonce')) {
            $request = $request->withAttribute('cspStyleNonce', $this->csp->nonce('style-src'));
        }
        $response = $handler->handle($request);

        /** @var \Psr\Http\Message\ResponseInterface */
        return $this->csp->injectCSPHeader($response);
    }
}
