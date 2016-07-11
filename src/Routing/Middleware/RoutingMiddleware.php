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
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Routing\Middleware;

use Cake\Routing\Exception\RedirectException;
use Cake\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\RedirectResponse;

/**
 * Applies routing rules to the request and creates the controller
 * instance if possible.
 */
class RoutingMiddleware
{

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Message\ResponseInterface $response The response.
     * @param callable $next The next middleware to call.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        try {
            $params = (array)$request->getAttribute('params', []);
            if (empty($params['controller'])) {
                $path = $request->getUri()->getPath();
                $request = $request->withAttribute('params', Router::parse($path, $request->getMethod()));
            }
        } catch (RedirectException $e) {
            return new RedirectResponse(
                $e->getMessage(),
                $e->getCode(),
                $response->getHeaders()
            );
        }
        return $next($request, $response);
    }
}
