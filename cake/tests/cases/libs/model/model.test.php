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
 * @subpackage		cake.tests.cases.libs.model
 * @since			CakePHP(tm) v 1.2.0.4206
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
	if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
		define('CAKEPHP_UNIT_TEST_EXECUTION', 1);
	}
	require_once LIBS.'model'.DS.'model.php';

	class Test extends Model {
		var $useTable = false;
		var $name = 'Test';

		function loadInfo() {
			return new Set(array(
				array('name' => 'id', 'type' => 'integer', 'null' => '', 'default' => '1', 'length' => '8'),
				array('name' => 'name', 'type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
				array('name' => 'email', 'type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
				array('name' => 'notes', 'type' => 'text', 'null' => '1', 'default' => 'write some notes here', 'length' => ''),
				array('name' => 'created', 'type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
				array('name' => 'updated', 'type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
			));
		}
	}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class ModelTest extends UnitTestCase {

	function setUp() {
		$this->Test =& new Test();
	}

	function testIdentity() {
		$result = $this->Test->name;
		$expected = 'Test';
		$this->assertEqual($result, $expected);
	}

	function testCreation() {
		$expected = array('Test' => array('notes' => 'write some notes here'));
		$this->assertEqual($this->Test->create(), $expected);
	}
}

?>