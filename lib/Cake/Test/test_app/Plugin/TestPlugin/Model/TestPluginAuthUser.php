<?php
/**
 * Test Plugin Auth User Model
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
 * @package       Cake.Test.TestApp.Plugin.TestPlugin.Model
 * @since         CakePHP v 1.2.0.4487
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * TestPluginAuthUser
 *
 * @package       Cake.Test.TestApp.Plugin.TestPlugin.Model
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
 * @var string
 */
	public $useDbConfig = 'test';
}
