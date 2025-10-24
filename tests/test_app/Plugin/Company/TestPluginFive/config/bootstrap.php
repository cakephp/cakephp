<?php

use Cake\Core\Configure;
use Company\TestPluginFive\Utility\Hello;

Configure::write('PluginTest.test_plugin_five.bootstrap', 'loaded plugin five bootstrap');
Configure::write('PluginTest.test_plugin_five.autoload', class_exists(Hello::class));
