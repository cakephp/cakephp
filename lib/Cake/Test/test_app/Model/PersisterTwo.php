<?php
/**
 * Test App Comment Model
 *
 *
 *
 * PHP 5
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
 * @package       Cake.Test.TestApp.Model
 * @since         CakePHP v 1.2.0.7726
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Class PersisterTwo
 *
 * @package       Cake.Test.TestApp.Model
 */
class PersisterTwo extends AppModel {

	public $useTable = 'posts';

	public $name = 'PersisterTwo';

	public $actsAs = array('PersisterOneBehavior', 'TestPlugin.TestPluginPersisterOne');

	public $hasMany = array('Comment', 'TestPlugin.TestPluginComment');

}
