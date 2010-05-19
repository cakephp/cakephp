<?php
/**
 * Short description for file.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.fixtures
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */

/**
 * Short description for class.
 *
 * @package       cake
 * @subpackage    cake.tests.fixtures
 */
class AroTwoFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'AroTwo'
 * @access public
 */
	var $name = 'AroTwo';

/**
 * fields property
 *
 * @var array
 * @access public
 */
	var $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'parent_id' => array('type' => 'integer', 'length' => 10, 'null' => true),
		'model' => array('type' => 'string', 'null' => true),
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
	var $records = array(
		array('id' => 1, 'parent_id' => null, 'model' => null, 'foreign_key' => null, 'alias' => 'root', 	'lft' => '1',  'rght' => '20'),
		array('id' => 2, 'parent_id' => 1, 'model' => 'Group', 'foreign_key' => '1', 'alias' => 'admin', 	'lft' => '2',   'rght' => '5'),
		array('id' => 3, 'parent_id' => 1, 'model' => 'Group', 'foreign_key' => '2', 'alias' => 'managers', 'lft' => '6',  'rght' => '9'),
		array('id' => 4, 'parent_id' => 1, 'model' => 'Group', 'foreign_key' => '3', 'alias' => 'users',    'lft' => '10', 'rght' => '19'),
		array('id' => 5, 'parent_id' => 2, 'model' => 'User',  'foreign_key' => '1', 'alias' => 'Bobs',      'lft' => '3',  'rght' => '4' ),
		array('id' => 6, 'parent_id' => 3, 'model' => 'User',  'foreign_key' => '2', 'alias' => 'Lumbergh',  'lft' => '7' ,  'rght' => '8'),
		array('id' => 7, 'parent_id' => 4, 'model' => 'User',  'foreign_key' => '3', 'alias' => 'Samir',     'lft' => '11' ,  'rght' => '12'),
		array('id' => 8, 'parent_id' => 4, 'model' => 'User',  'foreign_key' => '4', 'alias' => 'Micheal',   'lft' => '13',  'rght' => '14'),
		array('id' => 9, 'parent_id' => 4, 'model' => 'User',  'foreign_key' => '5', 'alias' => 'Peter',     'lft' => '15',  'rght' => '16'),
		array('id' => 10, 'parent_id' => 4, 'model' => 'User',  'foreign_key' => '6', 'alias' => 'Milton',   'lft' => '17',  'rght' => '18'),
	);
}
