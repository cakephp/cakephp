<?php
/**
 * Routes file
 *
 * Routes for test app
 *
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @package       Cake.Test.TestApp.Config
 * @since         CakePHP v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

Router::parseExtensions('json');
Router::connect('/some_alias', array('controller' => 'tests_apps', 'action' => 'some_method'));
