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
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.fixtures
 * @since			CakePHP(tm) v 1.2.0.4667
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.fixtures
 */
class AcoFixture extends CakeTestFixture {
	var $name = 'Aco';
	var $fields = array(
		'id'		=> array('type' => 'integer', 'key' => 'primary', 'extra'=> 'auto_increment'),
		'parent_id'	=> array('type' => 'integer', 'length' => 10, 'null' => true),
		'model'		=> array('type' => 'string', 'default' => ''),
		'foreign_key' => array('type' => 'integer', 'length' => 10, 'null' => true),
		'alias'		=> array('type' => 'string', 'default' => ''),
		'lft'		=> array('type' => 'integer', 'length' => 10, 'null' => true),
		'rght'		=> array('type' => 'integer', 'length' => 10, 'null' => true)
	);

	var $records = array(
		array('id' => 1, 'parent_id' => null, 	'model' => null, 'foreign_key' => null, 'alias' => 'ROOT',			'lft' => 1,  'rght' => 18),
        array('id' => 2, 'parent_id' => 1,		'model' => null, 'foreign_key' => null, 'alias' => 'Controller1',	'lft' => 2,  'rght' => 9),
        array('id' => 3, 'parent_id' => 2,		'model' => null, 'foreign_key' => null, 'alias' => 'action1',		'lft' => 3,  'rght' => 6),
        array('id' => 4, 'parent_id' => 3,		'model' => null, 'foreign_key' => null, 'alias' => 'record1',		'lft' => 4,  'rght' => 5),
        array('id' => 5, 'parent_id' => 2,		'model' => null, 'foreign_key' => null, 'alias' => 'action2',		'lft' => 7,  'rght' => 8),
        array('id' => 6, 'parent_id' => 1,		'model' => null, 'foreign_key' => null, 'alias' => 'Controller2',	'lft' => 10, 'rght' => 17),
        array('id' => 7, 'parent_id' => 6,		'model' => null, 'foreign_key' => null, 'alias' => 'action1',		'lft' => 11, 'rght' => 14),
        array('id' => 8, 'parent_id' => 7,		'model' => null, 'foreign_key' => null, 'alias' => 'record1',		'lft' => 12, 'rght' => 13),
        array('id' => 9, 'parent_id' => 6,		'model' => null, 'foreign_key' => null, 'alias' => 'action2',		'lft' => 15, 'rght' => 16),
	);
}

?>