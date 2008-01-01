<?php
/* SVN FILE: $Id: join_thing_fixture.php 4860 2007-04-15 07:19:31Z phpnut $ */
/**
 * Short description for file.
 *
 * Long description for file
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
 * @since			CakePHP(tm) v 1.2.0.4667
 * @version			$Revision: 4860 $
 * @modifiedby		$LastChangedBy: phpnut $
 * @lastmodified	$Date: 2007-04-15 03:19:31 -0400 (Sun, 15 Apr 2007) $
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.fixtures
 */
class JoinThingFixture extends CakeTestFixture {
	var $name = 'JoinThing';
	var $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary', 'extra'=> 'auto_increment'),
		'something_id' => array('type' => 'integer', 'length' => 10, 'null' => true),
		'something_else_id' => array('type' => 'integer', 'default' => ''),
		'doomed' => array('type' => 'boolean'),
		'created' => array('type' => 'datetime', 'null' => true),
		'updated' => array('type' => 'datetime', 'null' => true)
	);

	var $records = array(
		array('id' => 1, 'something_id' => 1, 'something_else_id' => 2, 'doomed' => true, 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'),
		array('id' => 2, 'something_id' => 2, 'something_else_id' => 3, 'doomed' => false, 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'),
		array('id' => 3, 'something_id' => 3, 'something_else_id' => 1, 'doomed' => true, 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31')
	);
}

?>