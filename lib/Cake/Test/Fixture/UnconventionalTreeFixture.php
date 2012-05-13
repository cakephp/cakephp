<?php
/**
 * Unconventional Tree behavior class test fixture.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 1.2.0.7879
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * UnconventionalTreeFixture class
 *
 * Like Number tree, but doesn't use the default values for lft and rght or parent_id
 *
 * @uses          CakeTestFixture
 * @package       Cake.Test.Fixture
 */
class UnconventionalTreeFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'FlagTree'
 */
	public $name = 'UnconventionalTree';

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id'	=> array('type' => 'integer','key' => 'primary'),
		'name'	=> array('type' => 'string','null' => false),
		'join' => 'integer',
		'left'	=> array('type' => 'integer','null' => false),
		'right'	=> array('type' => 'integer','null' => false),
	);
}
