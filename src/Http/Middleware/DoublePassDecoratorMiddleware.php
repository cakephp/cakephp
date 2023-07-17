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
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Middleware;

use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function Cake\Core\deprecationWarning;

/**
 * Decorate double-pass middleware as PSR-15 middleware.
 *
 * The callable can be a closure with the following signature:
 *
 * ```
 * function (
 *     ServerRequestInterface $request,
 *     ResponseInterface $response,
 *     callable $next
 * ): ResponseInterface
 * ```
 *
 * or a class with `__invoke()` method with same signature as above.
 *
 * Neither the arguments nor the return value need be typehinted.
 *
 * @deprecated 4.3.0 "Double pass" middleware are deprecated.
 *   Use a `Closure` or a class which implements `Psr\Http\Server\MiddlewareInterface` instead.
 */
class DoublePassDecoratorMiddleware implements MiddlewareInterface
{
    /**
     * A closure or invokable object.
     *
     * @var callable
     */
    protected $callable;

    /**
     * Constructor
     *
     * @param callable $callable A closure.
     */
    public function __construct(callable $callable)
    {
        deprecationWarning(
            '"Double pass" middleware are deprecated. Use a `Closure` with the signature of'
            . ' `($request, $handler)` or a class which implements `Psr\Http\Server\MiddlewareInterface` instead.',
            0
        );
        $this->callable = $callable;
    }

    /**
     * Run the internal double pass callable to process an incoming server request.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request instance.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler Request handler instance.
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return ($this->callable)(
            $request,
            new Response(),
            function ($request, $res) use ($handler) {
                return $handler->handle($request);
            }
        );
    }

    /**
     * @internal
     * @return callable
     */
    public function getCallable(): callable
    {
        return $this->callable;
    }
}
