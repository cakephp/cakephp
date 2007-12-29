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
 * @subpackage		cake.tests.cases.libs.controller.components
 * @since			CakePHP(tm) v 1.2.0.5436
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
uses('controller' . DS . 'controller', 'controller' . DS . 'components' . DS .'session');

class SessionTestController extends Controller {}
/**
 * Short description for class.
 *
 * @package    cake.tests
 * @subpackage cake.tests.cases.libs.controller.components
 */
class SessionComponentTest extends CakeTestCase {

	function setUp() {
		$this->Session = new SessionComponent();
	}

	function testSessionAutoStart() {
		$this->Session->startup(new SessionTestController());
		$this->assertTrue(isset($_SESSION) && empty($_SESSION));
	}

	function testSessionWriting() {
		$this->assertTrue($this->Session->write('Test.key.path', 'some value'));
		$this->assertEqual($this->Session->read('Test.key.path'), 'some value');
	}

	function tearDown() {
		unset($this->Session);
	}
}

?>
