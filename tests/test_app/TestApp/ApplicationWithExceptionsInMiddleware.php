<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.6.2
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp;

use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\RoutingMiddleware;
use TestApp\Middleware\ThrowsExceptionMiddleware;

/**
 * Simple Application class doing nothing that:
 */
class ApplicationWithExceptionsInMiddleware extends BaseApplication
{
    /**
     * Bootstrap hook.
     *
     * Nerfed as this is for IntegrationTestCase testing.
     *
     * @return void
     */
    public function bootstrap(): void
    {
        // Do nothing.
    }

    public function middleware(MiddlewareQueue $middlewareQueueQueue): MiddlewareQueue
    {
        $middlewareQueueQueue
            // Catch any exceptions in the lower layers,
            // and make an error page/response
            ->add(ErrorHandlerMiddleware::class)

            // Throw an error
            ->add(ThrowsExceptionMiddleware::class)

            // Add routing middleware.
            ->add(new RoutingMiddleware($this));

        return $middlewareQueueQueue;
    }
}
