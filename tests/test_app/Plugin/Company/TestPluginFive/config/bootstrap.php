<?php

use Cake\Core\Configure;

Configure::write('PluginTest.test_plugin_five.bootstrap', 'loaded plugin five bootstrap');
Configure::write('PluginTest.test_plugin_five.autoload', class_exists('Company\TestPluginFive\Utility\Hello'));
