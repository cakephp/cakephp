<?php
/* SVN FILE: $Id: uuid_fixture.php 6354 2008-01-10 07:02:33Z nate $ */
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
 * @since			CakePHP(tm) v 1.2.0.6700
 * @version			$Revision: 6354 $
 * @modifiedby		$LastChangedBy: nate $
 * @lastmodified	$Date: 2008-01-10 02:02:33 -0500 (Thu, 10 Jan 2008) $
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.fixtures
 */
class UuidFixture extends CakeTestFixture {
	var $name = 'Uuid';
	var $fields = array(
		'id' => array('type' => 'string', 'length' => 36, 'key' => 'primary'),
		'title' => 'string',
		'created' => 'datetime',
		'updated' => 'datetime'
	);
	var $records = array(
		array('id' => '47c36f9c-bc00-4d17-9626-4e183ca6822b', 'title' => 'Unique record 1', 'created' => '2008-03-13 01:16:23', 'updated' => '2008-03-13 01:18:31'),
		array('id' => '47c36f9c-f2b0-43f5-b3f7-4e183ca6822b', 'title' => 'Unique record 2', 'created' => '2008-03-13 01:18:24', 'updated' => '2008-03-13 01:20:32'),
		array('id' => '47c36f9c-0ffc-4084-9b03-4e183ca6822b', 'title' => 'Unique record 3', 'created' => '2008-03-13 01:20:25', 'updated' => '2008-03-13 01:22:33'),
		array('id' => '47c36f9c-2578-4c2e-aeab-4e183ca6822b', 'title' => 'Unique record 4', 'created' => '2008-03-13 01:22:26', 'updated' => '2008-03-13 01:24:34'),
	);
}

?>