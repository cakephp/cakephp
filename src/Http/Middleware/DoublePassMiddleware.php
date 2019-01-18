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

use Cake\Core\Exception\Exception;
use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DoublePassMiddleware implements MiddlewareInterface
{
    protected $callable;

    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $response = new Response();

        return ($this->callable)(
            $request,
            $response,
            $this->decorateHandler($handler, $response)
        );
    }

    public function getCallable(): callable
    {
        return $this->callable;
    }

    protected function decorateHandler(RequestHandlerInterface $handler, $response): callable
    {
        return function ($request, $res) use ($handler, $response) {
            // This check doesn't work as intended and returns false positive
            // when response bubbles up.
            // if ($res !== $response) {
            //     throw new Exception('Callable should not modify response instance before calling $next.');
            // }

            return $handler->handle($request);
        };
    }
}
