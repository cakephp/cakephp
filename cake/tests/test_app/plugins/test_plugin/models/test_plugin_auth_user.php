<?php
/**
 * Test Plugin Auth User Model
 *
 *
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
 * @package       cake
 * @subpackage    cake.cake.tests.test_app.plugins.test_plugin
 * @since         CakePHP v 1.2.0.4487
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

class TestPluginAuthUser extends TestPluginAppModel {

/**
 * Name property
 *
 * @var string
 */
	var $name = 'TestPluginAuthUser';

/**
 * useTable property
 *
 * @var string
 */
	var $useTable = 'auth_users';

/**
 * useDbConfig property
 *
 * @var string 'test_suite'
 * @access public
 */
	var $useDbConfig = 'test_suite';
}
