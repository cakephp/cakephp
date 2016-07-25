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
namespace Cake\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Executes the middleware queue and provides the `next` callable
 * that allows the queue to be iterated.
 */
class Runner
{
    /**
     * The current index in the middleware queue.
     *
     * @var int
     */
    protected $index;

    /**
     * The middleware queue being run.
     *
     * @var MiddlewareStack
     */
    protected $middleware;

    /**
     * @param \Cake\Http\MiddlewareStack $middleware The middleware queue
     * @param \Psr\Http\Message\ServerRequestInterface $request The Server Request
     * @param \Psr\Http\Message\ResponseInterface $response The response
     * @return \Psr\Http\Message\ResponseInterface A response object
     */
    public function run($middleware, ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->middleware = $middleware;
        $this->index = 0;

        return $this->__invoke($request, $response);
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request  The server request
     * @param \Psr\Http\Message\ResponseInterface $response The response object
     * @return \Psr\Http\Message\ResponseInterface An updated response
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $next = $this->middleware->get($this->index);
        if ($next) {
            $this->index++;

            return $next($request, $response, $this);
        }

        // End of the queue
        return $response;
    }
}
