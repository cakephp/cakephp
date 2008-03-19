<?php
/* SVN FILE: $Id: flag_tree_fixture.php 6354 2008-01-10 07:02:33Z nate $ */
/**
 * Tree behavior class test fixture.
 *
 * Enables a model object to act as a node-based tree.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.fixtures
 * @since			CakePHP(tm) v 1.2.0.5331
 * @version			$Revision: 6354 $
 * @modifiedby		$LastChangedBy: nate $
 * @lastmodified	$Date: 2008-01-10 02:02:33 -0500 (Thu, 10 Jan 2008) $
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
/**
 * Flag Tree Test Fixture
 *
 * Like Number Tree, but uses a flag for testing scope parameters
 *
 * @package		cake
 * @subpackage	cake.tests.fixtures
 */
class FlagTreeFixture extends CakeTestFixture {
	var $name = 'FlagTree';
	var $fields = array(
		'id'	=> array('type' => 'integer','key' => 'primary'),
		'name'	=> array('type' => 'string','null' => false),
		'parent_id' => 'integer',
		'lft'	=> array('type' => 'integer','null' => false),
		'rght'	=> array('type' => 'integer','null' => false),
		'flag'	=> array('type' => 'boolean','null' => false, 'default' => false)
	);
}

?>