<?php
/**
 * Short description for file.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
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
class BasketFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'Basket'
 * @access public
 */
	var $name = 'Basket';

/**
 * fields property
 *
 * @var array
 * @access public
 */
	var $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'type' => array('type' => 'string', 'length' => 255),
		'name' => array('type' => 'string', 'length' => 255),
		'object_id' => array('type' => 'integer'),
		'user_id' => array('type' => 'integer'),
	);

/**
 * records property
 *
 * @var array
 * @access public
 */
	var $records = array(
		array('id' => 1, 'type' => 'nonfile', 'name' => 'basket1', 'object_id' => 1, 'user_id' => 1),
		array('id' => 2, 'type' => 'file', 'name' => 'basket2', 'object_id' => 2, 'user_id' => 1),
	);
}
