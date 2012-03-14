<?php
/**
 * UUID Tree behavior fixture.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 1.2.0.7984
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * UuidTreeFixture class
 *
 * @uses          CakeTestFixture
 * @package       Cake.Test.Fixture
 */
class UuidTreeFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'UuidTree'
 */
	public $name = 'UuidTree';

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id'	=> array('type' => 'string', 'length' => 36, 'key' => 'primary'),
		'name'	=> array('type' => 'string','null' => false),
		'parent_id' => array('type' => 'string', 'length' => 36, 'null' => true),
		'lft'	=> array('type' => 'integer','null' => false),
		'rght'	=> array('type' => 'integer','null' => false)
	);
}
