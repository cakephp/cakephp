<?php
/**
 * Short description for file.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests.fixtures
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Short description for class.
 *
 * @package       cake.tests.fixtures
 */
class AcoFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'Aco'
 * @access public
 */
	public $name = 'Aco';

/**
 * fields property
 *
 * @var array
 * @access public
 */
	public $fields = array(
		'id'		=> array('type' => 'integer', 'key' => 'primary'),
		'parent_id'	=> array('type' => 'integer', 'length' => 10, 'null' => true),
		'model'		=> array('type' => 'string', 'null' => true),
		'foreign_key' => array('type' => 'integer', 'length' => 10, 'null' => true),
		'alias' => array('type' => 'string', 'default' => ''),
		'lft' => array('type' => 'integer', 'length' => 10, 'null' => true),
		'rght' => array('type' => 'integer', 'length' => 10, 'null' => true)
	);

/**
 * records property
 *
 * @var array
 * @access public
 */
	public $records = array(
		array('id' => 1, 'parent_id' => null, 'model' => null, 'foreign_key' => null, 'alias' => 'ROOT', 'lft' => 1,  'rght' => 24),
		array('id' => 2, 'parent_id' => 1, 'model' => null, 'foreign_key' => null, 'alias' => 'Controller1', 'lft' => 2,  'rght' => 9),
		array('id' => 3, 'parent_id' => 2, 'model' => null, 'foreign_key' => null, 'alias' => 'action1', 'lft' => 3,  'rght' => 6),
		array('id' => 4, 'parent_id' => 3, 'model' => null, 'foreign_key' => null, 'alias' => 'record1', 'lft' => 4,  'rght' => 5),
		array('id' => 5, 'parent_id' => 2, 'model' => null, 'foreign_key' => null, 'alias' => 'action2', 'lft' => 7,  'rght' => 8),
		array('id' => 6, 'parent_id' => 1, 'model' => null, 'foreign_key' => null, 'alias' => 'Controller2','lft' => 10, 'rght' => 17),
		array('id' => 7, 'parent_id' => 6, 'model' => null, 'foreign_key' => null, 'alias' => 'action1', 'lft' => 11, 'rght' => 14),
		array('id' => 8, 'parent_id' => 7, 'model' => null, 'foreign_key' => null, 'alias' => 'record1', 'lft' => 12, 'rght' => 13),
		array('id' => 9, 'parent_id' => 6, 'model' => null, 'foreign_key' => null, 'alias' => 'action2', 'lft' => 15, 'rght' => 16),
		array('id' => 10, 'parent_id' => 1, 'model' => null, 'foreign_key' => null, 'alias' => 'Users', 'lft' => 18, 'rght' => 23),
		array('id' => 11, 'parent_id' => 9, 'model' => null, 'foreign_key' => null, 'alias' => 'Users', 'lft' => 19, 'rght' => 22),
		array('id' => 12, 'parent_id' => 10, 'model' => null, 'foreign_key' => null, 'alias' => 'view', 'lft' => 20, 'rght' => 21),
	);
}
