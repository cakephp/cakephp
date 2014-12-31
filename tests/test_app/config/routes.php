<?php
/**
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
use Cake\Routing\Router;

Router::extensions('json');
Router::scope('/', function ($routes) {
    $routes->connect('/', ['controller' => 'pages', 'action' => 'display', 'home']);
    $routes->connect('/some_alias', ['controller' => 'tests_apps', 'action' => 'some_method']);
    $routes->fallbacks();
});
