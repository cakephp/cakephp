<?php
use Cake\Core\Configure;

Configure::write('PluginTest.test_plugin_three.bootstrap', 'loaded plugin three bootstrap');
Configure::write('PluginTest.test_plugin_three.autoload', class_exists('Company\TestPluginThree\Utility\Hello'));

