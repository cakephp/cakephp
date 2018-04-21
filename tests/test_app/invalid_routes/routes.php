<?php
/**
 * Test routes file with routes that trigger a missing route class error.
 * Application requests should have InvalidArgument error rendered.
 */
$routes->setRouteClass('DoesNotExist');
$routes->get('/', ['controller' => 'Pages']);
