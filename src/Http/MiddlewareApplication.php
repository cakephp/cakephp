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
namespace Cake\Http;

use Cake\Core\HttpApplicationInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Base class for standalone HTTP applications
 *
 * Provides a base class to inherit from for applications using
 * only the http package. This class defines a fallback handler
 * that renders a simple 404 response.
 *
 * You can overload the `handle` method to provide your own logic
 * to run when no middleware generates a response.
 */
abstract class MiddlewareApplication implements HttpApplicationInterface
{
    /**
     * @inheritDoc
     */
    abstract public function bootstrap(): void;

    /**
     * @inheritDoc
     */
    abstract public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue;

    /**
     * Generate a 404 response as no middleware handled the request.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handle(
        ServerRequestInterface $request,
    ): ResponseInterface {
        return new Response(['body' => 'Not found', 'status' => 404]);
    }
}
