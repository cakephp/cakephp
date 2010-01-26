<?php
/**
 * Unconventional Tree behavior class test fixture.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.fixtures
 * @since         CakePHP(tm) v 1.2.0.7879
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */

/**
 * UnconventionalTreeFixture class
 *
 * Like Number tree, but doesn't use the default values for lft and rght or parent_id
 *
 * @uses          CakeTestFixture
 * @package       cake
 * @subpackage    cake.tests.fixtures
 */
class UnconventionalTreeFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'FlagTree'
 * @access public
 */
	var $name = 'UnconventionalTree';

/**
 * fields property
 *
 * @var array
 * @access public
 */
	var $fields = array(
		'id'	=> array('type' => 'integer','key' => 'primary'),
		'name'	=> array('type' => 'string','null' => false),
		'join' => 'integer',
		'left'	=> array('type' => 'integer','null' => false),
		'right'	=> array('type' => 'integer','null' => false),
	);
}
?>