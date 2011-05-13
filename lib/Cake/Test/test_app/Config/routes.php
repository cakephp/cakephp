<?php
/**
 * Routes file
 *
 * Routes for test app
 *
 * PHP versions 4 and 5
 *
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright 2006-2010, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2010, Cake Software Foundation, Inc.
 * @link          http://cakephp.org CakePHP Project
 * @package       cake.tests.test_app.config
 * @since         CakePHP v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

Router::parseExtensions('json');
Router::connect('/some_alias', array('controller' => 'tests_apps', 'action' => 'some_method'));
Router::redirect('/redirect_me_now', 'http://cakephp.org');
Router::redirect('/redirect_me', array('controller' => 'tests_apps', 'action' => 'some_method'));