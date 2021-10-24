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
 * @since         3.5.2
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp;

use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\RoutingMiddleware;

/**
 * Simple Application class doing nothing that:
 *
 * - do nothing in bootstrap
 * - add just `RoutingMiddleware` to middleware queue
 *
 * Useful to test app using the default `BaseApplication::routes()` method
 */
class ApplicationWithDefaultRoutes extends BaseApplication
{
    /**
     * Bootstrap hook.
     *
     * Nerfed as this is for IntegrationTestCase testing.
     */
    public function bootstrap(): void
    {
        // Do nothing.
    }

    public function middleware(MiddlewareQueue $middlewareQueueQueue): MiddlewareQueue
    {
        $middlewareQueueQueue->add(new RoutingMiddleware($this));

        return $middlewareQueueQueue;
    }
}
