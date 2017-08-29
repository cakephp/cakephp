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
 * @since         3.6.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Middleware;

use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use InvalidArgumentException;
use ParagonIE\CSPBuilder\CSPBuilder;

/**
 * Content Security Policy Middleware
 */
class CspMiddleware
{

    /**
     * CSP Builder
     *
     * @var \ParagonIE\CSPBuilder\|array|null $csp CSP Builder or config array
     */
    protected $csp;

    /**
     * Constructor
     *
     * @param null|\ParagonIE\CSPBuilder\
     */
    public function __construct($csp = null)
    {
        if (!is_array($csp)) {
            $cspConfig = (array)Configure::read('App.CSP');
        } else {
            $cspConfig = $csp;
        }

        if (!empty($cspConfig)) {
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
     * Adds the CSP Headers
     *
     * @param \Cake\Http\ServerRequest $request The request.
     * @param \Cake\Http\Response $response The response.
     * @param callable $next Callback to invoke the next middleware.
     * @return \Cake\Http\Response A response
     */
    public function __invoke(ServerRequest $request, Response $response, $next)
    {
        $response = $next($request, $response);

        return $this->csp->injectCSPHeader($response);
    }
}
