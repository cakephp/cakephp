<?php
/**
 * Tree behavior class.
 *
 * Enables a model object to act as a node-based tree.
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
 * @since         CakePHP(tm) v 1.2.0.5331
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Number Tree Test Fixture
 *
 * Generates a tree of data for use testing the tree behavior
 *
 * @package       cake.tests.fixtures
 */
class NumberTreeTwoFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'NumberTree'
 * @access public
 */
	public $name = 'NumberTreeTwo';

/**
 * fields property
 *
 * @var array
 * @access public
 */
	public $fields = array(
		'id'	=> array('type' => 'integer','key' => 'primary'),
		'name'	=> array('type' => 'string','null' => false),
		'number_tree_id' => array('type' => 'integer', 'null' => false),
		'parent_id' => 'integer',
		'lft'	=> array('type' => 'integer','null' => false),
		'rght'	=> array('type' => 'integer','null' => false)
	);
}
