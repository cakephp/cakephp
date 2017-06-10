<?php
/**
 * Routes file
 *
 * Routes for test app
 *
 * CakePHP : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @package       Cake.Test.TestApp.Config
 * @since         CakePHP v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

Router::parseExtensions('json');
Router::connect('/some_alias', array('controller' => 'tests_apps', 'action' => 'some_method'));
