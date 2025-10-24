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
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http;

use Cake\Routing\Router;
use Cake\Routing\RoutingApplicationInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Executes the middleware queue and provides the `next` callable
 * that allows the queue to be iterated.
 */
class Runner implements RequestHandlerInterface
{
    /**
     * The middleware queue being run.
     *
     * @var \Cake\Http\MiddlewareQueue
     */
    protected MiddlewareQueue $queue;

    /**
     * Fallback handler to use if middleware queue does not generate response.
     *
     * @var \Psr\Http\Server\RequestHandlerInterface|null
     */
    protected ?RequestHandlerInterface $fallbackHandler = null;

    /**
     * @param \Cake\Http\MiddlewareQueue $queue The middleware queue
     * @param \Psr\Http\Message\ServerRequestInterface $request The Server Request
     * @param \Psr\Http\Server\RequestHandlerInterface|null $fallbackHandler Fallback request handler.
     * @return \Psr\Http\Message\ResponseInterface A response object
     */
    public function run(
        MiddlewareQueue $queue,
        ServerRequestInterface $request,
        ?RequestHandlerInterface $fallbackHandler = null,
    ): ResponseInterface {
        $this->queue = $queue;
        $this->queue->rewind();
        $this->fallbackHandler = $fallbackHandler;

        return $this->handle($request);
    }

    /**
     * Handle incoming server request and return a response.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The server request
     * @return \Psr\Http\Message\ResponseInterface An updated response
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (
            $this->fallbackHandler instanceof RoutingApplicationInterface &&
            $request instanceof ServerRequest
        ) {
            Router::setRequest($request);
        }

        if ($this->queue->valid()) {
            $middleware = $this->queue->current();
            $this->queue->next();

            return $middleware->process($request, $this);
        }

        if ($this->fallbackHandler) {
            return $this->fallbackHandler->handle($request);
        }

        return new Response([
            'body' => 'Middleware queue was exhausted without returning a response '
                . 'and no fallback request handler was set for Runner',
            'status' => 500,
        ]);
    }
}
