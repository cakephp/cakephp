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
use InvalidArgumentException;
use ParagonIE\CSPBuilder\CSPBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Content Security Policy Middleware
 */
class CspMiddleware implements MiddlewareInterface
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
     * @param \ParagonIE\CSPBuilder\CSPBuilder|array|null $csp CSP object or config array
     * @throws \Exception
     */
    public function __construct($csp = null)
    {
        if ($csp === null) {
            $cspConfig = (array)Configure::read('App.CSP');
        } else {
            $cspConfig = $csp;
        }

        if (!empty($cspConfig) && is_array($cspConfig)) {
            $this->csp = CSPBuilder::fromData(json_encode($cspConfig));

            return;
        }

        if (!$csp instanceof CSPBuilder) {
            throw new InvalidArgumentException(sprintf(
                'Expected `%s`, `%s` given.',
                CSPBuilder::class,
                gettype($csp)
            ));
        }

        $this->csp = $csp;
    }

    /**
     * Serve assets if the path matches one.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        /** @var \Psr\Http\Message\ResponseInterface $response */
        $response = $this->csp->injectCSPHeader($response);

        return $response;
    }
}
