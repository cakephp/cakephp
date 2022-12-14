<?php
declare(strict_types=1);

use Cake\Routing\RouteBuilder;

/*
 * Test routes file with routes that trigger a missing route class error.
 * Application requests should have InvalidArgument error rendered.
 */

return function (RouteBuilder $routes) {
    $routes->setRouteClass('DoesNotExist');
    $routes->get('/', ['controller' => 'Pages']);
};
