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
 * @subpackage		cake.tests.cases.libs
 * @since			CakePHP(tm) v 1.2.0.5432
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
uses('configure');
/**
 * Short description for class.
 *
 * @package    cake.tests
 * @subpackage cake.tests.cases.libs
 */
class ConfigureTest extends UnitTestCase {

	function setUp() {
		$this->Configure =& Configure::getInstance();
	}

	function testListCoreObjects() {
		$result = $this->Configure->listObjects('class', TEST_CAKE_CORE_INCLUDE_PATH . 'libs');
		$this->assertTrue(in_array('Xml', $result));
		$this->assertTrue(in_array('Cache', $result));
		$this->assertTrue(in_array('HttpSocket', $result));

		$result = $this->Configure->listObjects('behavior');
		$this->assertTrue(in_array('Tree', $result));

		$result = $this->Configure->listObjects('controller');
		$this->assertTrue(in_array('Pages', $result));

		$result = $this->Configure->listObjects('component');
		$this->assertTrue(in_array('Auth', $result));

		$result = $this->Configure->listObjects('view');
		$this->assertTrue(in_array('Media', $result));

		$result = $this->Configure->listObjects('helper');
		$this->assertTrue(in_array('Html', $result));
	}

	function testRead() {
		$expected = 'ok';

		$this->Configure->write('level1.level2.level3_1', $expected);
		$this->Configure->write('level1.level2.level3_2', 'something_else');
		$result = $this->Configure->read('level1.level2.level3_1');
		$this->assertEqual($expected, $result);

		$result = $this->Configure->read('level1.level2.level3_2');
		$this->assertEqual($result, 'something_else');
	}

	function testThatWereOnlyListingUserlandClasses() {
		$result = $this->Configure->listObjects('model');
		$notExpected = array('AppModel', 'Behavior', 'ConnectionManager',  'DbAcl', 'Model', 'Schema');

		foreach ($notExpected as $class) {
			$this->assertFalse(in_array($class, $result));
		}
	}

	function testWriteConfig() {
		$this->Configure->write('SomeName.someKey', 'myvalue');
		$result = $this->Configure->read('SomeName.someKey');
		$this->assertEqual($result, 'myvalue');

		$this->Configure->write('SomeName.someKey', null);
		$result = $this->Configure->read('SomeName.someKey');
		$this->assertEqual($result, null);
	}

	function tearDown() {
		unset($this->Configure);
		if (file_exists(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_core_paths')) {
			unlink(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_core_paths');
		}
	}
}

?>