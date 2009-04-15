<?php
/* SVN FILE: $Id: counter_cache_user_fixture.php 7848 2008-11-08 02:58:37Z nate $ */
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
 * @since         CakePHP(tm) v 1.2.0.4667
 * @version       $Revision: 7848 $
 * @modifiedby    $LastChangedBy: renan.saddam $
 * @lastmodified  $Date: 2008-11-07 21:58:37 -0500 (Fri, 07 Nov 2008) $
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
/**
 * Short description for class.
 *
 * @package       cake
 * @subpackage    cake.tests.fixtures
 */
class CounterCacheUserNonstandardPrimaryKeyFixture extends CakeTestFixture {

	var $name = 'CounterCacheUserNonstandardPrimaryKey';

	var $fields = array(
		'uid' => array('type' => 'integer', 'key' => 'primary'),
		'name' => array('type' => 'string', 'length' => 255, 'null' => false),
		'post_count' => array('type' => 'integer', 'null' => true)
	);

	var $records = array(
		array('uid' => 66, 'name' => 'Alexander','post_count' => 2),
		array('uid' => 301, 'name' => 'Steven','post_count' => 1),
	);
}

?>