<?php
/**
 * Test Plugin Auth User Model
 *
 * PHP 5
 *
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc.
 * @link          http://cakephp.org CakePHP Project
 * @package       Cake.Test.test_app.Plugin.TestPlugin.Model
 * @since         CakePHP v 1.2.0.4487
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TestPluginAuthUser extends TestPluginAppModel {

/**
 * Name property
 *
 * @var string
 */
	public $name = 'TestPluginAuthUser';

/**
 * useTable property
 *
 * @var string
 */
	public $useTable = 'auth_users';

/**
 * useDbConfig property
 *
 * @var string 'test'
 */
	public $useDbConfig = 'test';
}
