<?php
/**
 * UUID Tree behavior fixture.
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
 * @since         CakePHP(tm) v 1.2.0.7984
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * UuidTreeFixture class
 *
 * @uses          CakeTestFixture
 * @package       cake.tests.fixtures
 */
class UuidTreeFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'UuidTree'
 * @access public
 */
	public $name = 'UuidTree';

/**
 * fields property
 *
 * @var array
 * @access public
 */
	public $fields = array(
		'id'	=> array('type' => 'string', 'length' => 36, 'key' => 'primary'),
		'name'	=> array('type' => 'string','null' => false),
		'parent_id' => array('type' => 'string', 'length' => 36, 'null' => true),
		'lft'	=> array('type' => 'integer','null' => false),
		'rght'	=> array('type' => 'integer','null' => false)
	);
}
