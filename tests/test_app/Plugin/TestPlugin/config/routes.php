<?php

use Cake\Core\Configure;
use Cake\Routing\RouteBuilder;

Configure::write('PluginTest.test_plugin.routes', 'loaded plugin routes');

return function (RouteBuilder $routes) {
    $routes->get(
        '/test_plugin',
        ['controller' => 'TestPlugin', 'plugin' => 'TestPlugin', 'action' => 'index'],
        'test_plugin:index'
    );
};
