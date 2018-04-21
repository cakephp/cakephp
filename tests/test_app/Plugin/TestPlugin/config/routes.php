<?php
use Cake\Core\Configure;

Configure::write('PluginTest.test_plugin.routes', 'loaded plugin routes');

if (isset($routes)) {
    $routes->get('/test_plugin', ['controller' => 'TestPlugin', 'plugin' => 'TestPlugin', 'action' => 'index']);
}
