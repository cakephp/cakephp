<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.fixtures
 * @since         CakePHP(tm) v 1.2.0.6317
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
/**
 * Short description for class.
 *
 * @package       cake
 * @subpackage    cake.tests.fixtures
 */
class JoinABFixture extends CakeTestFixture {
/**
 * name property
 *
 * @var string 'JoinAsJoinB'
 * @access public
 */
	var $name = 'JoinAsJoinB';
/**
 * fields property
 *
 * @var array
 * @access public
 */
	var $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'join_a_id' => array('type' => 'integer', 'length' => 10, 'null' => true),
		'join_b_id' => array('type' => 'integer', 'default' => null),
		'other' => array('type' => 'string', 'default' => ''),
		'created' => array('type' => 'datetime', 'null' => true),
		'updated' => array('type' => 'datetime', 'null' => true)
	);
/**
 * records property
 *
 * @var array
 * @access public
 */
	var $records = array(
		array('join_a_id' => 1, 'join_b_id' => 2, 'other' => 'Data for Join A 1 Join B 2', 'created' => '2008-01-03 10:56:33', 'updated' => '2008-01-03 10:56:33'),
		array('join_a_id' => 2, 'join_b_id' => 3, 'other' => 'Data for Join A 2 Join B 3', 'created' => '2008-01-03 10:56:34', 'updated' => '2008-01-03 10:56:34'),
		array('join_a_id' => 3, 'join_b_id' => 1, 'other' => 'Data for Join A 3 Join B 1', 'created' => '2008-01-03 10:56:35', 'updated' => '2008-01-03 10:56:35')
	);
}

?>
