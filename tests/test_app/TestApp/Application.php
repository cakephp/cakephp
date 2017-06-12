<?php
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
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp;

use Cake\Http\BaseApplication;
use Cake\Routing\Middleware\RoutingMiddleware;

class Application extends BaseApplication
{
    /**
     * Bootstrap hook.
     *
     * Nerfed as this is for IntegrationTestCase testing.
     *
     * @return void
     */
    public function bootstrap()
    {
        // Do nothing.
    }

    public function middleware($middleware)
    {
        $middleware->add(new RoutingMiddleware());
        $middleware->add(function ($req, $res, $next) {
            $res = $next($req, $res);

            return $res->withHeader('X-Middleware', 'true');
        });

        return $middleware;
    }
}
