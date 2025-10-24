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

use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Decorate closures as PSR-15 middleware.
 *
 * Decorates closures with the following signature:
 *
 * ```
 * function (
 *     ServerRequestInterface $request,
 *     RequestHandlerInterface $handler
 * ): ResponseInterface
 * ```
 *
 * such that it will operate as PSR-15 middleware.
 */
class ClosureDecoratorMiddleware implements MiddlewareInterface
{
    /**
     * A Closure.
     *
     * @var \Closure
     */
    protected Closure $callable;

    /**
     * Constructor
     *
     * @param \Closure $callable A closure.
     */
    public function __construct(Closure $callable)
    {
        $this->callable = $callable;
    }

    /**
     * Run the callable to process an incoming server request.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request instance.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler Request handler instance.
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return ($this->callable)(
            $request,
            $handler,
        );
    }

    /**
     * @internal
     * @return \Closure
     */
    public function getCallable(): Closure
    {
        return $this->callable;
    }
}
