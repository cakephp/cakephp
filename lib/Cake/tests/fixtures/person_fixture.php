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
 * @since         CakePHP(tm) v 1.2.0.6700
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Short description for class.
 *
 * @package       cake.tests.fixtures
 */
class PersonFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'Person'
 * @access public
 */
	public $name = 'Person';

/**
 * fields property
 *
 * @var array
 * @access public
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'key' => 'primary'),
		'name' => array('type' => 'string', 'null' => false, 'length' => 32),
		'mother_id' => array('type' => 'integer', 'null' => false, 'key' => 'index'),
		'father_id' => array('type' => 'integer', 'null' => false),
		'indexes' => array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'mother_id' => array('column' => array('mother_id', 'father_id'), 'unique' => 0)
		)
	);

/**
 * records property
 *
 * @var array
 * @access public
 */
	public $records = array(
		array('name' => 'person', 'mother_id' => 2, 'father_id' => 3),
		array('name' => 'mother', 'mother_id' => 4, 'father_id' => 5),
		array('name' => 'father', 'mother_id' => 6, 'father_id' => 7),
		array('name' => 'mother - grand mother', 'mother_id' => 0, 'father_id' => 0),
		array('name' => 'mother - grand father', 'mother_id' => 0, 'father_id' => 0),
		array('name' => 'father - grand mother', 'mother_id' => 0, 'father_id' => 0),
		array('name' => 'father - grand father', 'mother_id' => 0, 'father_id' => 0)
	);
}
