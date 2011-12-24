<?php
/**
 * Short description for file.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Short description for class.
 *
 * @package       Cake.Test.Fixture
 */
class AcoFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'Aco'
 */
	public $name = 'Aco';

/**
 * fields property
 *
 * @var array
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
 */
	public $records = array(
		array('parent_id' => null, 'model' => null, 'foreign_key' => null, 'alias' => 'ROOT', 'lft' => 1,  'rght' => 24),
		array('parent_id' => 1, 'model' => null, 'foreign_key' => null, 'alias' => 'Controller1', 'lft' => 2,  'rght' => 9),
		array('parent_id' => 2, 'model' => null, 'foreign_key' => null, 'alias' => 'action1', 'lft' => 3,  'rght' => 6),
		array('parent_id' => 3, 'model' => null, 'foreign_key' => null, 'alias' => 'record1', 'lft' => 4,  'rght' => 5),
		array('parent_id' => 2, 'model' => null, 'foreign_key' => null, 'alias' => 'action2', 'lft' => 7,  'rght' => 8),
		array('parent_id' => 1, 'model' => null, 'foreign_key' => null, 'alias' => 'Controller2', 'lft' => 10, 'rght' => 17),
		array('parent_id' => 6, 'model' => null, 'foreign_key' => null, 'alias' => 'action1', 'lft' => 11, 'rght' => 14),
		array('parent_id' => 7, 'model' => null, 'foreign_key' => null, 'alias' => 'record1', 'lft' => 12, 'rght' => 13),
		array('parent_id' => 6, 'model' => null, 'foreign_key' => null, 'alias' => 'action2', 'lft' => 15, 'rght' => 16),
		array('parent_id' => 1, 'model' => null, 'foreign_key' => null, 'alias' => 'Users', 'lft' => 18, 'rght' => 23),
		array('parent_id' => 9, 'model' => null, 'foreign_key' => null, 'alias' => 'Users', 'lft' => 19, 'rght' => 22),
		array('parent_id' => 10, 'model' => null, 'foreign_key' => null, 'alias' => 'view', 'lft' => 20, 'rght' => 21),
	);
}
