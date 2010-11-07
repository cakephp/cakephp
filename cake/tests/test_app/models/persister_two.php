<?php
/**
 * Test App Comment Model
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
 * @subpackage    cake.tests.test_app.models
 * @since         CakePHP v 1.2.0.7726
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class PersisterTwo extends AppModel {
	var $useTable = 'posts';
	var $name = 'PersisterTwo';

	var $actsAs = array('PersisterOneBehavior', 'TestPlugin.TestPluginPersisterOne');

	var $hasMany = array('Comment', 'TestPlugin.TestPluginComment');
}
