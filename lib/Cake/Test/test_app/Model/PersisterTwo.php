<?php
/**
 * Test App Comment Model
 *
 *
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
 * @package       Cake.Test.test_app.Model
 * @since         CakePHP v 1.2.0.7726
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class PersisterTwo extends AppModel {

	public $useTable = 'posts';

	public $name = 'PersisterTwo';

	public $actsAs = array('PersisterOneBehavior', 'TestPlugin.TestPluginPersisterOne');

	public $hasMany = array('Comment', 'TestPlugin.TestPluginComment');

}
