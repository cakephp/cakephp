<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Core;

use Cake\Http\MiddlewareQueue;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * An interface defining the methods that the
 * http server depend on.
 */
interface HttpApplicationInterface extends RequestHandlerInterface
{
    /**
     * Load all the application configuration and bootstrap logic.
     *
     * Override this method to add additional bootstrap logic for your application.
     *
     * @return void
     */
    public function bootstrap(): void;

    /**
     * Define the HTTP middleware layers for an application.
     *
     * @param \Cake\Http\MiddlewareQueue $middleware The middleware queue to set in your App Class
     * @return \Cake\Http\MiddlewareQueue
     */
    public function middleware(MiddlewareQueue $middleware): MiddlewareQueue;
}
